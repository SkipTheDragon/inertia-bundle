<?php

namespace Rompetomp\InertiaBundle\Architecture;

use Symfony\Component\HttpFoundation\Response;

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
     *
     * @param mixed|null $value
     */
    public function share(string $key, mixed $value = null): void;

    /**
     * @param string|null $key
     * @return mixed
     */
    public function getShared(string $key = null): mixed;

    /**
     * Adds global view data for the templating system.
     *
     * @param mixed|null $value
     */
    public function viewData(string $key, mixed $value = null): void;

    /**
     * @param string|null $key
     * @return mixed
     */
    public function getViewData(string $key = null): mixed;

    public function version(string $version): void;

    /**
     * Adds a context for the serializer.
     *
     * @param mixed|null $value
     */
    public function context(string $key, mixed $value = null): void;

    /**
     * @param string|null $key
     * @return mixed
     */
    public function getContext(string $key = null): mixed;

    /**
     * @return string|null
     */
    public function getVersion(): ?string;

    public function setRootView(string $rootView): void;

    public function getRootView(): ?string;

    /**
     * Set if it uses ssr.
     */
    public function useSsr(bool $useSsr): void;

    /**
     * Check if it's using ssr.
     */
    public function isSsr(): bool;

    /**
     * Set the ssr url where it will fetch its content.
     */
    public function setSsrUrl(string $url): void;

    /**
     * Get the ssr url where it will fetch its content.
     */
    public function getSsrUrl(): string;

    /**
     * @param callable|array|string $callback
     */
    public function lazy(callable|array|string $callback): LazyProp;

    /**
     * @param string $component component name
     * @param array $props     component properties
     * @param array $viewData  templating view data
     * @param array $context   serialization context
     * @param string|null $url       custom url
     */
    public function render(string $component, array $props = [], array $viewData = [], array $context = [], ?string $url = null): Response;
}
