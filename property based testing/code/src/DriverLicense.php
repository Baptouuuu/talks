<?php
declare(strict_types = 1);

namespace PBT;

use Innmind\TimeContinuum\PointInTime;

final class DriverLicense
{
    public function __construct(
        string $firstName,
        string $lastName,
        PointInTime $birthDay,
        string $placeOfBirth
    ) {
    }
}
