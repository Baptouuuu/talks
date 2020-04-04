<?php
declare(strict_types = 1);

namespace PBT;

use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
    Earth\Period\Year,
};

final class Citizen
{
    private string $firstName;
    private string $lastName;
    private PointInTime $birthday;
    private string $placeOfBirth;
    private bool $emancipatedMinor = false;

    public function __construct(
        string $firstName,
        string $lastName,
        PointInTime $birthday,
        string $placeOfBirth
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->birthday = $birthday;
        $this->placeOfBirth = $placeOfBirth;
    }

    public function emancipate(): void
    {
        $this->emancipatedMinor = true;
    }

    public function isAdult(Clock $clock): bool
    {
        if ($this->emancipatedMinor) {
            return true;
        }

        return $this->age($clock) > 18;
    }

    public function age(Clock $clock): int
    {
        $today = $clock->now();
        $years = $today->year()->toInt() - $this->birthday->year()->toInt();
        $thisYearBirthday = $this->birthday->goForward(new Year($years));

        if ($today->aheadOf($thisYearBirthday)) {
            return $years - 1;
        }

        return $years;
    }
}
