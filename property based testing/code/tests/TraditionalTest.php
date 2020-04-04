<?php
declare(strict_types = 1);

namespace Tests\PBT;

use PBT\{
    Citizen,
    DeliverDriverLicense,
};
use Innmind\TimeContinuum\Earth\{
    FrozenClock,
    PointInTime\PointInTime,
};
use PHPUnit\Framework\TestCase;

class CitizenTest extends TestCase
{
    public function testAge()
    {
        $clock = new FrozenClock(new PointInTime('2020-05-15 11:30:00'));

        $citizen = new Citizen(
            'John',
            'Doe',
            $clock->at('2004-05-14 03:00:00'),
            'Somewhere',
        );
        $this->assertSame(15, $citizen->age($clock));

        $citizen = new Citizen(
            'John',
            'Doe',
            $clock->at('2000-05-16 03:00:00'),
            'Somewhere',
        );
        $this->assertSame(20, $citizen->age($clock));
    }

    public function testIsAdult()
    {
        $clock = new FrozenClock(new PointInTime('2020-05-15 11:30:00'));

        $citizen = new Citizen(
            'John',
            'Doe',
            $clock->at('2004-05-14 03:00:00'),
            'Somewhere',
        );
        $this->assertFalse($citizen->isAdult($clock));

        $citizen = new Citizen(
            'John',
            'Doe',
            $clock->at('2000-05-16 03:00:00'),
            'Somewhere',
        );
        $this->assertTrue($citizen->isAdult($clock));
    }

    public function testEmancipatedCitizenIsConsideredAnAdult()
    {
        $clock = new FrozenClock(new PointInTime('2020-05-15 11:30:00'));

        $citizen = new Citizen(
            'John',
            'Doe',
            $clock->at('2004-05-14 03:00:00'),
            'Somewhere',
        );
        $citizen->emancipate();

        $this->assertTrue($citizen->isAdult($clock));
    }

    public function testANonAdultCantObtainADriverLicense()
    {
        $clock = new FrozenClock(new PointInTime('2020-05-15 11:30:00'));
        $deliver = new DeliverDriverLicense($clock);

        $citizen = new Citizen(
            'John',
            'Doe',
            $clock->at('2004-05-14 03:00:00'),
            'Somewhere',
        );

        try {
            $citizen->obtainDriverLicense($clock, $deliver);
            $this->fail('it should throw an exception');
        } catch (\LogicException $e) {
            $this->assertFalse($citizen->hasDriverLicense());
        }
    }

    public function testAnAdultCanObtainADriverLicense()
    {
        $clock = new FrozenClock(new PointInTime('2020-05-15 11:30:00'));
        $deliver = new DeliverDriverLicense($clock);

        $citizen = new Citizen(
            'John',
            'Doe',
            $clock->at('2000-05-16 03:00:00'),
            'Somewhere',
        );

        $this->assertFalse($citizen->hasDriverLicense());
        $this->assertNull($citizen->obtainDriverLicense($clock, $deliver));
        $this->assertTrue($citizen->hasDriverLicense());
    }
}
