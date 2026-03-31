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
        if ($this === self::PENDING_VERIFICATION) {
            return 'Pending Verification';
        }
        
        if ($this === self::PENDING_RECOMMENDATION) {
            return 'Pending Recommendation';
        }
        
        if ($this === self::RETURNED_TO_ADMISSION) {
            return 'Returned to Admission';
        }
        
        if ($this === self::RETURNED_TO_STAFF_1) {
            return 'Returned to Staff 1';
        }
        
        if ($this === self::APPROVED) {
            return 'Approved';
        }
        
        if ($this === self::DECLINED) {
            return 'Declined';
        }
        
        return 'Unknown';
    }

    public function getColor(): string
    {
        if ($this === self::PENDING_VERIFICATION) {
            return 'bg-orange-100 text-orange-700';
        }
        
        if ($this === self::PENDING_RECOMMENDATION) {
            return 'bg-blue-100 text-blue-700';
        }
        
        if ($this === self::RETURNED_TO_ADMISSION) {
            return 'bg-yellow-100 text-yellow-700';
        }
        
        if ($this === self::RETURNED_TO_STAFF_1) {
            return 'bg-purple-100 text-purple-700';
        }
        
        if ($this === self::APPROVED) {
            return 'bg-green-100 text-green-700';
        }
        
        if ($this === self::DECLINED) {
            return 'bg-red-100 text-red-700';
        }
        
        return 'bg-gray-100 text-gray-700';
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
