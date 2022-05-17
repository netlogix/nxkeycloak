<?php

declare(strict_types=1);

namespace Netlogix\Nxkeycloak\Tests\Functional\Providers;

use ErrorException;
use Exception;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Token\AccessToken;
use Netlogix\Nxkeycloak\Providers\KeycloakProvider;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;

class KeycloakProviderTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = ['typo3conf/ext/nxkeycloak'];

    /**
     * @test
     *
     * @return void
     */
    public function itFetchesBaseAuthorizationUrlFromServer()
    {
        $authUrl = 'https://www.example.com/' . uniqid();

        // KeycloakProvider will fetch config from Keycloak server. Mock an empty response
        $mock = new MockHandler([new Response(200, [], '{"authorization_endpoint": "' . $authUrl . '"}')]);
        $stack = HandlerStack::create($mock);

        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $stack;

        $subject = new KeycloakProvider(['host' => 'https://www.example.com/', 'realm' => 'bar'], []);

        self::assertEquals($authUrl, $subject->getBaseAuthorizationUrl());
    }

    /**
     * @test
     *
     * @return void
     */
    public function itFetchesBaseAccessTokenUrlFromServer()
    {
        $urlAccessToken = 'https://www.example.com/' . uniqid();

        // KeycloakProvider will fetch config from Keycloak server. Mock an empty response
        $mock = new MockHandler([new Response(200, [], '{"token_endpoint": "' . $urlAccessToken . '"}')]);
        $stack = HandlerStack::create($mock);

        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $stack;

        $subject = new KeycloakProvider(['host' => 'https://www.example.com/', 'realm' => 'bar'], []);

        self::assertEquals($urlAccessToken, $subject->getBaseAccessTokenUrl([]));
    }

    /**
     * @test
     *
     * @return void
     */
    public function itFetchesResourceOwnerDetailsUrlFromServer()
    {
        $urlResourceOwnerDetails = 'https://www.example.com/' . uniqid();

        // KeycloakProvider will fetch config from Keycloak server. Mock an empty response
        $mock = new MockHandler([new Response(200, [], '{"userinfo_endpoint": "' . $urlResourceOwnerDetails . '"}')]);
        $stack = HandlerStack::create($mock);

        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $stack;

        $subject = new KeycloakProvider(['host' => 'https://www.example.com/', 'realm' => 'bar'], []);

        self::assertEquals(
            $urlResourceOwnerDetails,
            $subject->getResourceOwnerDetailsUrl(new AccessToken(['access_token' => 'foo']))
        );
    }

    /**
     * @test
     * @return void
     */
    public function itThrowsExceptionIfHostIsMissing()
    {
        $this->expectException(Exception::class);

        new KeycloakProvider(['host' => '', 'realm' => 'bar'], []);
    }

    /**
     * @test
     * @return void
     */
    public function itThrowsExceptionIfRealmIsMissing()
    {
        $this->expectException(Exception::class);

        new KeycloakProvider(['host' => 'foo', 'realm' => ''], []);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itThrowsExceptionIfConfigResponseIsNotJSON()
    {
        $this->expectException(ErrorException::class);

        // KeycloakProvider will fetch config from Keycloak server. Mock an empty response
        $mock = new MockHandler([new Response(200, [], 'this is not JSON!')]);
        $stack = HandlerStack::create($mock);

        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $stack;

        new KeycloakProvider(['host' => 'https://www.example.com/', 'realm' => 'bar'], []);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itCanFetchResourceOwnerDetails()
    {
        $ressourceOwnerID = uniqid();

        // KeycloakProvider will fetch config from Keycloak server. Mock an empty response
        $mock = new MockHandler(
            [
                // mock config request
                new Response(200, [], '{"userinfo_endpoint": "https://www.example.com/i/must/be/an/url"}'),
                // mock fetch owner request
                new Response(200, [], '{"sub": "' . $ressourceOwnerID . '"}')
            ]
        );
        $stack = HandlerStack::create($mock);

        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $stack;

        $subject = new KeycloakProvider(['host' => 'https://www.example.com/', 'realm' => 'bar'], []);
        $owner = $subject->getResourceOwner(new AccessToken(['access_token' => 'foo']));

        self::assertEquals($ressourceOwnerID, $owner->getId());
    }
}