<?php
declare(strict_types = 1);

namespace PBT;

use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
    Earth\Format\ISO8601,
    Earth\Period\Year,
};

final class DeliverDriverLicense
{
    private Clock $clock;

    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    public function __invoke(
        string $firstName,
        string $lastName,
        PointInTime $birthday,
        string $placeOfBirth
    ): DriverLicense {
        $today = $this->clock->now();
        $years = $today->year()->toInt() - $birthday->year()->toInt();
        $thisYearBirthday = $birthday->goForward(new Year($years));
        $age = $years;

        if ($today->aheadOf($thisYearBirthday)) {
            $age = $years - 1;
        }

        if ($age < 18) {
            throw new \LogicException("Citizen too young to obtain a driver license");
        }

        $licenseId = \md5($this->clock->now()->format(new ISO8601));

        return new DriverLicense(
            $firstName,
            $lastName,
            $birthday,
            $placeOfBirth,
            $licenseId,
        );
    }
}
