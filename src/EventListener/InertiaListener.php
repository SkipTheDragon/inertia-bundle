<?php

namespace Rompetomp\InertiaBundle\EventListener;

use Rompetomp\InertiaBundle\Architecture\InertiaInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Class InertiaListener.
 */
class InertiaListener
{

    protected string $inertiaCsrfTokenName = 'X-Inertia-CSRF-TOKEN';

    public function __construct(
        protected InertiaInterface $inertia,
        protected CsrfTokenManagerInterface $csrfTokenManager,
        protected bool $debug
    )
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->headers->get('X-Inertia')) {
            return;
        }

        // Validate CSRF token:
        $csrfToken = $request->headers->get('X-XSRF-TOKEN');

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($this->inertiaCsrfTokenName, $csrfToken))) {
            $event->setResponse(new Response('Invalid CSRF token.', 403));
            return;
        }

        if ('GET' === $request->getMethod()
            && $request->headers->get('X-Inertia-Version') !== $this->inertia->getVersion()
        ) {
            $response = new Response('', 409, ['X-Inertia-Location' => $request->getUri()]);

            // Add CSRF token to Inertia requests
            $response->headers->setCookie(new Cookie('XSRF-TOKEN', $this->csrfTokenManager->refreshToken($this->inertiaCsrfTokenName), 0, '/', null, false, true));

            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->getRequest()->headers->get('X-Inertia')) {
            return;
        }

        if ($this->debug && $event->getRequest()->isXmlHttpRequest()) {
            $event->getResponse()->headers->set('Symfony-Debug-Toolbar-Replace', 1);
        }

        if ($event->getResponse()->isRedirect()
            && 302 === $event->getResponse()->getStatusCode()
            && in_array($event->getRequest()->getMethod(), ['PUT', 'PATCH', 'DELETE'])
        ) {
            $event->getResponse()->setStatusCode(303);
        }
    }
}
