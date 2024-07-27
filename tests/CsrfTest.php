<?php

namespace Rompetomp\InertiaBundle\Tests;

use Rompetomp\InertiaBundle\Architecture\DefaultInertiaErrorResponse;
use Rompetomp\InertiaBundle\EventListener\InertiaListener;
use Rompetomp\InertiaBundle\Tests\Fixtures\InertiaBaseConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfTest extends InertiaBaseConfig
{
    public function testCSRFInvalidTokenRequest()
    {
        $listener = new InertiaListener(
            $this->inertia,
            $this->createMock(CsrfTokenManagerInterface::class),
            false,
            $this->container,
            new DefaultInertiaErrorResponse()
        );

        // Create mock request:
        $request = Request::create('http://localhost/');
        $request->headers->set('X-Inertia', true);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $event->setResponse(new Response('Test Content.'));

        $listener->onKernelRequest($event);
        $this->assertEquals(
            'Invalid CSRF token.',
            $event->getResponse()->getContent()
        );
    }

    public function testCSRFValidTokenRequest()
    {
        $csrfToken = $this->createMock(CsrfTokenManagerInterface::class);

        $csrfToken
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(true);

        $listener = new InertiaListener(
            $this->inertia,
            $csrfToken,
            false,
            $this->container,
            new DefaultInertiaErrorResponse()
        );

        // Create mock request:
        $request = Request::create('http://localhost/');
        $request->headers->set('X-Inertia', true);
        $request->cookies->set('X-XSRF-TOKEN', 'sadlokasds');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $event->setResponse(new Response('Test Content.'));

        $listener->onKernelRequest($event);

        $this->assertEquals(
            'Test Content.',
            $event->getResponse()->getContent()
        );
    }

    public function testCsrfTokenResponseSetCookie()
    {
        $request = Request::create('http://localhost/');
        $request->headers->set('X-Inertia', true);

        $listener = new InertiaListener(
            $this->inertia,
            $this->createMock(CsrfTokenManagerInterface::class),
            false,
            $this->container,
            new DefaultInertiaErrorResponse()
        );

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response('Test Content.')
        );

        $listener->onKernelResponse($event);

        $this->assertEquals(
            'XSRF-TOKEN',
            $event
                ->getResponse()
                ->headers->getCookies(ResponseHeaderBag::COOKIES_FLAT)[0]
                ->getName()
        );
    }
}
