<?php

namespace Rompetomp\InertiaBundle\Architecture;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Interface InertiaInterface.
 *
 * @author  Hannes Vermeire <hannes@codedor.be>
 *
 * @since   2019-08-09
 */
interface InertiaInterface
{
    /**
     * Adds global component properties for the templating system.
     * @param string $key
     * @param mixed|null $value
     * @return void
     */
    public function share(string $key, mixed $value = null): void;

    /**
     * Get global component properties by key.
     * @param string|null $key
     * @return mixed
     */
    public function getShared(?string $key = null): mixed;

    /**
     * Set additional view data for the templating system.
     * @param string $key
     * @param mixed|null $value
     * @return void
     */
    public function viewData(string $key, mixed $value = null): void;

    /**
     * Get the data that should be passed to the view.
     * @param string|null $key
     * @return mixed
     */
    public function getViewData(?string $key = null): mixed;

    /**
     * Set the version of the application.
     * @param string $version
     * @return void
     */
    public function version(string $version): void;

    /**
     * Adds a context for the serializer.
     * @param mixed|null $value
     */
    public function context(string $key, mixed $value = null): void;

    /**
     * Get the context for the serializer by key.
     * @param string|null $key
     * @return mixed
     */
    public function getContext(?string $key = null): mixed;

    /**
     * Get the version of the application.
     * @return string|null
     */
    public function getVersion(): ?string;

    /**
     * Set the root view/twig base template to use for this request only.
     * @param string $rootView
     * @return void
     */
    public function setRootView(string $rootView): void;

    /**
     * Get the root view.
     * @return string|null
     */
    public function getRootView(): ?string;

    /**
     * Set if it uses ssr.
     * @param bool $useSsr
     * @return void
     */
    public function useSsr(bool $useSsr): void;

    /**
     * Check if it's using ssr.
     * @return bool
     */
    public function isSsr(): bool;

    /**
     * Set the ssr url where it will fetch its content.
     * @param string $url
     * @return void
     */
    public function setSsrUrl(string $url): void;

    /**
     * Get the ssr url where it will fetch its content.
     * @return string
     */
    public function getSsrUrl(): string;

    /**
     * Function to redirect users from the backend to a non inertia page.
     *
     * @param string|RedirectResponse $url
     * @return Response
     */
    public function location(string|RedirectResponse $url): Response;

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
    public function lazy(callable|array|string $callback): LazyProp;

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
    public function render(
        string $component,
        array $props = [],
        array $viewData = [],
        array $context = [],
        ?string $url = null
    ): Response;
}
