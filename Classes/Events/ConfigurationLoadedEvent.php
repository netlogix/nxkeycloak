<?php

declare(strict_types=1);

namespace Netlogix\Nxkeycloak\Events;

final class ConfigurationLoadedEvent
{
    private string $host;
    private string $realm;
    private string $clientId;
    private string $clientSecret;

    public function __construct(string $host, string $realm, string $clientId, string $clientSecret)
    {
        $this->host = $host;
        $this->realm = $realm;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    /**
     * @param string $host
     * @return ConfigurationLoadedEvent
     */
    public function setHost(string $host): ConfigurationLoadedEvent
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param string $realm
     * @return ConfigurationLoadedEvent
     */
    public function setRealm(string $realm): ConfigurationLoadedEvent
    {
        $this->realm = $realm;
        return $this;
    }

    /**
     * @param string $clientId
     * @return ConfigurationLoadedEvent
     */
    public function setClientId(string $clientId): ConfigurationLoadedEvent
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * @param string $clientSecret
     * @return ConfigurationLoadedEvent
     */
    public function setClientSecret(string $clientSecret): ConfigurationLoadedEvent
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getRealm(): string
    {
        return $this->realm;
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

}
