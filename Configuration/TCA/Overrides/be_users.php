<?php

declare(strict_types=1);

use Netlogix\Nxkeycloak\Utility\KeycloakUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;


ExtensionManagementUtility::addTCAcolumns(
    'be_users',
    [
        // this field will not be displayed if it is empty
        'tx_nxkeycloak_identifier' => [
            'label' => 'LLL:EXT:nxkeycloak/Resources/Private/Language/locallang_tca.xlf:be_users.tx_nxkeycloak_identifier',
            'displayCond' => 'FIELD:tx_nxkeycloak_identifier:REQ:true',
            'config' => [
                'type' => 'input',
                'readOnly' => true
            ]
        ]
    ]

);
ExtensionManagementUtility::addToAllTCAtypes('be_users', 'tx_nxkeycloak_identifier', '', 'after:admin');

if (KeycloakUtility::isLocalLoginDisabled()) {
    $GLOBALS['TCA']['be_users']['columns']['username']['displayCond'] = 'FIELD:tx_nxkeycloak_identifier:REQ:false';
    $GLOBALS['TCA']['be_users']['columns']['password']['displayCond'] = 'FIELD:tx_nxkeycloak_identifier:REQ:false';
    $GLOBALS['TCA']['be_users']['columns']['mfa']['displayCond'] = 'FIELD:tx_nxkeycloak_identifier:REQ:false';
}

