<?php

declare(strict_types=1);

namespace Netlogix\Nxkeycloak\Tests\Unit\Events;

use League\OAuth2\Client\Provider\GenericResourceOwner;
use Netlogix\Nxkeycloak\Events\BeforeCreateUserEvent;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class BeforeCreateUserEventTest extends UnitTestCase
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

        $subject = new BeforeCreateUserEvent($dummyUser, new GenericResourceOwner([], ''));

        self::assertEquals($dummyUser['name'], $subject->getUser()['name'] ?? '');
    }

    /**
     * @test
     *
     * @return void
     */
    public function itAllowsManipulationOfUserData()
    {
        $dummyUser = [
            'name' => uniqid()
        ];

        $subject = new BeforeCreateUserEvent([], new GenericResourceOwner([], ''));
        $subject->setUser($dummyUser);

        self::assertEquals($dummyUser['name'], $subject->getUser()['name'] ?? '');
    }

    /**
     * @test
     *
     * @return void
     */
    public function itExposesResourceOwner()
    {
        $id = uniqid();

        $subject = new BeforeCreateUserEvent([], new GenericResourceOwner(['id' => $id], 'id'));

        self::assertEquals($id, $subject->getResourceOwner()->getId());
    }
}
