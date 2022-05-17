<?php

declare(strict_types=1);

namespace Netlogix\Nxkeycloak\Events;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

final class BeforeCreateUserEvent {
    private array $user;
    private ResourceOwnerInterface $resourceOwner;

    public function __construct(array $user, ResourceOwnerInterface $resourceOwner) {
        $this->user = $user;
        $this->resourceOwner = $resourceOwner;
    }

    /**
     * @return array
     */
    public function getUser(): array
    {
        return $this->user;
    }

    /**
     * @param array $user
     */
    public function setUser(array $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return ResourceOwnerInterface
     */
    public function getResourceOwner(): ResourceOwnerInterface
    {
        return $this->resourceOwner;
    }

}
