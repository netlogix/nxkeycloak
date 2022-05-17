<?php

declare(strict_types=1);

namespace Netlogix\Nxkeycloak\Tests\Unit\Events;

use Netlogix\Nxkeycloak\Events\ConfigurationLoadedEvent;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class ConfigurationLoadedEventTest extends UnitTestCase
{

    /**
     * @test
     * @return void
     */
    public function itExposesHost()
    {
        $host = 'https://www.example.com/' . uniqid();

        $subject = new ConfigurationLoadedEvent($host, '', '', '');

        self::assertEquals($host, $subject->getHost());
    }

    /**
     * @test
     * @return void
     */
    public function itAllowsManipulationOfHost()
    {
        $host = 'https://www.example.com/' . uniqid();

        $subject = new ConfigurationLoadedEvent('', '', '', '');
        $subject->setHost($host);

        self::assertEquals($host, $subject->getHost());
    }

    /**
     * @test
     * @return void
     */
    public function itExposesRealm()
    {
        $realm = uniqid();

        $subject = new ConfigurationLoadedEvent('', $realm, '', '');

        self::assertEquals($realm, $subject->getRealm());
    }

    /**
     * @test
     * @return void
     */
    public function itAllowsManipulationOfRealm()
    {
        $realm = uniqid();

        $subject = new ConfigurationLoadedEvent('', '', '', '');
        $subject->setRealm($realm);

        self::assertEquals($realm, $subject->getRealm());
    }

    /**
     * @test
     * @return void
     */
    public function itExposesClientID()
    {
        $clientID = uniqid();

        $subject = new ConfigurationLoadedEvent('', '', $clientID, '');

        self::assertEquals($clientID, $subject->getClientId());
    }

    /**
     * @test
     * @return void
     */
    public function itAllowsManipulationOfClientID()
    {
        $clientID = uniqid();

        $subject = new ConfigurationLoadedEvent('', '', '', '');
        $subject->setClientId($clientID);

        self::assertEquals($clientID, $subject->getClientId());
    }

    /**
     * @test
     * @return void
     */
    public function itExposesClientSecret()
    {
        $clientSecret = uniqid();

        $subject = new ConfigurationLoadedEvent('', '', '', $clientSecret);

        self::assertEquals($clientSecret, $subject->getClientSecret());
    }

    /**
     * @test
     * @return void
     */
    public function itAllowsManipulationOfClientSecret()
    {
        $clientSecret = uniqid();

        $subject = new ConfigurationLoadedEvent('', '', '', '');
        $subject->setClientSecret($clientSecret);

        self::assertEquals($clientSecret, $subject->getClientSecret());
    }
}