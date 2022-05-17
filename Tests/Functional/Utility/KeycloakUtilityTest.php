<?php

declare(strict_types=1);

namespace Netlogix\Nxkeycloak\Tests\Functional\Utility;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Netlogix\Nxkeycloak\Providers\KeycloakProvider;
use Netlogix\Nxkeycloak\Utility\KeycloakUtility;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;

class KeycloakUtilityTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = ['typo3conf/ext/nxkeycloak'];

    protected $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'nxkeycloak' => [
                'clientId' => '',
                'clientSecret' => '',
                'createAdmin' => '1',
                'disableLocalLogin' => '',
                'host' => 'http://127.0.0.1',
                'realm' => 'example',
            ],
        ]
    ];

    public function setUp(): void
    {
        parent::setUp();

        // KeycloakProvider will fetch config from Keycloak server. Mock an empty response
        $mock = new MockHandler([new Response(200, [], '{}')]);
        $stack = HandlerStack::create($mock);

        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $stack;
    }

    /**
     * @test
     *
     * @return void
     */
    public function itProvidesKeycloakProvider()
    {
        $res = KeycloakUtility::getProvider();

        self::assertInstanceOf(KeycloakProvider::class, $res);
    }
}