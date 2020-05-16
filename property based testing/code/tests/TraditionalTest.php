<?php
declare(strict_types = 1);

namespace Tests\PBT;

use PBT\{
    Person,
    DeliverDriverLicense,
};
use Innmind\TimeContinuum\Earth\{
    FrozenClock,
    PointInTime\PointInTime,
};
use PHPUnit\Framework\TestCase;

class TraditionalTest extends TestCase
{
    public function testAge()
    {
        $clock = new FrozenClock(new PointInTime('2020-05-15 11:30:00'));

        $person = new Person(
            'John',
            'Doe',
            $clock->at('2004-05-16 03:00:00'),
            'Somewhere',
        );
        $this->assertSame(15, $person->age($clock));

        $person = new Person(
            'John',
            'Doe',
            $clock->at('2000-05-14 03:00:00'),
            'Somewhere',
        );
        $this->assertSame(20, $person->age($clock));
    }

    public function testIsNotAndAdult()
    {
        $clock = new FrozenClock(new PointInTime('2020-05-15 11:30:00'));

        $person = new Person(
            'John',
            'Doe',
            $clock->at('2004-05-14 03:00:00'),
            'Somewhere',
        );
        $this->assertFalse($person->isAdult($clock));
    }

    public function testIsAdult()
    {
        $clock = new FrozenClock(new PointInTime('2020-05-15 11:30:00'));

        $person = new Person(
            'John',
            'Doe',
            $clock->at('2000-05-16 03:00:00'),
            'Somewhere',
        );
        $this->assertTrue($person->isAdult($clock));
    }

    public function testEmancipatedCitizenIsConsideredAnAdult()
    {
        $clock = new FrozenClock(new PointInTime('2020-05-15 11:30:00'));

        $person = new Person(
            'John',
            'Doe',
            $clock->at('2004-05-14 03:00:00'),
            'Somewhere',
        );
        $person->emancipate();

        $this->assertTrue($person->isAdult($clock));
    }

    public function testANonAdultCantObtainADriverLicense()
    {
        $clock = new FrozenClock(new PointInTime('2020-05-15 11:30:00'));
        $deliver = new DeliverDriverLicense($clock);

        $person = new Person(
            'John',
            'Doe',
            $clock->at('2004-05-14 03:00:00'),
            'Somewhere',
        );

        try {
            $person->obtainDriverLicense($clock, $deliver);
            $this->fail('it should throw an exception');
        } catch (\LogicException $e) {
            $this->assertFalse($person->hasDriverLicense());
        }
    }

    public function testAnAdultCanObtainADriverLicense()
    {
        $clock = new FrozenClock(new PointInTime('2020-05-15 11:30:00'));
        $deliver = new DeliverDriverLicense($clock);

        $person = new Person(
            'John',
            'Doe',
            $clock->at('2000-05-16 03:00:00'),
            'Somewhere',
        );

        $this->assertFalse($person->hasDriverLicense());
        $this->assertNull($person->obtainDriverLicense($clock, $deliver));
        $this->assertTrue($person->hasDriverLicense());
    }
}
