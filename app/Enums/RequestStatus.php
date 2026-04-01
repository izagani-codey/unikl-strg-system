<?php

namespace App\Enums;

enum RequestStatus: int
{
    case PENDING_VERIFICATION = 1;
    case PENDING_RECOMMENDATION = 2;
    case PENDING_DEAN_APPROVAL = 3;
    case PENDING_DEAN_VERIFICATION = 4;
    case RETURNED_TO_ADMISSION = 5;
    case RETURNED_TO_STAFF_1 = 6;
    case RETURNED_TO_STAFF_2 = 7;
    case APPROVED = 8;
    case DECLINED = 9;

    public function getLabel(): string
    {
        return match($this) {
            self::PENDING_VERIFICATION => 'Pending Verification',
            self::PENDING_RECOMMENDATION => 'Pending Recommendation',
            self::PENDING_DEAN_APPROVAL => 'Pending Dean Approval',
            self::PENDING_DEAN_VERIFICATION => 'Pending Dean Verification',
            self::RETURNED_TO_ADMISSION => 'Returned to Admission',
            self::RETURNED_TO_STAFF_1 => 'Returned to Staff 1',
            self::RETURNED_TO_STAFF_2 => 'Returned to Staff 2',
            self::APPROVED => 'Approved',
            self::DECLINED => 'Declined',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::PENDING_VERIFICATION => 'bg-orange-100 text-orange-700',
            self::PENDING_RECOMMENDATION => 'bg-blue-100 text-blue-700',
            self::PENDING_DEAN_APPROVAL => 'bg-purple-100 text-purple-700',
            self::PENDING_DEAN_VERIFICATION => 'bg-amber-100 text-amber-700',
            self::RETURNED_TO_ADMISSION => 'bg-yellow-100 text-yellow-700',
            self::RETURNED_TO_STAFF_1 => 'bg-indigo-100 text-indigo-700',
            self::RETURNED_TO_STAFF_2 => 'bg-pink-100 text-pink-700',
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
        return $this === self::RETURNED_TO_ADMISSION;
    }

    public function canBeActionedByStaff1(): bool
    {
        return in_array($this, [self::PENDING_VERIFICATION, self::RETURNED_TO_STAFF_1]);
    }

    public function canBeActionedByStaff2(): bool
    {
        return in_array($this, [self::PENDING_RECOMMENDATION, self::RETURNED_TO_STAFF_2]);
    }

    public function canBeActionedByDean(): bool
    {
        return $this === self::PENDING_DEAN_APPROVAL;
    }

    public static function getAllCases(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->value => $case->getLabel()
        ])->toArray();
    }
}
