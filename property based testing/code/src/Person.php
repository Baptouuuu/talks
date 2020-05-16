<?php
declare(strict_types = 1);

namespace PBT;

use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
    Earth\Period\Year,
};

final class Person
{
    private string $firstName;
    private string $lastName;
    private PointInTime $birthday;
    private string $placeOfBirth;
    private bool $emancipatedMinor = false;
    private ?DriverLicense $driverLicense = null;

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

        return $this->age($clock) >= 18;
    }

    public function age(Clock $clock): int
    {
        $today = $clock->now();
        $years = $today->year()->toInt() - $this->birthday->year()->toInt();
        $thisYearBirthday = $this->birthday->goForward(new Year($years));

        if ($thisYearBirthday->aheadOf($today)) {
            return $years - 1;
        }

        return $years;
    }

    public function obtainDriverLicense(Clock $clock, DeliverDriverLicense $deliver): void
    {
        if (!$this->isAdult($clock)) {
            throw new \LogicException("Only an adult can obtain a driver license");
        }

        $this->driverLicense = $deliver(
            $this->firstName,
            $this->lastName,
            $this->birthday,
            $this->placeOfBirth,
        );
    }

    public function hasDriverLicense(): bool
    {
        return !\is_null($this->driverLicense);
    }
}
