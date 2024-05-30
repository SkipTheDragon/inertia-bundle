<?php

namespace Rompetomp\InertiaBundle\Architecture;

/**
 * The InertiaResponse attribute.
 *
 * @author  Tudorache Leonard Valentin <tudorache.leonard@wyverr.com>
 */
#[
    \Attribute(
        \Attribute::TARGET_CLASS |
            \Attribute::TARGET_METHOD |
            \Attribute::TARGET_FUNCTION
    )
]
class InertiaResponse
{
    /**
     * The attribute must have the same arguments as the InertiaInterface::render method.
     *
     * @param string $component
     * @param array $props
     * @param array $viewData
     * @param array $context
     * @param string|null $url
     */
    public function __construct(
        /**
         * The name of the component to render.
         */
        public string $component,

        /**
         * The controller method arguments to pass to the template.
         * Extra to the already returned array from the initial controller function.
         */
        public array $props = [],
        /**
         * The view data to pass to the twig template.
         */
        public array $viewData = [],
        /**
         * The context to pass to the serializer.
         */
        public array $context = [],

        /**
         * Location to redirect to.
         */
        public ?string $url = null
    ) {
    }
}
