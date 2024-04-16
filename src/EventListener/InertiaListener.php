<?php

namespace Rompetomp\InertiaBundle\EventListener;

use Rompetomp\InertiaBundle\Architecture\DefaultInertiaErrorResponseInterface;
use Rompetomp\InertiaBundle\Architecture\InertiaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
        protected InertiaInterface                     $inertia,
        protected CsrfTokenManagerInterface            $csrfTokenManager,
        protected bool                                 $debug,
        protected ContainerInterface                   $container,
        protected DefaultInertiaErrorResponseInterface $defaultInertiaErrorResponse
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
        if ($this->container->getParameter('inertia.csrf.enabled')) {
            $csrfToken = $request->headers->get($this->container->getParameter('inertia.csrf.header_name'));

            if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($this->inertiaCsrfTokenName, $csrfToken))) {
                $event->setResponse($this->defaultInertiaErrorResponse->getResponse());
                return;
            }
        }

        if ('GET' === $request->getMethod()
            && $request->headers->get('X-Inertia-Version') !== $this->inertia->getVersion()
        ) {
            $response = new Response('', 409, ['X-Inertia-Location' => $request->getUri()]);

            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // Add the CSRF token on any response
        if ($this->container->getParameter('inertia.csrf.enabled')) {
            $event->getResponse()->headers->setCookie(
                new Cookie(
                    $this->container->getParameter('inertia.csrf.cookie_name'),
                    $this->csrfTokenManager->refreshToken($this->inertiaCsrfTokenName),
                    $this->container->getParameter('inertia.csrf.expire'),
                    $this->container->getParameter('inertia.csrf.path'),
                    $this->container->getParameter('inertia.csrf.domain'),
                    $this->container->getParameter('inertia.csrf.secure'),
                    false,
                    $this->container->getParameter('inertia.csrf.raw'),
                    $this->container->getParameter('inertia.csrf.samesite')
                )
            );
        }

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
