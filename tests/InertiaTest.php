<?php

namespace Rompetomp\InertiaBundle\Tests;

use App\Kernel;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Rompetomp\InertiaBundle\Architecture\DefaultInertiaErrorResponse;
use Rompetomp\InertiaBundle\DependencyInjection\InertiaExtension;
use Rompetomp\InertiaBundle\EventListener\InertiaListener;
use Rompetomp\InertiaBundle\InertiaBundle;
use Rompetomp\InertiaBundle\Service\InertiaService;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Twig\Environment;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class InertiaTest extends TestCase
{
    private InertiaService $inertia;
    private Container $container;
    private LegacyMockInterface|MockInterface|Environment $environment;
    private LegacyMockInterface|MockInterface|RequestStack $requestStack;
    private LegacyMockInterface|MockInterface|Serializer|null $serializer;
    private array $inertiaConfig = [
        'root_view' => 'base.twig.html',
        'ssr' => ['enabled' => false, 'url' => 'http://localhost:3000'],
        'csrf' => ['enabled' => true]
    ];

    public function setUp(): void
    {
        $container = $this->createContainerBuilder([
            'framework' => ['secret' => 'testing', 'http_method_override' => false],
            'inertia' => $this->inertiaConfig,
        ]);
        $container->compile();

        $this->serializer = null;
        $this->container = $container;
        $this->environment = \Mockery::mock(Environment::class);
        $this->requestStack = \Mockery::mock(RequestStack::class);

        $this->inertia = new InertiaService($this->environment, $this->requestStack, $container, $this->serializer);
    }

    public function testSharedSingle()
    {
        $this->inertia->share('app_name', 'Testing App 1');
        $this->inertia->share('app_version', '1.0.0');
        $this->assertEquals('Testing App 1', $this->inertia->getShared('app_name'));
        $this->assertEquals('1.0.0', $this->inertia->getShared('app_version'));
    }

    public function testSharedMultiple()
    {
        $this->inertia->share('app_name', 'Testing App 2');
        $this->inertia->share('app_version', '2.0.0');
        $this->assertEquals(
            [
                'app_version' => '2.0.0',
                'app_name' => 'Testing App 2',
            ],
            $this->inertia->getShared()
        );
    }

    public function testVersion()
    {
        $this->assertNull($this->inertia->getVersion());
        $this->inertia->version('1.2.3');
        $this->assertEquals('1.2.3', $this->inertia->getVersion());
    }

    public function testRootView()
    {
        $this->assertEquals($this->inertiaConfig['root_view'], $this->inertia->getRootView());
    }

    public function testSetRootView()
    {
        $this->inertia->setRootView('other-root.twig.html');
        $this->assertEquals('other-root.twig.html', $this->inertia->getRootView());
    }

    public function testRenderJSON()
    {
        $mockRequest = \Mockery::mock(Request::class);
        $mockRequest->shouldReceive('getRequestUri')->andSet('headers', new HeaderBag(['X-Inertia' => true]));
        $mockRequest->allows()->getRequestUri()->andReturns('https://example.test');
        $this->requestStack->allows()->getCurrentRequest()->andReturns($mockRequest);

        $this->inertia = new InertiaService($this->environment, $this->requestStack, $this->container, $this->serializer);

        $response = $this->inertia->render('Dashboard');
        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testRenderProps()
    {
        $mockRequest = \Mockery::mock(Request::class);
        $mockRequest->shouldReceive('getRequestUri')->andSet('headers', new HeaderBag(['X-Inertia' => true]));
        $mockRequest->allows()->getRequestUri()->andReturns('https://example.test');
        $this->requestStack->allows()->getCurrentRequest()->andReturns($mockRequest);

        $this->inertia = new InertiaService($this->environment, $this->requestStack, $this->container, $this->serializer);

        $response = $this->inertia->render('Dashboard', ['test' => 123]);
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(['test' => 123], $data['props']);
    }

    public function testRenderSharedProps()
    {
        $mockRequest = \Mockery::mock(Request::class);
        $mockRequest->shouldReceive('getRequestUri')->andSet('headers', new HeaderBag(['X-Inertia' => true]));
        $mockRequest->allows()->getRequestUri()->andReturns('https://example.test');
        $this->requestStack->allows()->getCurrentRequest()->andReturns($mockRequest);

        $this->inertia = new InertiaService($this->environment, $this->requestStack, $this->container, $this->serializer);
        $this->inertia->share('app_name', 'Testing App 3');
        $this->inertia->share('app_version', '2.0.0');

        $response = $this->inertia->render('Dashboard', ['test' => 123]);
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(['test' => 123, 'app_name' => 'Testing App 3', 'app_version' => '2.0.0'], $data['props']);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function testRenderClosureProps()
    {
        $mockRequest = \Mockery::mock(Request::class);
        $mockRequest->shouldReceive('getRequestUri')->andSet('headers', new HeaderBag(['X-Inertia' => true]));
        $mockRequest->allows()->getRequestUri()->andReturns('https://example.test');
        $this->requestStack->allows()->getCurrentRequest()->andReturns($mockRequest);

        $this->inertia = new InertiaService($this->environment, $this->requestStack, $this->container, $this->serializer);

        $response = $this->inertia->render('Dashboard', ['test' => function () {
            return 'test-value';
        }]);
        $this->assertEquals(
            'test-value',
            json_decode($response->getContent(), true)['props']['test']
        );
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function testRenderDoc()
    {
        $mockRequest = \Mockery::mock(Request::class);
        $mockRequest->shouldReceive('getRequestUri')->andSet('headers', new HeaderBag(['X-Inertia' => false]));
        $mockRequest->allows()->getRequestUri()->andReturns('https://example.test');
        $this->requestStack->allows()->getCurrentRequest()->andReturns($mockRequest);

        $this->environment->allows('render')->andReturn('<div>123</div>');

        $this->inertia = new InertiaService($this->environment, $this->requestStack, $this->container, $this->serializer);

        $response = $this->inertia->render('Dashboard');
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testViewDataSingle()
    {
        $this->inertia->viewData('app_name', 'Testing App 1');
        $this->inertia->viewData('app_version', '1.0.0');
        $this->assertEquals('Testing App 1', $this->inertia->getViewData('app_name'));
        $this->assertEquals('1.0.0', $this->inertia->getViewData('app_version'));
    }

    public function testViewDataMultiple()
    {
        $this->inertia->viewData('app_name', 'Testing App 2');
        $this->inertia->viewData('app_version', '2.0.0');
        $this->assertEquals(
            [
                'app_version' => '2.0.0',
                'app_name' => 'Testing App 2',
            ],
            $this->inertia->getViewData()
        );
    }

    public function testContextSingle()
    {
        $this->inertia->context('groups', ['group1', 'group2']);
        $this->assertEquals(['group1', 'group2'], $this->inertia->getContext('groups'));
    }

    public function testContextMultiple()
    {
        $this->inertia->context('groups', ['group1', 'group2']);
        $this->assertEquals(
            [
                'groups' => ['group1', 'group2'],
            ],
            $this->inertia->getContext()
        );
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function testTypesArePreservedUsingJsonEncode()
    {
        $mockRequest = \Mockery::mock(Request::class);
        $mockRequest->shouldReceive('getRequestUri')->andSet('headers', new HeaderBag(['X-Inertia' => true]));
        $mockRequest->allows()->getRequestUri()->andReturns('https://example.test');
        $this->requestStack->allows()->getCurrentRequest()->andReturns($mockRequest);

        $this->inertia = new InertiaService($this->environment, $this->requestStack, $this->container, $this->serializer);

        $this->innerTestTypesArePreserved(false);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function testTypesArePreservedUsingSerializer()
    {
        $mockRequest = \Mockery::mock(Request::class);
        $mockRequest->shouldReceive('getRequestUri')->andSet('headers', new HeaderBag(['X-Inertia' => true]));
        $mockRequest->allows()->getRequestUri()->andReturns('https://example.test');
        $this->requestStack->allows()->getCurrentRequest()->andReturns($mockRequest);

        $this->serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $this->inertia = new InertiaService($this->environment, $this->requestStack, $this->container, $this->serializer);

        $this->innerTestTypesArePreserved(true);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    private function innerTestTypesArePreserved($usingSerializer = false)
    {
        $props = [
            'integer' => 123,
            'float' => 1.23,
            'string' => 'test',
            'null' => null,
            'true' => true,
            'false' => false,
            'object' => new \DateTime(),
            'empty_object' => new \stdClass(),
            'iterable_object' => new \ArrayObject([1, 2, 3]),
            'empty_iterable_object' => new \ArrayObject(),
            'array' => [1, 2, 3],
            'empty_array' => [],
            'associative_array' => ['test' => 'test']
        ];

        $response = $this->inertia->render('Dashboard', $props);
        $data = json_decode($response->getContent(), false);
        $responseProps = (array)$data->props;

        $this->assertIsInt($responseProps['integer']);
        $this->assertIsFloat($responseProps['float']);
        $this->assertIsString($responseProps['string']);
        $this->assertNull($responseProps['null']);
        $this->assertTrue($responseProps['true']);
        $this->assertFalse($responseProps['false']);
        $this->assertIsObject($responseProps['object']);
        $this->assertIsObject($responseProps['empty_object']);

        if (!$usingSerializer) {
            $this->assertIsObject($responseProps['iterable_object']);
        } else {
            $this->assertIsArray($responseProps['iterable_object']);
        }

        $this->assertIsObject($responseProps['empty_iterable_object']);
        $this->assertIsArray($responseProps['array']);
        $this->assertIsArray($responseProps['empty_array']);
        $this->assertIsObject($responseProps['associative_array']);
    }

    private static function createContainerBuilder(array $configs = []): ContainerBuilder
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.bundles' => ['FrameworkBundle' => FrameworkBundle::class, 'InertiaBundle' => InertiaBundle::class],
            'kernel.bundles_metadata' => [],
            'kernel.cache_dir' => __DIR__,
            'kernel.debug' => false,
            'kernel.environment' => 'test',
            'kernel.name' => 'kernel',
            'kernel.root_dir' => __DIR__,
            'kernel.project_dir' => __DIR__,
            'kernel.container_class' => 'AutowiringTestContainer',
            'kernel.charset' => 'utf8',
            'kernel.runtime_environment' => 'test',
            'kernel.build_dir' => __DIR__,
            'debug.file_link_format' => null,
        ]));

        $container->registerExtension(new FrameworkExtension());
        $container->registerExtension(new InertiaExtension());

        foreach ($configs as $extension => $config) {
            $container->loadFromExtension($extension, $config);
        }

        return $container;
    }

    public function testCSRFInvalidTokenRequest()
    {
        $listener = new InertiaListener(
            $this->inertia,
            $this->createMock(CsrfTokenManagerInterface::class),
            false,
            $this->container,
            new DefaultInertiaErrorResponse()
        );

        // Create mock request:
        $request = Request::create('http://localhost/');
        $request->headers->set('X-Inertia', true);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        $event->setResponse(new Response('Test Content.'));

        $listener->onKernelRequest($event);
        $this->assertEquals('Something went wrong with Inertia!', $event->getResponse()->getContent());
    }

    public function testCSRFValidTokenRequest()
    {
        $csrfToken = $this->createMock(CsrfTokenManagerInterface::class);

        $csrfToken->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(true);

        $listener = new InertiaListener(
            $this->inertia,
            $csrfToken,
            false,
            $this->container,
            new DefaultInertiaErrorResponse()
        );

        // Create mock request:
        $request = Request::create('http://localhost/');
        $request->headers->set('X-Inertia', true);
        $request->cookies->set('X-XSRF-TOKEN', 'sadlokasds' );

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        $event->setResponse(new Response('Test Content.'));

        $listener->onKernelRequest($event);

        $this->assertEquals('Test Content.', $event->getResponse()->getContent());
    }

    public function testCsrfTokenResponseSetCookie()
    {
        $request = Request::create('http://localhost/');
        $request->headers->set('X-Inertia', true);

        $listener = new InertiaListener(
            $this->inertia,
            $this->createMock(CsrfTokenManagerInterface::class),
            false,
            $this->container,
            new DefaultInertiaErrorResponse()
        );

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response('Test Content.'),
        );

        $listener->onKernelResponse($event);

        $this->assertEquals('XSRF-TOKEN', $event->getResponse()->headers->getCookies(ResponseHeaderBag::COOKIES_FLAT)[0]->getName());
    }
}
