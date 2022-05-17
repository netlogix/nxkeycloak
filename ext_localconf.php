<?php
/** @noinspection PhpMissingStrictTypesDeclarationInspection */

/** @noinspection PhpFullyQualifiedNameUsageInspection */

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['nxkeycloak_config'] ??= [
    // use a simple in-memory cache to reuse config data during a single request.
    // communication with Keycloak is done during login only so no persistent data is needed
    'backend' => \TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class
];


$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][1652174812] = [
    'provider' => \Netlogix\Nxkeycloak\LoginProvider\KeycloakLoginProvider::class,
    'sorting' => 70,
    'icon-class' => 'fa-sign-in',
    'label' => 'LLL:EXT:nxkeycloak/Resources/Private/Language/locallang.xlf:loginProvider.label'
];


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    'nxkeycloak',
    'auth',
    \Netlogix\Nxkeycloak\Services\KeycloakLoginService::class,
    [
        'title' => 'Netlogix Authentication',
        'description' => 'Keycloak authentication service for backend users using Keycloak',
        'subtype' => 'getUserBE,authUserBE',
        'available' => true,
        'priority' => 75,
        'quality' => 50,
        'os' => '',
        'exec' => '',
        'className' => \Netlogix\Nxkeycloak\Services\KeycloakLoginService::class
    ]
);

