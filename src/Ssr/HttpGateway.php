<?php

namespace Rompetomp\InertiaBundle\Ssr;

use Exception;
use Rompetomp\InertiaBundle\Service\InertiaInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpGateway implements GatewayInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private InertiaInterface $inertia
    )
    {
    }

    /**
     * Dispatch the Inertia page to the Server Side Rendering engine.
     * @throws TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface
     */
    public function dispatch(array $page): ?Response
    {
        $response = $this->httpClient->request(
            'POST',
            $this->inertia->getSsrUrl(),
            [
                'headers' => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                'body' => json_encode($page),
            ]
        );

        $content = $response->toArray();

        return new Response(
            implode("\n", $content['head']),
            $content['body']
        );
    }
}
