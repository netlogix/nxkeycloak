<?php

declare(strict_types=1);

namespace Netlogix\Nxkeycloak\Events;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

final class AuthenticateUserEvent {
    private array $user;
    private ResourceOwnerInterface $resourceOwner;
    private int $status;

    public function __construct(array $user, ResourceOwnerInterface $resourceOwner, int $status) {
        $this->user = $user;
        $this->resourceOwner = $resourceOwner;
        $this->status = $status;
    }

    /**
     * @return array
     */
    public function getUser(): array
    {
        return $this->user;
    }

    /**
     * @return ResourceOwnerInterface
     */
    public function getResourceOwner(): ResourceOwnerInterface
    {
        return $this->resourceOwner;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return AuthenticateUserEvent
     */
    public function setStatus(int $status): AuthenticateUserEvent
    {
        $this->status = $status;
        return $this;
    }

}
