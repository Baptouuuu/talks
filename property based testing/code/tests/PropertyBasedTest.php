<?php
declare(strict_types = 1);

namespace Tests\PBT;

use PBT\{
    Person,
    DeliverDriverLicense,
};
use Innmind\TimeContinuum\Earth\{
    FrozenClock,
    Period\Year,
    Period\Composite,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};
use Fixtures\Innmind\TimeContinuum\Earth\{
    Period,
    PointInTime,
};

class PropertyBasedTest extends TestCase
{
    use BlackBox;

    public function testAgeWithPeriodBeforeBirthdayIsDifferenceBetweenYearsMinusOne()
    {
        $this
            ->forAll(
                PointInTime::before('3000-01-01T00:00:00'),
                Set\Decorate::immutable(
                    static fn(int $year): Year => new Year($year),
                    Set\Integers::between(1, 130), // small chance someone will be older than that
                ),
                Period::lessThanAYear(),
                Set\Strings::any(),
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->then(function($birthday, $age, $lessThanAYear, $firstName, $lastName, $placeOfBirth) {
                $now = $birthday->goForward($age)->goBack($lessThanAYear);
                $clock = new FrozenClock($now);
                $person = new Person(
                    $firstName,
                    $lastName,
                    $birthday,
                    $placeOfBirth,
                );

                $this->assertSame($age->years() - 1, $person->age($clock));
            });
    }

    public function testAgeWithPeriodAfterBirthdayIsDifferenceBetweenYears()
    {
        $this
            ->forAll(
                PointInTime::before('3000-01-01T00:00:00'),
                Set\Decorate::immutable(
                    static fn(int $year): Year => new Year($year),
                    Set\Integers::between(1, 130), // small chance someone will be older than that
                ),
                Period::lessThanAYear(),
                Set\Strings::any(),
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->then(function($birthday, $age, $lessThanAYear, $firstName, $lastName, $placeOfBirth) {
                $now = $birthday->goForward($age)->goForward($lessThanAYear);
                $clock = new FrozenClock($now);
                $person = new Person(
                    $firstName,
                    $lastName,
                    $birthday,
                    $placeOfBirth,
                );

                $this->assertSame($age->years(), $person->age($clock));
            });
    }

    public function testCitizenBornLessThanAYearAgoHasAnAgeOfZero()
    {
        $this
            ->forAll(
                PointInTime::before('3000-01-01T00:00:00'),
                Period::lessThanAYear(),
                Set\Strings::any(),
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->then(function($birthday, $lessThanAYear, $firstName, $lastName, $placeOfBirth) {
                $now = $birthday->goForward($lessThanAYear);
                $clock = new FrozenClock($now);
                $person = new Person(
                    $firstName,
                    $lastName,
                    $birthday,
                    $placeOfBirth,
                );

                $this->assertSame(0, $person->age($clock));
            });
    }

    public function testCitizenIsConsideredAnAdultWhenHeReachesHis18thBirthday()
    {
        $this
            ->forAll(
                PointInTime::before('3000-01-01T00:00:00'),
                $this->ageBetween(18, 130), // small chance someone will be older than that
                Set\Strings::any(),
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->then(function($birthday, $period, $firstName, $lastName, $placeOfBirth) {
                $now = $birthday->goForward($period);
                $clock = new FrozenClock($now);
                $person = new Person(
                    $firstName,
                    $lastName,
                    $birthday,
                    $placeOfBirth,
                );

                $this->assertTrue($person->isAdult($clock));
            });
    }

    public function testCitizenIsNotConsideredAnAdultWhenHeHasntReachedHis18thBirthdayYet()
    {
        $this
            ->forAll(
                PointInTime::before('3000-01-01T00:00:00'),
                $this->ageBetween(0, 17), // can't be older than 17
                Set\Strings::any(),
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->then(function($birthday, $age, $firstName, $lastName, $placeOfBirth) {
                $now = $birthday->goForward($age);
                $clock = new FrozenClock($now);
                $person = new Person(
                    $firstName,
                    $lastName,
                    $birthday,
                    $placeOfBirth,
                );

                $this->assertFalse($person->isAdult($clock));
            });
    }

    public function testCitizenIsConsideredAnAdultWhenHeEmancipateNotMatterHisAge()
    {
        $this
            ->forAll(
                PointInTime::before('3000-01-01T00:00:00'),
                $this->ageBetween(0, 130), // small chance someone will be older than that
                Set\Strings::any(),
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->then(function($birthday, $period, $firstName, $lastName, $placeOfBirth) {
                $now = $birthday->goForward($period);
                $clock = new FrozenClock($now);
                $person = new Person(
                    $firstName,
                    $lastName,
                    $birthday,
                    $placeOfBirth,
                );
                $person->emancipate();

                $this->assertTrue($person->isAdult($clock));
            });
    }

    public function testAnyAdultCanObtainADriverLicense()
    {
        $this
            ->forAll(PersonSet::anyAdult())
            ->then(function($personAndClock) {
                [$person, $clock] = $personAndClock;
                $deliver = new DeliverDriverLicense($clock);

                $this->assertFalse($person->hasDriverLicense());
                $this->assertNull($person->obtainDriverLicense($clock, $deliver));
                $this->assertTrue($person->hasDriverLicense());
            });
    }

    private function ageBetween(int $min, int $max): Set
    {
        return Set\Composite::immutable(
            static function($year, $month, $day, $hour, $minute, $second, $millisecond): Composite {
                return new Composite(
                    $year,
                    $month,
                    $day,
                    $hour,
                    $minute,
                    $second,
                    $millisecond,
                );
            },
            Set\Integers::between($min, $max),
            Set\Integers::between(0, 11),
            Set\Integers::between(0, 29), // prevent overlapping to march from february
            Set\Integers::between(0, 23),
            Set\Integers::between(0, 59),
            Set\Integers::between(0, 59),
            Set\Integers::between(0, 999),
        );
    }
}
