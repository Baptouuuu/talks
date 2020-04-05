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

class ParametrizedTest extends TestCase
{
    private $clock;
    private $faker;

    public function setUp(): void
    {
        $this->clock = new FrozenClock(new PointInTime('2020-05-15 11:30:00'));
        $this->faker = \Faker\Factory::create();
    }

    /**
     * @dataProvider ages
     */
    public function testAge($birthday, $expectedAge)
    {
        $citizen = new Citizen(
            $this->faker->firstName,
            $this->faker->lastName,
            $this->clock->at($birthday),
            $this->faker->city,
        );
        $this->assertSame($expectedAge, $citizen->age($this->clock));
    }

    public function testIsNotAnAdult()
    {
        $citizen = new Citizen(
            $this->faker->firstName,
            $this->faker->lastName,
            $this->clock->at('2004-05-14 03:00:00'),
            $this->faker->city,
        );
        $this->assertFalse($citizen->isAdult($this->clock));
    }

    public function testIsAdult()
    {
        $citizen = new Citizen(
            $this->faker->firstName,
            $this->faker->lastName,
            $this->clock->at('2000-05-16 03:00:00'),
            $this->faker->city,
        );
        $this->assertTrue($citizen->isAdult($this->clock));
    }

    public function testEmancipatedCitizenIsConsideredAnAdult()
    {
        $citizen = new Citizen(
            $this->faker->firstName,
            $this->faker->lastName,
            $this->clock->at('2004-05-14 03:00:00'),
            $this->faker->city,
        );
        $citizen->emancipate();

        $this->assertTrue($citizen->isAdult($this->clock));
    }

    public function testANonAdultCantObtainADriverLicense()
    {
        $deliver = new DeliverDriverLicense($this->clock);

        $citizen = new Citizen(
            $this->faker->firstName,
            $this->faker->lastName,
            $this->clock->at('2004-05-14 03:00:00'),
            $this->faker->city,
        );

        try {
            $citizen->obtainDriverLicense($this->clock, $deliver);
            $this->fail('it should throw an exception');
        } catch (\LogicException $e) {
            $this->assertFalse($citizen->hasDriverLicense());
        }
    }

    public function testAnAdultCanObtainADriverLicense()
    {
        $deliver = new DeliverDriverLicense($this->clock);

        $citizen = new Citizen(
            $this->faker->firstName,
            $this->faker->lastName,
            $this->clock->at('2000-05-16 03:00:00'),
            $this->faker->city,
        );

        $this->assertFalse($citizen->hasDriverLicense());
        $this->assertNull($citizen->obtainDriverLicense($this->clock, $deliver));
        $this->assertTrue($citizen->hasDriverLicense());
    }

    public function ages(): array
    {
        return [
            'the day before is 16th birthday' => [
                '2004-05-16 03:00:00',
                15,
            ],
            'the day after is 20th birthday' => [
                '2000-05-14 03:00:00',
                20,
            ],
        ];
    }
}
