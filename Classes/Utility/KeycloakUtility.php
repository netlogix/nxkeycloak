<?php

declare(strict_types=1);

namespace Netlogix\Nxkeycloak\Utility;

use Netlogix\Nxkeycloak\Events\ConfigurationLoadedEvent;
use Netlogix\Nxkeycloak\Providers\KeycloakProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This utility class is a collection of helper functions
 */
class KeycloakUtility
{

    /**
     * Create a Keycloak provider
     *
     * @return KeycloakProvider
     */
    public static function getProvider(): KeycloakProvider
    {
        static $oauthProvider = null;

        if ($oauthProvider == null) {
            $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);

            $config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('nxkeycloak');


            /** @var ConfigurationLoadedEvent $event */
            $event = $eventDispatcher->dispatch(
                new ConfigurationLoadedEvent(
                    $config['host'],
                    $config['realm'],
                    $config['clientId'] ?: getenv('NXKEYCLOAK_CLIENTID') ?: '',
                    $config['clientSecret'] ?: getenv('NXKEYCLOAK_CLIENTSECRET') ?: ''
                )
            );

            $oauthProvider = new KeycloakProvider(
                [
                    'clientId' => $event->getClientId(),
                    'clientSecret' => $event->getClientSecret(),
                    'redirectUri' => GeneralUtility::locationHeaderUrl(
                        '/typo3/index.php?loginProvider=1529672977&login_status=login'
                    ),
                    'host' => $event->getHost(),
                    'realm' => $event->getRealm(),
                ]
            );
        }


        return $oauthProvider;
    }

    /**
     * check if local login for Keycloak enabled users is enabled
     *
     * @return bool
     */
    public static function isLocalLoginDisabled(): bool
    {
        $config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('nxkeycloak');

        return (bool)$config['disableLocalLogin'];
    }

    public static function createUserAsAdmin(): bool
    {
        $config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('nxkeycloak');

        return (bool)$config['createAdmin'];
    }
}