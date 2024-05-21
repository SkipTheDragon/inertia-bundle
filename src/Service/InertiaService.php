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

/**
 * The class that provides the Inertia service to the application.
 */
class InertiaService implements InertiaInterface
{
    protected array $sharedProps = [];

    protected array $sharedViewData = [];

    protected array $sharedContext = [];

    protected ?string $version = null;

    protected bool $useSsr = false;

    protected string $ssrUrl = '';

    protected ?string $rootView = null;

    public function __construct(
        protected Environment          $engine,
        protected RequestStack         $requestStack,
        private ContainerInterface     $container,
        protected ?SerializerInterface $serializer = null,
    )
    {
        /**
         * Check if SSR is enabled and set the SSR URL.
         */
        if ($this->container->hasParameter('inertia.ssr.enabled') && $this->container->getParameter('inertia.ssr.enabled')) {
            $this->useSsr(true);
            $this->setSsrUrl($this->container->getParameter('inertia.ssr.url'));
        }

        /**
         * Set the root view if it is set in the configuration.
         */
        if ($this->container->hasParameter('inertia.root_view')) {
            $this->setRootView($this->container->getParameter('inertia.root_view'));
        }
    }

    /**
     * Adds global component properties for the templating system.
     * @param string $key
     * @param mixed|null $value
     * @return void
     */
    public function share(string $key, mixed $value = null): void
    {
        $this->sharedProps[$key] = $value;
    }

    /**
     * Get global component properties by key.
     * @param string|null $key
     * @return mixed
     */
    public function getShared(string $key = null): mixed
    {
        if ($key) {
            return $this->sharedProps[$key] ?? null;
        }

        return $this->sharedProps;
    }

    /**
     * Set additional view data for the templating system.
     *
     * @param string $key
     * @param mixed|null $value
     * @return void
     */
    public function viewData(string $key, mixed $value = null): void
    {
        $this->sharedViewData[$key] = $value;
    }

    /**
     * Get the data that should be passed to the view.
     *
     * @param string|null $key
     * @return mixed
     */
    public function getViewData(string $key = null): mixed
    {
        if ($key) {
            return $this->sharedViewData[$key] ?? null;
        }

        return $this->sharedViewData;
    }

    /**
     * Set the context for the serializer.
     *
     * @param string $key
     * @param mixed|null $value
     * @return void
     */
    public function context(string $key, mixed $value = null): void
    {
        $this->sharedContext[$key] = $value;
    }

    /**
     * Get the context by key.
     * @param string|null $key
     * @return mixed
     */
    public function getContext(string $key = null): mixed
    {
        if ($key) {
            return $this->sharedContext[$key] ?? null;
        }

        return $this->sharedContext;
    }

    /**
     * Set the version of the application.
     * @param string $version
     * @return void
     */
    public function version(string $version): void
    {
        $this->version = $version;
    }

    /**
     * Get the version of the application.
     * @return string|null
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * Set the root view.
     * @param string $rootView
     * @return void
     */
    public function setRootView(string $rootView): void
    {
        $this->rootView = $rootView;
    }

    /**
     * Get the root view.
     * @return string|null
     */
    public function getRootView(): ?string
    {
        return $this->rootView;
    }

    /**
     * Set if it uses ssr.
     * @param bool $useSsr
     * @return void
     */
    public function useSsr(bool $useSsr): void
    {
        $this->useSsr = $useSsr;
    }

    /**
     * Check if it's using ssr.
     * @return bool
     */
    public function isSsr(): bool
    {
        return $this->useSsr;
    }

    /**
     * Set the ssr url where it will fetch its content.
     * @param string $url
     * @return void
     */
    public function setSsrUrl(string $url): void
    {
        $this->ssrUrl = $url;
    }

    /**
     * Get the ssr url where it will fetch its content.
     * @return string
     */
    public function getSsrUrl(): string
    {
        return $this->ssrUrl;
    }

    /**
     * Function that makes your controller return an Inertia response.
     *
     * @param string $component The component to render. Can be a path, but it must be relative to the pages dir that you are importing inside your frontend.
     * @param array $props The props to pass to the component.
     * @param array $viewData The view data to pass to the root view (ak. your twig template).
     * @param array $context The context to pass to the serializer.
     * @param string|null $url The URL to pass to the page.
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(string $component, array $props = [], array $viewData = [], array $context = [], ?string $url = null): Response
    {
        /**
         * If the root view is not set, throw an exception.
         */
        if ($this->rootView === null) {
            throw new RuntimeError('The root view is not set. Inertia bundle requires a root view to render the page, set one globally in config/packages/inertia.yaml or pass it to the render method.');
        }

