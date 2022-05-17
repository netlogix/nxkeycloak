<?php

declare(strict_types=1);

namespace Netlogix\Nxkeycloak\Services;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Netlogix\Nxkeycloak\Events\AuthenticateUserEvent;
use Netlogix\Nxkeycloak\Events\BeforeCreateUserEvent;
use Netlogix\Nxkeycloak\Providers\KeycloakProvider;
use Netlogix\Nxkeycloak\Utility\KeycloakUtility;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\AbstractAuthenticationService;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * This login service handles logins via Keycloak.
 *
 * It will redirect to Keycloak login form.
 * It handles incoming redirects from Keycloak and tries to find a user for the provided token.
 *
 * Getting a TYPO3 user is done in one of three ways:
 *  * try to find a TYPO3 user with a matching OAuth ID (set in 'tx_nxkeycloak_identifier')
 *  * try to find a user with a matching email address, then add the OAuth ID to 'tx_nxkeycloak_identifier'
 *  * create a new user with details provided by Keycloak
 */
class KeycloakLoginService extends AbstractAuthenticationService implements SingletonInterface
{

    private ?AccessTokenInterface $currentToken;

    private KeycloakProvider $oauthProvider;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function initAuth($mode, $loginData, $authInfo, $pObj)
    {
        $this->oauthProvider = KeycloakUtility::getProvider();

        parent::initAuth($mode, $loginData, $authInfo, $pObj);
    }

    public function getUser(): ?array
    {
        if ($this->login['status'] !== 'login') {
            return null;
        }

        $action = GeneralUtility::_GP('oauth-action') ?? '';
        $state = GeneralUtility::_GET('state') ?? '';
        $code = GeneralUtility::_GET('code') ?? '';

        if ($action == 'redirect') {
            $authorizationUrl = $this->oauthProvider->getAuthorizationUrl();
            HttpUtility::redirect($authorizationUrl, HttpUtility::HTTP_STATUS_303);
            exit;
        }

        if ($state == '' || $code == '') {
            return null;
        }

        $this->currentToken = $this->oauthProvider->getAccessToken(
            'authorization_code',
            [
                'code' => $code
            ]
        );

        $owner = $this->oauthProvider->getResourceOwner($this->currentToken);

        return $this->resolveUserForOwner($owner);
    }

    /**
     * Try to find a local BE user for the token owner or try to create one.
     *
     * @param ResourceOwnerInterface $owner
     * @return array|null
     */
    private function resolveUserForOwner(ResourceOwnerInterface $owner): ?array
    {
        $user = $this->getUserByResourceOwner($owner);

        if (!$user) {
            $user = $this->getUserByEmailAddress($owner);
        }

        if (!$user) {
            $user = $this->createUserByResourceOwner($owner);
        }

        if ($user) {
            $user['fetched_by_keycloak'] = true;
        }

        return $user;
    }

    /**
     * This function tries to fetch a local TYPO3 BE user using the Keycloak identifier.
     *
     * @param ResourceOwnerInterface $resourceOwner
     * @return void
     */
    private function getUserByResourceOwner(ResourceOwnerInterface $resourceOwner): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->db_user['table']);

        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());

        $record = $queryBuilder
            ->select('*')
            ->from($this->db_user['table'])
            ->where(
                $queryBuilder->expr()->eq(
                    'tx_nxkeycloak_identifier',
                    $queryBuilder->createNamedParameter(
                        $resourceOwner->getId(),
                        Connection::PARAM_STR
                    )
                ),
                $this->db_user['check_pid_clause'],
                $this->db_user['enable_clause']
            )
            ->execute()
            ->fetchAssociative();

        return $record ?: null;
    }

    /**
     * Try to find a user with a matching email address.
     * If a user is found then add Keycloak identifier to it for easier matching in further requests.
     *
     * @param ResourceOwnerInterface $resourceOwner
     * @return array|null
     */
    private function getUserByEmailAddress(ResourceOwnerInterface $resourceOwner): ?array
    {
        if (empty($resourceOwner->toArray()['email'])) {
            return null;
        }


        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->db_user['table']);

        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());

        $record = $queryBuilder
            ->select('*')
            ->from($this->db_user['table'])
            ->where(
                $queryBuilder->expr()->eq(
                    'email',
                    $queryBuilder->createNamedParameter(
                        $resourceOwner->toArray()['email'],
                        Connection::PARAM_STR
                    )
                ),
                $this->db_user['check_pid_clause'],
                $this->db_user['enable_clause']
            )
            ->execute()
            ->fetchAssociative();

        if (!$record) {
            return null;
        }

        $queryBuilder
            ->update($this->db_user['table'])
            ->set('tx_nxkeycloak_identifier', $resourceOwner->getId())
            ->execute();

        $record['tx_nxkeycloak_identifier'] = $resourceOwner->getId();

        return $record;
    }

    /**
     * Create a new TYPO3 BE user for this owner and return its record
     *
     * @param ResourceOwnerInterface $resourceOwner
     * @return array|null
     */
    private function createUserByResourceOwner(ResourceOwnerInterface $resourceOwner): ?array
    {
        $conn = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->db_user['table']);

        $saltingInstance = GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('BE');

        $record = [
            'crdate' => $GLOBALS['EXEC_TIME'],
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'admin' => KeycloakUtility::createUserAsAdmin() ? 1 : 0,
            'disable' => 0,
            'starttime' => 0,
            'endtime' => 0,
            'tx_nxkeycloak_identifier' => $resourceOwner->getId(),
            'password' => $saltingInstance->getHashedPassword(md5(uniqid())),
            'email' => $resourceOwner->toArray()['email'] ?? '',
            'realname' => $resourceOwner->toArray()['name'] ?? '',
            'username' => $resourceOwner->toArray()['preferred_username'] ?? $resourceOwner->getId(),
        ];

        /** @var BeforeCreateUserEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new BeforeCreateUserEvent($record, $resourceOwner)
        );

        $conn->insert($this->db_user['table'], $event->getUser());

        $id = $conn->lastInsertId($this->db_user['table']);

        return BackendUtility::getRecord($this->db_user['table'], $id);
    }


    /**
     * check if the userRecord matches the token's owner ID.
     * If it does then the user can be authenticated.
     *
     * @param array $userRecord
     * @return int
     */
    public function authUser(array $userRecord): int
    {
        // this is not a Keycloak-enabled user
        if (empty($userRecord['tx_nxkeycloak_identifier'])) {
            return 100;
        }

        // this user is keycloak enabled but tried to log-in using another method
        if (!$userRecord['fetched_by_keycloak']) {
            if (KeycloakUtility::isLocalLoginDisabled()) {
                // local login for Keycloak user has been disabled. Reject this login.
                return -1;
            } else {
                // we do not care about this user here and just let TYPO3 continue
                return 100;
            }
        }

        $owner = $this->oauthProvider->getResourceOwner($this->currentToken);

        if ($userRecord['tx_nxkeycloak_identifier'] == $owner->getId()) {
            /** @var AuthenticateUserEvent $event */
            $event = $this->eventDispatcher->dispatch(
                new AuthenticateUserEvent($userRecord, $owner, 200)
            );

            return $event->getStatus();
        }

        // let some other service make a decision
        return 100;
    }
}
