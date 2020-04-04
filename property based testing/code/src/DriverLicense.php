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
        if (
            \mb_strlen($firstName) > 60 ||
            \mb_strlen($lastName) > 60 ||
            \mb_strlen($placeOfBirth) > 60
        ) {
            throw new \LogicException("Data can't fit in the document");
        }
    }
}
