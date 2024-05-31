<?php

namespace Rompetomp\InertiaBundle\Ssr;

class InertiaSsrResponse
{
    /**
     * Prepare the Inertia Server Side Rendering (SSR) response.
     */
    public function __construct(public string $head, public string $body)
    {
    }
}
