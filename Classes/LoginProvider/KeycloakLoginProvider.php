<?php

declare(strict_types=1);

namespace Netlogix\Nxkeycloak\LoginProvider;

use Netlogix\Nxkeycloak\Utility\KeycloakUtility;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface;
use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * This class is responsible for rendering the login form
 */
class KeycloakLoginProvider implements LoginProviderInterface, SingletonInterface
{
    private UriBuilder $uriBuilder;

    public function __construct(UriBuilder $uriBuilder = null)
    {
        $this->uriBuilder = $uriBuilder;
    }

    public function render(StandaloneView $view, PageRenderer $pageRenderer, LoginController $loginController)
    {
        $action = $loginController->getCurrentRequest()->getQueryParams()['keycloak_action'] ?? '';

        if ($action == 'redirect') {
            $authorizationUrl = KeycloakUtility::getProvider()->getAuthorizationUrl();
            $resp = new RedirectResponse($authorizationUrl, 303);
            throw new ImmediateResponseException($resp, 1652353729);
        }


        $view->setTemplatePathAndFilename('EXT:nxkeycloak/Resources/Private/Templates/KeycloakLogin.html');

        // the form will link to this controller and trigger a redirect to Keycloak
        $formActionUrl = $this->uriBuilder->buildUriWithRedirect(
            'login',
            [
                'loginProvider' => $loginController->getLoginProviderIdentifier(),
                'keycloak_action' => 'redirect'
            ],
            RouteRedirect::createFromRequest($loginController->getCurrentRequest())
        );

        $view->assign('formActionUrl', $formActionUrl);
    }
}