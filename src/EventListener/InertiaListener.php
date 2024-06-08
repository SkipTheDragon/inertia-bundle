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
 * The listener that processes a request to determine if it is an Inertia request and modifies the response accordingly.
 */
class InertiaListener
{
    /**
     * @var string The name of the CSRF token used to validate requests.
     */
    protected string $inertiaCsrfTokenName = 'X-Inertia-CSRF-TOKEN';

    public function __construct(
        protected InertiaInterface $inertia,
        protected CsrfTokenManagerInterface $csrfTokenManager,
        protected bool $debug,
        protected ContainerInterface $container,
        protected DefaultInertiaErrorResponseInterface $defaultInertiaErrorResponse
    ) {
    }

    /**
     * @param RequestEvent $event
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        /**
         * If the request is not an Inertia request, we don't need to do anything.
         */
        if (!$request->headers->get('X-Inertia')) {
            return;
        }

        // Validate CSRF token
        if ($this->container->getParameter('inertia.csrf.enabled')) {
            $csrfToken = $request->headers->get(
                $this->container->getParameter('inertia.csrf.header_name')
            );

            if (
                !$this->csrfTokenManager->isTokenValid(
                    new CsrfToken($this->inertiaCsrfTokenName, $csrfToken)
                )
            ) {
                $event->setResponse(
                    $this->defaultInertiaErrorResponse->getResponse()
                );
                return;
            }
        }

        /**
         * Tell Inertia to update the page if the version has changed.
         */
        if (
            'GET' === $request->getMethod() &&
            $request->headers->get('X-Inertia-Version') !==
                $this->inertia->getVersion()
        ) {
            $response = new Response('', Response::HTTP_CONFLICT, [
                'X-Inertia-Location' => $request->getUri(),
            ]);

            $event->setResponse($response);
        }
    }

    /**
     * @param ResponseEvent $event
     * @return void
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $route = $event->getRequest()->attributes->get('_route');
        // Ignore requests to internal Symfony routes/Profiler
        if (is_string($route) && str_starts_with($route, '_')) {
            return;
        }
        /**
         * If the CSRF protection is enabled, we need to refresh the CSRF token.
         * We add this cookie to any request, not just Inertia requests.
         */
        if (
            $this->container->getParameter('inertia.csrf.enabled') &&
            $event->isMainRequest() && // Only set the cookie on the main request
            !$event->getResponse()->isRedirect() // Don't set the cookie on redirect responses
        ) {
            $event
                ->getResponse()
                ->headers->setCookie(
                    new Cookie(
                        $this->container->getParameter(
                            'inertia.csrf.cookie_name'
                        ),
                        $this->csrfTokenManager->refreshToken(
                            $this->inertiaCsrfTokenName
                        ),
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

        /**
         * If the request is not an Inertia request, we don't need to do anything.
         */
        if (!$event->getRequest()->headers->get('X-Inertia')) {
            return;
        }

        /**
         * Refreshes the toolbar when the request is an AJAX request.
         */
        if ($this->debug && $event->getRequest()->isXmlHttpRequest()) {
            $event
                ->getResponse()
                ->headers->set('Symfony-Debug-Toolbar-Replace', 1);
        }

        /**
         * If the response is a redirect and the request method is PUT, PATCH, or DELETE, we need to change the status code to 303.
         */
        if (
            $event->getResponse()->isRedirect() &&
            Response::HTTP_FOUND === $event->getResponse()->getStatusCode() &&
            in_array($event->getRequest()->getMethod(), [
                'PUT',
                'PATCH',
                'DELETE',
            ])
        ) {
            $event->getResponse()->setStatusCode(Response::HTTP_SEE_OTHER);
        }
    }
}