        $context = array_merge($this->sharedContext, $context);
        $viewData = array_merge($this->sharedViewData, $viewData);
        $props = array_merge($this->sharedProps, $props);
        $request = $this->requestStack->getCurrentRequest();
        $url = $url ?? $request->getRequestUri();

        if ($url === '') {
            $url = null;
        }

        /**
         * Get the props that should be loaded.
         */
        $only = array_filter(explode(',', $request->headers->get('X-Inertia-Partial-Data') ?? ''));

        /**
         * Decide what props to load, and what props to skip.
         * We will not load lazy props on the first visit, only on partial reloads.
         */
        $props = ($only && $request->headers->get('X-Inertia-Partial-Component') === $component)
            ? self::array_only($props, $only)
            : array_filter($props, function ($prop) {
                return !($prop instanceof LazyProp);
            });

        /**
         * Walk through the props and resolve the lazy props.
         */
        array_walk_recursive($props, function (&$prop) {
            if ($prop instanceof LazyProp) {
                $prop = call_user_func($prop);
            } elseif ($prop instanceof Closure) {
                $prop = $prop();
            }
        });

        $version = $this->version;

        /**
         * Serialize the page props.
         */
        $page = $this->serialize(compact('component', 'props', 'url', 'version'), $context);

        /**
         * If the request is an Inertia request, we return a JSON response.
         */
        if ($request->headers->get('X-Inertia')) {
            return new JsonResponse($page, Response::HTTP_OK, [
                'Vary' => 'Accept',
                'X-Inertia' => true,
            ]);
        }

        /**
         * Update the Response content to use the root view, pass the props and render the page.
         */
        $response = new Response();

        $response->setContent($this->engine->render($this->rootView, compact('page', 'viewData')));

        return $response;
    }

    /**
     * Function to redirect users from the backend to a non inertia page.
     *
     * @param string|RedirectResponse $url
     * @return Response
     */
    public function location(string|RedirectResponse $url): Response
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($url instanceof RedirectResponse) {
            $url = $url->getTargetUrl();
        }

        if ($request->headers->has('X-Inertia')) {
            return new Response('', Response::HTTP_CONFLICT, ['X-Inertia-Location' => $url]);
        }

        return new RedirectResponse($url);
    }

    /**
     * Lazy load a prop. This is useful when you want to load a prop only when it is needed.
     *
     * NEVER included on first visit...
     * OPTIONALLY included on partial reloads...
     * ONLY evaluated when needed...
     *
     * @see https://inertiajs.com/partial-reloads#lazy-data-evaluation
     *
     * @param callable|array|string $callback
     * @return LazyProp
     */
    public function lazy(callable|array|string $callback): LazyProp
    {
        /**
         * If the callback is a string we transform it into an array. This is useful when you want to call static methods.
         */
        if (is_string($callback)) {
            $callback = explode('::', $callback, 2);
        }

        /**
         * If the callback is an array, we check if the first element is a service in the container. If it is, we return a LazyProp with the service.
         */
        if (is_array($callback)) {
            list($name, $action) = array_pad($callback, 2, null);
            $useContainer = is_string($name) && $this->container->has($name);
            /**
             * A service is found in the container and an action is provided, we return a LazyProp with the service and the action.
             */
            if ($useContainer && !is_null($action)) {
                return new LazyProp([$this->container->get($name), $action]);
            }

            /**
             * A service is found in the container and an action is NOT provided, we return a LazyProp with the service and without the action.
             */
            if ($useContainer && is_null($action)) {
                return new LazyProp($this->container->get($name));
            }
        }

        /**
         * If the callback is a string, and it is not a service in the container, we return a LazyProp with the string and action.
         */
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
     * @return array returns a decoded array of the previously JSON-encoded data, so it can safely be given to {@see JsonResponse}
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

    /**
     * @param $array
     * @param $keys
     * @return array
     */
    private static function array_only($array, $keys): array
    {
        return array_intersect_key($array, array_flip((array)$keys));
    }
}
