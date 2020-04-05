<?php
declare(strict_types = 1);

namespace Tests\PBT;

use PBT\{
    Citizen,
    DeliverDriverLicense,
};
use Innmind\TimeContinuum\Earth\{
    FrozenClock,
    PointInTime\PointInTime as PIT,
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
}
