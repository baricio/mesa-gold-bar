<?php

namespace Sympla\RemoteAuthentication;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Sympla\RemoteAuthentication\Exception\InvalidCredentialsException;

/**
 * Class OAuth2RemoteAuthentication
 * @package Sympla\RemoteAuthentication
 */
class OAuth2RemoteAuthentication
{

    /** @var ClientInterface */
    private $client;

    /** @var string */
    private $serverEndpoint = "";

    /**
     * OAuth2RemoteAuthentication constructor.
     * @param $serverEndpoint
     */
    public function __construct(ClientInterface $client, $serverEndpoint)
    {
        $this->client = $client;
        $this->serverEndpoint = $serverEndpoint;
    }

    /**
     * Discovers a user by its access token.
     *
     * @param $accessToken The access token.
     * @throws InvalidCredentialsException
     * @returns array User information.
     */
    public function getUserFromToken($accessToken)
    {
        try {
            $response = $this->client->get(
                $this->serverEndpoint,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken
                    ]
                ]
            );
        } catch (RequestException $e) {
            $error = json_decode($e->getResponse()->getBody()->getContents(), true)['error_description'];
            throw new InvalidCredentialsException($error);
        }

        $user = json_decode($response->getBody()->getContents(), true);
        return $user;
    }

    /**
     * Discovers a user from a Request Header.
     *
     * @param RequestInterface $request
     * @return mixed
     * @throws InvalidCredentialsException
     */
    public function getUserFromRequest(RequestInterface $request)
    {
        $token = $request->getHeaderLine('Authorization');

        if (empty($token)) {
            throw new InvalidCredentialsException(
                'There needs to be an Authorization header with a Bearer token to access this route.'
            );
        }

        $params = explode(" ", $token);
        if ($params[0] !== 'Bearer' || count($params) !== 2) {
            throw new InvalidCredentialsException(
                'The information passed in the Authorization header is not a valid Bearer token.'
            );
        }

        $accessToken = $params[1];
        return $this->getUserFromToken($accessToken);
    }
}
