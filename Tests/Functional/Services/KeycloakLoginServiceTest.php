<?php

declare(strict_types=1);

namespace Netlogix\Nxkeycloak\Tests\Functional\Services;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Netlogix\Nxkeycloak\Events\AuthenticateUserEvent;
use Netlogix\Nxkeycloak\Events\BeforeCreateUserEvent;
use Netlogix\Nxkeycloak\Services\KeycloakLoginService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class KeycloakLoginServiceTest extends FunctionalTestCase
{

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

    protected $testExtensionsToLoad = ['typo3conf/ext/nxkeycloak'];

    public function tearDown(): void
    {
        parent::tearDown();

        $_GET = [];
    }

    /**
     * @test
     * @return void
     */
    public function itDoesNothingIfNoLoginIsRequested()
    {
        $subject = new KeycloakLoginService(new EventDispatcher());

        self::assertNull($subject->getUser());
    }

    /**
     * @test
     * @return void
     */
    public function itFindsUserByIdentifier()
    {
        // KeycloakProvider will fetch config from Keycloak server. Mock an empty response
        $mock = new MockHandler(
            [
                // fetching config from Keycloak server
                new Response(
                    200,
                    [],
                    '{"token_endpoint": "https://www.example.com/foo", "userinfo_endpoint": "https://www.example.com/bar"}'
                ),
                // fetching access token
                new Response(200, [], '{"access_token": "dummy"}'),
                // fetching user details
                new Response(200, [], '{"sub": "known_identifier_from_keycloak"}'),
            ]
        );
        $stack = HandlerStack::create($mock);

        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $stack;

        $subject = new KeycloakLoginService(new EventDispatcher());
        $subject->initAuth(
            '',
            ['status' => 'login'],
            ['db_user' => ['table' => 'be_users']],
            new BackendUserAuthentication()
        );

        $user = $subject->getUser();

        self::assertNotNull($user);
        self::assertEquals(1652858852, $user['uid']);
    }

    /**
     * @test
     * @return void
     */
    public function itFindsUserByEmail()
    {
        // KeycloakProvider will fetch config from Keycloak server. Mock an empty response
        $mock = new MockHandler(
            [
                // fetching config from Keycloak server
                new Response(
                    200,
                    [],
                    '{"token_endpoint": "https://www.example.com/foo", "userinfo_endpoint": "https://www.example.com/bar"}'
                ),
                // fetching access token
                new Response(200, [], '{"access_token": "dummy"}'),
                // fetching user details
                new Response(200, [], '{"sub": "' . uniqid() . '", "email": "local_email@email.test"}'),
            ]
        );
        $stack = HandlerStack::create($mock);

        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $stack;

        $subject = new KeycloakLoginService(new EventDispatcher());
        $subject->initAuth(
            '',
            ['status' => 'login'],
            ['db_user' => ['table' => 'be_users']],
            new BackendUserAuthentication()
        );

        $user = $subject->getUser();

        self::assertNotNull($user);
        self::assertEquals(1652858853, $user['uid']);
    }

    /**
     * @test
     * @return void
     */
    public function itCreatesUserWithKeycloakUserDetails()
    {
        $keycloakID = uniqid();
        $keycloakEmail = uniqid() . 'email.test';

        // KeycloakProvider will fetch config from Keycloak server. Mock an empty response
        $mock = new MockHandler(
            [
                // fetching config from Keycloak server
                new Response(
                    200,
                    [],
                    '{"token_endpoint": "https://www.example.com/foo", "userinfo_endpoint": "https://www.example.com/bar"}'
                ),
                // fetching access token
                new Response(200, [], '{"access_token": "dummy"}'),
                // fetching user details
                new Response(200, [], '{"sub": "' . $keycloakID . '", "email": "' . $keycloakEmail . '"}'),
            ]
        );
        $stack = HandlerStack::create($mock);

        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $stack;

        $subject = new KeycloakLoginService(new EventDispatcher());
        $subject->initAuth(
            '',
            ['status' => 'login'],
            ['db_user' => ['table' => 'be_users']],
            new BackendUserAuthentication()
        );

        $user = $subject->getUser();

        self::assertNotNull($user);
        self::assertEquals($keycloakID, $user['tx_nxkeycloak_identifier']);
        self::assertEquals($keycloakEmail, $user['email']);
    }

    /**
     * @test
     * @return void
     */
    public function itFiresEventBeforeCreatingUser()
    {
        // KeycloakProvider will fetch config from Keycloak server. Mock an empty response
        $mock = new MockHandler(
            [
                // fetching config from Keycloak server
                new Response(
                    200,
                    [],
                    '{"token_endpoint": "https://www.example.com/foo", "userinfo_endpoint": "https://www.example.com/bar"}'
                ),
                // fetching access token
                new Response(200, [], '{"access_token": "dummy"}'),
                // fetching user details
                new Response(200, [], '{"sub": "' . uniqid() . '", "email": "' . uniqid() . 'email.test"}'),
            ]
        );
        $stack = HandlerStack::create($mock);

        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $stack;

        $dispatcherMock = $this->getMockBuilder(EventDispatcher::class)->getMock();
        $dispatcherMock->expects(self::once())->method('dispatch')->willReturnArgument(0);

        $subject = new KeycloakLoginService($dispatcherMock);
        $subject->initAuth(
            '',
            ['status' => 'login'],
            ['db_user' => ['table' => 'be_users']],
            new BackendUserAuthentication()
        );

        $user = $subject->getUser();

        self::assertNotNull($user);
    }

    /**
     * @test
     * @return void
     */
    public function allowsManipulationNewUserWithEvent()
    {
        $eventUser = [
            'admin' => 0,
            'usergroup' => '1,2,3'
        ];

        // KeycloakProvider will fetch config from Keycloak server. Mock an empty response
        $mock = new MockHandler(
            [
                // fetching config from Keycloak server
                new Response(
                    200,
                    [],
                    '{"token_endpoint": "https://www.example.com/foo", "userinfo_endpoint": "https://www.example.com/bar"}'
                ),
                // fetching access token
                new Response(200, [], '{"access_token": "dummy"}'),
                // fetching user details
                new Response(200, [], '{"sub": "' . uniqid() . '", "email": "' . uniqid() . 'email.test"}'),
            ]
        );
        $stack = HandlerStack::create($mock);

        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $stack;

        $dispatcherMock = $this->getMockBuilder(EventDispatcher::class)->getMock();
        $dispatcherMock->expects(self::once())->method('dispatch')
            ->willReturnCallback(function () use ($eventUser) {
                /** @var BeforeCreateUserEvent $event */
                $event = func_get_arg(0);
                $user = $event->getUser();
                $user['admin'] = $eventUser['admin'];
                $user['usergroup'] = $eventUser['usergroup'];
                $event->setUser($user);

                return $event;
            });

        $subject = new KeycloakLoginService($dispatcherMock);
        $subject->initAuth(
            '',
            ['status' => 'login'],
            ['db_user' => ['table' => 'be_users']],
            new BackendUserAuthentication()
        );

        $user = $subject->getUser();

        self::assertNotNull($user);
        self::assertEquals($eventUser['admin'], $user['admin']);
        self::assertEquals($eventUser['usergroup'], $user['usergroup']);
    }

    /**
     * @test
     * @return void
     */
    public function itWillContinueAuthChainForNonKeycloakUser()
    {
        $subject = new KeycloakLoginService(new EventDispatcher());
        $res = $subject->authUser(['uid' => rand(100, 1000), 'tx_nxkeycloak_identifier' => '']);

        self::assertTrue((100 <= $res && $res < 200));
    }

    /**
     * @test
     * @return void
     */
    public function itWillContinueAuthChainForKeycloakUserNotFoundByExtensionIfLocalLoginIsEnabled()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['nxkeycloak']['disableLocalLogin'] = 0;

        $subject = new KeycloakLoginService(new EventDispatcher());
        // the property `fetched_by_keycloak` is not set
        $res = $subject->authUser(['uid' => rand(100, 1000), 'tx_nxkeycloak_identifier' => uniqid()]);

        self::assertTrue((100 <= $res && $res < 200));
    }

    /**
     * @test
     * @return void
     */
    public function itPreventsAuthForKeycloakUserNotFoundByExtensionIfLocalLoginIsDisabled()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['nxkeycloak']['disableLocalLogin'] = 1;

        $subject = new KeycloakLoginService(new EventDispatcher());
        // the property `fetched_by_keycloak` is not set
        $res = $subject->authUser(['uid' => rand(100, 1000), 'tx_nxkeycloak_identifier' => uniqid()]);

        self::assertTrue($res < 0);
    }

    /**
     * @test
     * @return void
     */
    public function itAuthenticatesKnownUserFromKeycloak()
    {
        $keycloakID = 'known_identifier_from_keycloak';

        // KeycloakProvider will fetch config from Keycloak server. Mock an empty response
        $mock = new MockHandler(
            [
                // fetching config from Keycloak server
                new Response(
                    200,
                    [],
                    '{"token_endpoint": "https://www.example.com/foo", "userinfo_endpoint": "https://www.example.com/bar"}'
                ),
                // fetching access token
                new Response(200, [], '{"access_token": "dummy"}'),
                // fetching user details
                new Response(200, [], '{"sub": "' . $keycloakID . '"}'),
                // fetching user details again for auth
                new Response(200, [], '{"sub": "' . $keycloakID . '"}'),
            ]
        );
        $stack = HandlerStack::create($mock);

        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $stack;

        $subject = new KeycloakLoginService(new EventDispatcher());

        $subject->initAuth(
            '',
            ['status' => 'login'],
            ['db_user' => ['table' => 'be_users']],
            new BackendUserAuthentication()
        );
        $user = $subject->getUser();

        $res = $subject->authUser($user);

        self::assertTrue($res >= 200 && $res < 300);
    }

    /**
     * @test
     * @return void
     */
    public function itContinuesAuthChainForDifferentKeycloakUser()
    {
        $keycloakID = 'known_identifier_from_keycloak';

        // KeycloakProvider will fetch config from Keycloak server. Mock an empty response
        $mock = new MockHandler(
            [
                // fetching config from Keycloak server
                new Response(
                    200,
                    [],
                    '{"token_endpoint": "https://www.example.com/foo", "userinfo_endpoint": "https://www.example.com/bar"}'
                ),
                // fetching access token
                new Response(200, [], '{"access_token": "dummy"}'),
                // fetching user details for a known user
                new Response(200, [], '{"sub": "' . $keycloakID . '"}'),
                // but try to authenticate another one
                new Response(200, [], '{"sub": "' . uniqid() . '"}'),
            ]
        );
        $stack = HandlerStack::create($mock);

        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $stack;

        $subject = new KeycloakLoginService(new EventDispatcher());

        $subject->initAuth(
            '',
            ['status' => 'login'],
            ['db_user' => ['table' => 'be_users']],
            new BackendUserAuthentication()
        );
        $user = $subject->getUser();

        $res = $subject->authUser($user);

        self::assertTrue($res >= 100 && $res < 200);
    }

    /**
     * @test
     * @return void
     */
    public function itFiresEventBeforeAuthenticatingAUser()
    {
        $keycloakID = 'known_identifier_from_keycloak';

        // KeycloakProvider will fetch config from Keycloak server. Mock an empty response
        $mock = new MockHandler(
            [
                // fetching config from Keycloak server
                new Response(
                    200,
                    [],
                    '{"token_endpoint": "https://www.example.com/foo", "userinfo_endpoint": "https://www.example.com/bar"}'
                ),
                // fetching access token
                new Response(200, [], '{"access_token": "dummy"}'),
                // fetching user details
                new Response(200, [], '{"sub": "' . $keycloakID . '"}'),
                // fetching user details again for auth
                new Response(200, [], '{"sub": "' . $keycloakID . '"}'),
            ]
        );
        $stack = HandlerStack::create($mock);

        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $stack;

        $dispatcherMock = $this->getMockBuilder(EventDispatcher::class)->getMock();
        $dispatcherMock->expects(self::once())->method('dispatch')->willReturnArgument(0);

        $subject = new KeycloakLoginService($dispatcherMock);

        $subject->initAuth(
            '',
            ['status' => 'login'],
            ['db_user' => ['table' => 'be_users']],
            new BackendUserAuthentication()
        );
        $user = $subject->getUser();

        $subject->authUser($user);
    }

    /**
     * @test
     * @return void
     */
    public function itAllowsManipulatingAuthenticationResultUsingEvent()
    {
        $eventStatus = rand(1, 99);

        $keycloakID = 'known_identifier_from_keycloak';

        // KeycloakProvider will fetch config from Keycloak server. Mock an empty response
        $mock = new MockHandler(
            [
                // fetching config from Keycloak server
                new Response(
                    200,
                    [],
                    '{"token_endpoint": "https://www.example.com/foo", "userinfo_endpoint": "https://www.example.com/bar"}'
                ),
                // fetching access token
                new Response(200, [], '{"access_token": "dummy"}'),
                // fetching user details
                new Response(200, [], '{"sub": "' . $keycloakID . '"}'),
                // fetching user details again for auth
                new Response(200, [], '{"sub": "' . $keycloakID . '"}'),
            ]
        );
        $stack = HandlerStack::create($mock);

        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $stack;

        $dispatcherMock = $this->getMockBuilder(EventDispatcher::class)->getMock();
        $dispatcherMock->expects(self::once())->method('dispatch')
            ->willReturnCallback(function () use ($eventStatus) {
                /** @var AuthenticateUserEvent $event */
                $event = func_get_arg(0);
                $event->setStatus($eventStatus);

                return $event;
            });

        $subject = new KeycloakLoginService($dispatcherMock);

        $subject->initAuth(
            '',
            ['status' => 'login'],
            ['db_user' => ['table' => 'be_users']],
            new BackendUserAuthentication()
        );
        $user = $subject->getUser();

        $res = $subject->authUser($user);

        self::assertEquals($eventStatus, $res);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(
            GeneralUtility::getFileAbsFileName('EXT:nxkeycloak/Tests/Functional/Fixtures/backendUsers.xml')
        );

        $_GET = ['state' => 'foo', 'code' => 'bar'];
    }
}