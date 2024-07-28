<?php

namespace Rompetomp\InertiaBundle\Tests;

use Rompetomp\InertiaBundle\Architecture\InertiaResponse;
use Rompetomp\InertiaBundle\EventListener\InertiaResponseAttributeListener;
use Rompetomp\InertiaBundle\Service\InertiaFormProcessorService;
use Rompetomp\InertiaBundle\Service\InertiaService;
use Rompetomp\InertiaBundle\Tests\Fixtures\InertiaBaseConfig;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AttributesTest extends InertiaBaseConfig
{
    protected array $inertiaConfig = [
        'root_view' => 'base.twig.html',
        'ssr' => ['enabled' => false, 'url' => 'http://localhost:3000'],
        'csrf' => ['enabled' => false], // disable csrf
    ];

    public function testAttributeOnController()
    {
        $mockRequest = \Mockery::mock(Request::class);
        $mockRequest
            ->shouldReceive('getRequestUri')
            ->andSet('headers', new HeaderBag(['X-Inertia' => true]));
        $mockRequest
            ->allows()
            ->getRequestUri()
            ->andReturns('https://example.test');
        $this->requestStack
            ->allows()
            ->getCurrentRequest()
            ->andReturns($mockRequest);

        $listener = new InertiaResponseAttributeListener(
            new InertiaService(
                $this->environment,
                $this->requestStack,
                $this->container,
                new InertiaFormProcessorService(),
                $this->serializer
            )
        );

        $request = Request::create('http://localhost/');
        $request->headers->set('X-Inertia', true);
        $request->setMethod(Request::METHOD_GET);
        $request->attributes->set(
            '_template',
            new InertiaResponse(component: 'Index')
        );

        $kernelInterface = $this->createMock(HttpKernelInterface::class);

        $event = new ViewEvent(
            $kernelInterface,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            []
        );

        $listener->onKernelView($event);
        $this->assertEquals(
            '{"component":"Index","props":[],"url":null,"version":null}',
            $event->getResponse()->getContent()
        );
    }
}
