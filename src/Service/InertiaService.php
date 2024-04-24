<?php

namespace Rompetomp\InertiaBundle\Service;

use Closure;
use Rompetomp\InertiaBundle\Architecture\InertiaInterface;
use Rompetomp\InertiaBundle\Architecture\LazyProp;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class InertiaService implements InertiaInterface
{
    protected array $sharedProps = [];

    protected array $sharedViewData = [];

    protected array $sharedContext = [];

    protected ?string $version = null;

    protected bool $useSsr = false;

    protected string $ssrUrl = '';

    protected ?string $rootView = null;

    /**
     * Inertia constructor.
     */
    public function __construct(
        protected Environment          $engine,
        protected RequestStack         $requestStack,
        private ContainerInterface     $container,
        protected ?SerializerInterface $serializer = null,
    )
    {
        if ($this->container->hasParameter('inertia.ssr.enabled') && $this->container->getParameter('inertia.ssr.enabled')) {
            $this->useSsr(true);
            $this->setSsrUrl($this->container->getParameter('inertia.ssr.url'));
        }

        if ($this->container->hasParameter('inertia.root_view')) {
            $this->setRootView($this->container->getParameter('inertia.root_view'));
        }
    }

    public function share(string $key, mixed $value = null): void
    {
        $this->sharedProps[$key] = $value;
    }

    public function getShared(string $key = null): mixed
    {
        if ($key) {
            return $this->sharedProps[$key] ?? null;
        }

        return $this->sharedProps;
    }

    public function viewData(string $key, mixed $value = null): void
    {
        $this->sharedViewData[$key] = $value;
    }

    public function getViewData(string $key = null): mixed
    {
        if ($key) {
            return $this->sharedViewData[$key] ?? null;
        }

        return $this->sharedViewData;
    }

    public function context(string $key, mixed $value = null): void
    {
        $this->sharedContext[$key] = $value;
    }

    public function getContext(string $key = null): mixed
    {
        if ($key) {
            return $this->sharedContext[$key] ?? null;
        }

        return $this->sharedContext;
    }

    public function version(string $version): void
    {
        $this->version = $version;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setRootView(string $rootView): void
    {
        $this->rootView = $rootView;
    }

    public function getRootView(): ?string
    {
        return $this->rootView;
    }

    public function useSsr(bool $useSsr): void
    {
        $this->useSsr = $useSsr;
    }

    public function isSsr(): bool
    {
        return $this->useSsr;
    }

    public function setSsrUrl(string $url): void
    {
        $this->ssrUrl = $url;
    }

    public function getSsrUrl(): string
    {
        return $this->ssrUrl;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function render(string $component, array $props = [], array $viewData = [], array $context = [], string $url = null): Response
    {
        if ($this->rootView === null) {
            throw new RuntimeError('The root view is not set. Inertia bundle requires a root view to render the page, set one globally in config/packages/inertia.yaml or pass it to the render method.');
        }

        $context = array_merge($this->sharedContext, $context);
        $viewData = array_merge($this->sharedViewData, $viewData);
        $props = array_merge($this->sharedProps, $props);
        $request = $this->requestStack->getCurrentRequest();
        $url = $url ?? $request->getRequestUri();

        $only = array_filter(explode(',', $request->headers->get('X-Inertia-Partial-Data') ?? ''));
        $props = ($only && $request->headers->get('X-Inertia-Partial-Component') === $component)
            ? self::array_only($props, $only)
            : array_filter($props, function ($prop) {
                return !($prop instanceof LazyProp);
            });

        array_walk_recursive($props, function (&$prop) {
            if ($prop instanceof LazyProp) {
                $prop = call_user_func($prop);
            } elseif ($prop instanceof Closure) {
                $prop = $prop();
            }
        });

        $version = $this->version;
        $page = $this->serialize(compact('component', 'props', 'url', 'version'), $context);

        if ($request->headers->get('X-Inertia')) {
            return new JsonResponse($page, 200, [
                'Vary' => 'Accept',
                'X-Inertia' => true,
            ]);
        }

        $response = new Response();
        $response->setContent($this->engine->render($this->rootView, compact('page', 'viewData')));

        return $response;
    }

    public function location(string|RedirectResponse $url): Response
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($url instanceof RedirectResponse) {
            $url = $url->getTargetUrl();
        }

        if ($request->headers->has('X-Inertia')) {
            return new Response('', 409, ['X-Inertia-Location' => $url]);
        }

        return new RedirectResponse($url);
    }

    /**
     * @param callable|array|string $callback
     * @return LazyProp
     */
    public function lazy(callable|array|string $callback): LazyProp
    {
        if (is_string($callback)) {
            $callback = explode('::', $callback, 2);
        }

        if (is_array($callback)) {
            list($name, $action) = array_pad($callback, 2, null);
            $useContainer = is_string($name) && $this->container->has($name);
            if ($useContainer && !is_null($action)) {
                return new LazyProp([$this->container->get($name), $action]);
            }

            if ($useContainer && is_null($action)) {
                return new LazyProp($this->container->get($name));
            }
        }

        return new LazyProp($callback);
    }

    /**
     * Serializes the given objects with the given context if the Symfony Serializer is available. If not, use `json_encode`.
     *
     * @see https://github.com/OWASP/CheatSheetSeries/blob/master/cheatsheets/AJAX_Security_Cheat_Sheet.md#always-return-json-with-an-object-on-the-outside
     *
     * @param array $page
     * @param array $context
     *
     * @return array @return array returns a decoded array of the previously JSON-encoded data, so it can safely be given to {@see JsonResponse}
     */
    private function serialize(array $page, array $context = []): array
    {
        if (null !== $this->serializer) {
            $json = $this->serializer->serialize($page, 'json', array_merge([
                'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
                AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function () {
                    return null;
                },
                AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => true,
                AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true,
            ], $context));
        } else {
            $json = json_encode($page);
        }

        return (array)json_decode($json, false);
    }

    private static function array_only($array, $keys): array
    {
        return array_intersect_key($array, array_flip((array)$keys));
    }
}
