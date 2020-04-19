<?php
declare(strict_types = 1);

namespace Tests\PBT;

use PBT\{
    Citizen,
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
                PointInTime::any(),
                Set\Decorate::immutable(
                    static fn(int $year): Year => new Year($year),
                    Set\Integers::between(1, 130), // small chance someone will be older than that
                ),
                Period::lessThanAYear(),
                Set\Strings::any(),
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->filter(function($birthday) {
                // otherwise it may exceed the max year supported by PHP
                return $birthday->year()->toInt() < 9500;
            })
            ->filter(function($birthday, $age, $timeBeforeBirthday) {
                return $birthday->day()->ofYear() > $timeBeforeBirthday->days();
            })
            ->then(function($birthday, $age, $timeBeforeBirthday, $firstName, $lastName, $placeOfBirth) {
                $now = $birthday->goForward($age)->goBack($timeBeforeBirthday);
                $clock = new FrozenClock($now);
                $citizen = new Citizen(
                    $firstName,
                    $lastName,
                    $birthday,
                    $placeOfBirth,
                );

                $this->assertSame($age->years() - 1, $citizen->age($clock));
            });
    }

    public function testAgeWithPeriodAfterBirthdayIsDifferenceBetweenYears()
    {
        $this
            ->forAll(
                PointInTime::any(),
                Set\Decorate::immutable(
                    static fn(int $year): Year => new Year($year),
                    Set\Integers::between(1, 130), // small chance someone will be older than that
                ),
                Period::lessThanAYear(),
                Set\Strings::any(),
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->filter(function($birthday) {
                // otherwise it may exceed the max year supported by PHP
                return $birthday->year()->toInt() < 9500;
            })
            ->filter(function($birthday, $age, $timeAfterBirthday) {
                return $birthday->day()->ofYear() < $timeAfterBirthday->days();
            })
            ->then(function($birthday, $age, $timeAfterBirthday, $firstName, $lastName, $placeOfBirth) {
                $now = $birthday->goForward($age)->goForward($timeAfterBirthday);
                $clock = new FrozenClock($now);
                $citizen = new Citizen(
                    $firstName,
                    $lastName,
                    $birthday,
                    $placeOfBirth,
                );

                $this->assertSame($age->years(), $citizen->age($clock));
            });
    }

    public function testCitizenBornLessThanAYearAgoHasAnAgeOfZero()
    {
        $this
            ->forAll(
                PointInTime::any(),
                Period::lessThanAYear(),
                Set\Strings::any(),
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->filter(function($birthday) {
                // otherwise it may exceed the max year supported by PHP
                return $birthday->year()->toInt() < 9500;
            })
            ->then(function($birthday, $lessThanAYear, $firstName, $lastName, $placeOfBirth) {
                $now = $birthday->goForward($lessThanAYear);
                $clock = new FrozenClock($now);
                $citizen = new Citizen(
                    $firstName,
                    $lastName,
                    $birthday,
                    $placeOfBirth,
                );

                $this->assertSame(0, $citizen->age($clock));
            });
    }

    public function testCitizenIsConsideredAnAdultWhenHeReachesHis18thBirthday()
    {
        $this
            ->forAll(
                PointInTime::any(),
                Set\Composite::immutable(
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
                    Set\Integers::between(0, 130), // small chance someone will be older than that
                    Set\Integers::between(0, 12),
                    Set\Integers::between(0, 30),
                    Set\Integers::between(0, 23),
                    Set\Integers::between(0, 59),
                    Set\Integers::between(0, 59),
                    Set\Integers::between(0, 999),
                ),
                Set\Strings::any(),
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->filter(function($birthday) {
                // otherwise it may exceed the max year supported by PHP
                return $birthday->year()->toInt() < 9500;
            })
            ->then(function($birthday, $period, $firstName, $lastName, $placeOfBirth) {
                $now = $birthday->goForward($period->add(new Year(18)));
                $clock = new FrozenClock($now);
                $citizen = new Citizen(
                    $firstName,
                    $lastName,
                    $birthday,
                    $placeOfBirth,
                );

                $this->assertTrue($citizen->isAdult($clock));
            });
    }

    public function testCitizenIsNotConsideredAnAdultWhenHeHasntReachedHis18thBirthdayYet()
    {
        $this
            ->forAll(
                PointInTime::any(),
                Set\Composite::immutable(
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
                    Set\Integers::between(0, 17), // can't be older than 17
                    Set\Integers::between(0, 11),
                    Set\Integers::between(0, 30),
                    Set\Integers::between(0, 23),
                    Set\Integers::between(0, 59),
                    Set\Integers::between(0, 59),
                    Set\Integers::between(0, 999),
                ),
                Set\Strings::any(),
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->filter(function($birthday) {
                // otherwise it may exceed the max year supported by PHP
                return $birthday->year()->toInt() < 9500;
            })
            ->then(function($birthday, $age, $firstName, $lastName, $placeOfBirth) {
                $now = $birthday->goForward($age);
                $clock = new FrozenClock($now);
                $citizen = new Citizen(
                    $firstName,
                    $lastName,
                    $birthday,
                    $placeOfBirth,
                );

                $this->assertFalse($citizen->isAdult($clock));
            });
    }

    public function testCitizenIsConsideredAnAdultWhenHeEmancipateNotMatterHisAge()
    {
        $this
            ->forAll(
                PointInTime::any(),
                Set\Composite::immutable(
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
                    Set\Integers::between(0, 130), // small chance someone will be older than that
                    Set\Integers::between(0, 12),
                    Set\Integers::between(0, 30),
                    Set\Integers::between(0, 23),
                    Set\Integers::between(0, 59),
                    Set\Integers::between(0, 59),
                    Set\Integers::between(0, 999),
                ),
                Set\Strings::any(),
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->filter(function($birthday) {
                // otherwise it may exceed the max year supported by PHP
                return $birthday->year()->toInt() < 9500;
            })
            ->then(function($birthday, $period, $firstName, $lastName, $placeOfBirth) {
                $now = $birthday->goForward($period);
                $clock = new FrozenClock($now);
                $citizen = new Citizen(
                    $firstName,
                    $lastName,
                    $birthday,
                    $placeOfBirth,
                );
                $citizen->emancipate();

                $this->assertTrue($citizen->isAdult($clock));
            });
    }

    public function testAnyAdultCanObtainADriverLicense()
    {
        $this
            ->forAll(CitizenSet::anyAdult())
            ->then(function($citizenAndClock) {
                [$citizen, $clock] = $citizenAndClock;
                $deliver = new DeliverDriverLicense($clock);

                $this->assertFalse($citizen->hasDriverLicense());
                $this->assertNull($citizen->obtainDriverLicense($clock, $deliver));
                $this->assertTrue($citizen->hasDriverLicense());
            });
    }
}
