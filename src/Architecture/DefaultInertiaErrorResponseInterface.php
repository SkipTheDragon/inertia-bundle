<?php

namespace Rompetomp\InertiaBundle\Architecture;

use Symfony\Component\HttpFoundation\Response;

/**
 * The interface that provides a default response for Inertia Requests.
 *
 * @author  Tudorache Leonard Valentin <tudorache.leonard@wyverr.com>
 */
interface DefaultInertiaErrorResponseInterface
{
    public function getResponse(): Response;
}
