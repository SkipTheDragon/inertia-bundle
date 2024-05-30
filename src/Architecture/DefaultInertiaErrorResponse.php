<?php

namespace Rompetomp\InertiaBundle\Architecture;

use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a default response for Inertia Requests.
 *
 * @author  Tudorache Leonard Valentin <tudorache.leonard@wyverr.com>
 */
final class DefaultInertiaErrorResponse implements
    DefaultInertiaErrorResponseInterface
{
    public function getResponse(): Response
    {
        return new Response(
            'Something went wrong with Inertia!',
            Response::HTTP_FORBIDDEN
        );
    }
}
