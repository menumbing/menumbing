<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class OAuthServerClient
{
    public function __construct(protected ClientInterface $httpClient)
    {
    }

    public function userInfo(string $token): array
    {
        $response = $this->httpClient->request(
            'GET',
            '/oauth2/me',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]
        );

        return $this->decodeResponse($response);
    }

    public function clientDetail(string $clientId, string $token): array
    {
        $response = $this->httpClient->request(
            'GET',
            sprintf('/oauth2/clients/%s', $clientId),
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]
        );

        return $this->decodeResponse($response);
    }

    public function tokenValidity(string $tokenId): array
    {
        $response = $this->httpClient->request(
            'GET',
            sprintf('/oauth2/tokens/%s/validity', $tokenId),
        );
        
        return $this->decodeResponse($response);
    }

    protected function decodeResponse(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true);
    }
}