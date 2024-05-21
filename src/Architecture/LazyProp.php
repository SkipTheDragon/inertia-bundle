<?php

namespace Rompetomp\InertiaBundle\Architecture;

class LazyProp
{
    /**
     * @var callable|string|array We didn't add a type here because property cannot be callable, but it can be a string or an array.
     */
    private $callback;

    /**
     * @param callable|string|array $callback
     */
    public function __construct(callable|string|array $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Evaluate the callback and return the result.
     *
     * @return mixed
     */
    public function __invoke(): mixed
    {
        return call_user_func($this->callback);
    }
}
