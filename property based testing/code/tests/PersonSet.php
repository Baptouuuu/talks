<?php
declare(strict_types = 1);

namespace Tests\PBT;

use PBT\Person;
use Innmind\TimeContinuum\{
    Clock,
    Earth\FrozenClock,
    Earth\Period\Composite,
    Period,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\Set;
use Fixtures\Innmind\TimeContinuum\Earth\PointInTime;

class PersonSet
{
    /**
     * @return Set<array{0: Person, 1: Clock}>
     */
    public static function anyAdult(): Set
    {
        return new Set\Either(
            self::over18YearsOld(),
            self::emancipated(),
        );
    }

    /**
     * @return Set<array{0: Person, 1: Clock}>
     */
    public static function over18YearsOld(): Set
    {
        return Set\Composite::mutable(
            function($firstname, $lastname, $birthday, $age, $placeOfBirth) {
                return [
                    new Person(
                        $firstname,
                        $lastname,
                        $birthday,
                        $placeOfBirth,
                    ),
                    new FrozenClock($birthday->goForward($age)),
                ];
            },
            Set\Strings::any(),
            Set\Strings::any(),
            PointInTime::before('3000-01-01T00:00:00'),
            self::ageAbove(18),
            Set\Strings::any(),
        );
    }

    /**
     * @return Set<array{0: Person, 1: Clock}>
     */
    public static function emancipated(): Set
    {
        return Set\Composite::mutable(
            function($firstname, $lastname, $birthday, $age, $placeOfBirth) {
                $person = new Person(
                    $firstname,
                    $lastname,
                    $birthday,
                    $placeOfBirth,
                );
                $person->emancipate();

                return [
                    $person,
                    new FrozenClock($birthday->goForward($age)),
                ];
            },
            Set\Strings::any(),
            Set\Strings::any(),
            PointInTime::before('3000-01-01T00:00:00'),
            self::ageAbove(0),
            Set\Strings::any(),
        );
    }

    /**
     * @return Set<Period>
     */
    private static function ageAbove(int $min): Set
    {
        return Set\Composite::immutable(
            static function($year, $month, $day, $hour, $minute, $second, $millisecond): Composite {
                return new Composite($year, $month, $day, $hour, $minute, $second, $millisecond);
            },
            Set\Integers::between($min, 130), // small chance someone will be older than that
            Set\Integers::between(0, 12),
            Set\Integers::between(0, 30),
            Set\Integers::between(0, 23),
            Set\Integers::between(0, 59),
            Set\Integers::between(0, 59),
            Set\Integers::between(0, 999),
        );
    }
}
