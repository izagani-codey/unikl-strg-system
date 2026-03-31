<?php

namespace App\Enums;

enum RequestStatus: int
{
    case PENDING_VERIFICATION = 1;
    case PENDING_RECOMMENDATION = 2;
    case RETURNED_TO_ADMISSION = 3;
    case RETURNED_TO_STAFF_1 = 4;
    case APPROVED = 5;
    case DECLINED = 6;

    public function getLabel(): string
    {
        return match($this) {
            self::PENDING_VERIFICATION => 'Pending Verification',
            self::PENDING_RECOMMENDATION => 'Pending Recommendation',
            self::RETURNED_TO_ADMISSION => 'Returned to Admission',
            self::RETURNED_TO_STAFF_1 => 'Returned to Staff 1',
            self::APPROVED => 'Approved',
            self::DECLINED => 'Declined',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::PENDING_VERIFICATION => 'bg-orange-100 text-orange-700',
            self::PENDING_RECOMMENDATION => 'bg-blue-100 text-blue-700',
            self::RETURNED_TO_ADMISSION => 'bg-yellow-100 text-yellow-700',
            self::RETURNED_TO_STAFF_1 => 'bg-purple-100 text-purple-700',
            self::APPROVED => 'bg-green-100 text-green-700',
            self::DECLINED => 'bg-red-100 text-red-700',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::APPROVED, self::DECLINED]);
    }

    public function canBeEditedByAdmission(): bool
    {
        return in_array($this, [self::PENDING_VERIFICATION, self::RETURNED_TO_ADMISSION, self::RETURNED_TO_STAFF_1]);
    }

    public function canBeActionedByStaff1(): bool
    {
        return in_array($this, [self::PENDING_VERIFICATION, self::RETURNED_TO_STAFF_1]);
    }

    public function canBeActionedByStaff2(): bool
    {
        return $this === self::PENDING_RECOMMENDATION;
    }

    public static function getAllCases(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->value => $case->getLabel()
        ])->toArray();
    }
}
