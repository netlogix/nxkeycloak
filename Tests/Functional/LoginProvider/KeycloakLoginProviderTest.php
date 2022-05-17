<?php

declare(strict_types=1);

namespace Netlogix\Nxkeycloak\Tests\Functional\LoginProvider;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Netlogix\Nxkeycloak\LoginProvider\KeycloakLoginProvider;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Fluid\View\StandaloneView;

class KeycloakLoginProviderTest extends FunctionalTestCase
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
    private KeycloakLoginProvider $subject;

    public function setUp(): void
    {
        parent::setUp();

        $router = new Router();
        $router->addRoute('login', new Route('/login', []));
        $this->subject = new KeycloakLoginProvider(new UriBuilder($router));
    }

    /**
     * @test
     * @return void
     */
    public function itRedirectsToKeycloakLoginPage()
    {
        $subject = new KeycloakLoginProvider(new UriBuilder(new Router()));

        $authEndpoint = 'https://www.example.com/' . uniqid();

        $mock = new MockHandler([new Response(200, [], '{"authorization_endpoint": "' . $authEndpoint . '"}')]);
        $stack = HandlerStack::create($mock);

        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $stack;


        $loginController = $this
            ->getMockBuilder(LoginController::class)->disableOriginalConstructor()->getMock();

        $loginController->expects(self::any())->method('getCurrentRequest')->willReturn(
            (new ServerRequest('GET', 'https://www.example.com/foo'))->withQueryParams(['keycloak_action' => 'redirect']
            )
        );

        try {
            $subject->render(new StandaloneView(), new PageRenderer(), $loginController);
        } catch (ImmediateResponseException $e) {
            self::assertTrue($e->getResponse()->hasHeader('location'));
            self::assertStringStartsWith($authEndpoint, $e->getResponse()->getHeader('location')[0]);

            return;
        }

        self::fail('expected ImmediateResponseException');
    }

    /**
     * @test
     * @return void
     */
    public function itSetsTemplate()
    {
        $loginController = $this
            ->getMockBuilder(LoginController::class)->disableOriginalConstructor()->getMock();

        $loginController->expects(self::any())->method('getCurrentRequest')->willReturn(
            new ServerRequest('GET', 'https://www.example.com/foo')
        );

        $view = new StandaloneView();

        $this->subject->render($view, new PageRenderer(), $loginController);

        // template paths are absolute
        self::assertStringContainsString(
            'nxkeycloak/Resources/Private/Templates/KeycloakLogin.html',
            $view->getTemplatePathAndFilename()
        );
    }

    /**
     * @test
     * @return void
     */
    public function itSetsFormActionUrl()
    {
        $loginController = $this
            ->getMockBuilder(LoginController::class)->disableOriginalConstructor()->getMock();

        $loginController->expects(self::any())->method('getCurrentRequest')->willReturn(
            new ServerRequest('GET', 'https://www.example.com/foo')
        );

        $view = new StandaloneView();

        $this->subject->render($view, new PageRenderer(), $loginController);

        self::assertTrue($view->getRenderingContext()->getVariableProvider()->exists('formActionUrl'));
        self::assertInstanceOf(Uri::class, $view->getRenderingContext()->getVariableProvider()->get('formActionUrl'));
    }

    /**
     * @test
     * @return void
     */
    public function itContainsRedirectParameterInFormActionUrl()
    {
        $loginController = $this
            ->getMockBuilder(LoginController::class)->disableOriginalConstructor()->getMock();

        $loginController->expects(self::any())->method('getCurrentRequest')->willReturn(
            new ServerRequest('GET', 'https://www.example.com/foo')
        );

        $view = new StandaloneView();

        $this->subject->render($view, new PageRenderer(), $loginController);

        /** @var Uri $url */
        $url = $view->getRenderingContext()->getVariableProvider()->get('formActionUrl');

        self::assertStringContainsString('&keycloak_action=redirect', $url->getQuery());
    }
}