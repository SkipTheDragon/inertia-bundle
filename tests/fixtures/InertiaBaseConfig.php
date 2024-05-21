<?php

namespace Rompetomp\InertiaBundle\Tests\Fixtures;

use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Rompetomp\InertiaBundle\DependencyInjection\InertiaExtension;
use Rompetomp\InertiaBundle\InertiaBundle;
use Rompetomp\InertiaBundle\Service\InertiaService;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Serializer;
use Twig\Environment;

class InertiaBaseConfig extends TestCase
{
    protected InertiaService $inertia;
    protected Container $container;
    protected LegacyMockInterface|MockInterface|Environment $environment;
    protected LegacyMockInterface|MockInterface|RequestStack $requestStack;
    protected LegacyMockInterface|MockInterface|Serializer|null $serializer;

    protected array $inertiaConfig = [
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
}
