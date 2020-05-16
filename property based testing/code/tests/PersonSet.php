<?php
declare(strict_types = 1);

namespace Tests\PBT;

use PBT\Person;
use Innmind\TimeContinuum\{
    Clock,
    Earth\FrozenClock,
    Earth\Period\Composite,
    Earth\Period\Year,
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
                    new FrozenClock($birthday->goForward($age->add(new Year(18)))),
                ];
            },
            Set\Strings::any(),
            Set\Strings::any(),
            PointInTime::any()->filter(
                fn($birthday) => $birthday->year()->toInt() < 9500, // otherwise it may exceed the max year supported by PHP
            ),
            self::age(),
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
            PointInTime::any()->filter(
                fn($birthday) => $birthday->year()->toInt() < 9500, // otherwise it may exceed the max year supported by PHP
            ),
            self::age(),
            Set\Strings::any(),
        );
    }

    /**
     * @return Set<Period>
     */
    private static function age(): Set
    {
        return Set\Composite::immutable(
            static function($year, $month, $day, $hour, $minute, $second, $millisecond): Composite {
                return new Composite($year, $month, $day, $hour, $minute, $second, $millisecond);
            },
            Set\Integers::between(0, 130), // small chance someone will be older than that
            Set\Integers::between(0, 12),
            Set\Integers::between(0, 30),
            Set\Integers::between(0, 23),
            Set\Integers::between(0, 59),
            Set\Integers::between(0, 59),
            Set\Integers::between(0, 999),
        );
    }
}
