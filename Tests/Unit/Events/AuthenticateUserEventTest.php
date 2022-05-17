<?php

declare(strict_types=1);

namespace Netlogix\Nxkeycloak\Tests\Unit\Events;

use League\OAuth2\Client\Provider\GenericResourceOwner;
use Netlogix\Nxkeycloak\Events\AuthenticateUserEvent;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AuthenticateUserEventTest extends UnitTestCase
{

    /**
     * @test
     *
     * @return void
     */
    public function itExposesUserData()
    {
        $dummyUser = [
            'name' => uniqid()
        ];

        $subject = new AuthenticateUserEvent($dummyUser, new GenericResourceOwner([], ''), 0);

        self::assertEquals($dummyUser['name'], $subject->getUser()['name'] ?? '');
    }

    /**
     * @test
     *
     * @return void
     */
    public function itExposesLoginStatus()
    {
        $status = rand(1, 100);

        $subject = new AuthenticateUserEvent([], new GenericResourceOwner([], ''), $status);

        self::assertEquals($status, $subject->getStatus());
    }

    /**
     * @test
     *
     * @return void
     */
    public function itAllowsManipulationOfLoginStatus()
    {
        $status = rand(1, 100);

        $subject = new AuthenticateUserEvent([], new GenericResourceOwner([], ''), rand(1, 100));
        $subject->setStatus($status);

        self::assertEquals($status, $subject->getStatus());
    }


    /**
     * @test
     *
     * @return void
     */
    public function itExposesResourceOwner()
    {
        $id = uniqid();

        $subject = new AuthenticateUserEvent([], new GenericResourceOwner(['id' => $id], 'id'), 0);

        self::assertEquals($id, $subject->getResourceOwner()->getId());
    }
}