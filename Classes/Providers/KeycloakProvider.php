<?php

declare(strict_types=1);

namespace Netlogix\Nxkeycloak\Providers;

use ErrorException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use UnexpectedValueException;

/**
 * This class extends the generic OAuth2 provider and adds specific details for Keycloak
 */
class KeycloakProvider extends GenericProvider
{
    private array $openidConfiguration = [];

    public function __construct(array $options = [], array $collaborators = [])
    {
        if (empty($options['host']) || empty($options['realm'])) {
            throw new \Exception(
                'Keycloak needs a host and a realm to be set in order to fetch configuration',
                1652253239
            );
        }

        $this->loadConfiguration($options['host'], $options['realm']);

        // set needed URLs from configuration
        $options['urlAuthorize'] = $this->openidConfiguration['authorization_endpoint'];
        $options['urlAccessToken'] = $this->openidConfiguration['token_endpoint'];
        $options['urlResourceOwnerDetails'] = $this->openidConfiguration['userinfo_endpoint'];
        // tell GenericResourceOwner which property holds the user's ID
        $options['responseResourceOwnerId'] = 'sub';
        // all we need is generic profile information to create a user on our side
        $options['scopes'] = ['profile'];

        // use the default TYPO3 HTTP client implementation for all requests
        if (empty($collaborators['httpClient'])) {
            $collaborators['httpClient'] = GuzzleClientFactory::getClient();
        }

        parent::__construct($options, $collaborators);
    }

    /**
     * Fetch configuration from Keycloak
     *
     * This will make a request to Keycloak and fetch publicly available information and configuration.
     * This way we get all the needed URLs without needing to create them.
     *
     * @param string $host
     * @param string $realm
     * @return void
     */
    private function loadConfiguration(string $host, string $realm): void
    {
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('nxkeycloak_config');
        $identifier = md5($host . '_' . $realm);

        if (!$cache->has($identifier)) {
            $res = GeneralUtility::getUrl(sprintf('%s/realms/%s/.well-known/openid-configuration', $host, $realm));

            if ($res === false) {
                throw new ErrorException('unable to load Keycloak configuration', 1652077985);
            }

            $openidConfiguration = json_decode($res, true) ?? [];

            if (json_last_error() > 0) {
                throw new ErrorException('unable to parse Keycloak configuration', 1652078081);
            }

            $cache->set($identifier, $openidConfiguration);
        }

        $this->openidConfiguration = $cache->get($identifier);
    }

    /**
     * Requests resource owner details.
     *
     * This overrides the base function because Keycloak requires POST instead of GET when fetching user details.
     *
     * @param AccessToken $token
     * @return mixed
     */
    protected function fetchResourceOwnerDetails(AccessToken $token)
    {
        $url = $this->getResourceOwnerDetailsUrl($token);

        $request = $this->getAuthenticatedRequest(self::METHOD_POST, $url, $token);

        $response = $this->getParsedResponse($request);

        if (false === is_array($response)) {
            throw new UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected JSON.'
            );
        }

        return $response;
    }
}
