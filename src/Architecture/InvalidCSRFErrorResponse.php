<?php

namespace Rompetomp\InertiaBundle\Architecture;

use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a invalid CSRF response for Inertia Requests.
 *
 * @author  Tudorache Leonard Valentin <tudorache.leonard@wyverr.com>
 */
final class InvalidCSRFErrorResponse implements
    DefaultInertiaErrorResponseInterface
{
    public function getResponse(): Response
    {
        return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
    }
}
