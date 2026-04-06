<?php

namespace App\Enums;

enum RequestStatus: int
{
    case DRAFT = 1;
    case SUBMITTED = 2;
    case STAFF1_APPROVED = 3;
    case STAFF2_APPROVED = 4;
    case DEAN_APPROVED = 5;
    case RETURNED = 6;
    case REJECTED = 7;

    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::STAFF1_APPROVED => 'Staff 1 Approved',
            self::STAFF2_APPROVED => 'Staff 2 Approved',
            self::DEAN_APPROVED => 'Dean Approved',
            self::RETURNED => 'Returned for Revision',
            self::REJECTED => 'Rejected',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::DRAFT => 'bg-gray-100 text-gray-700',
            self::SUBMITTED => 'bg-orange-100 text-orange-700',
            self::STAFF1_APPROVED => 'bg-blue-100 text-blue-700',
            self::STAFF2_APPROVED => 'bg-purple-100 text-purple-700',
            self::DEAN_APPROVED => 'bg-green-100 text-green-700',
            self::RETURNED => 'bg-yellow-100 text-yellow-700',
            self::REJECTED => 'bg-red-100 text-red-700',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::DEAN_APPROVED, self::REJECTED]);
    }

    public function canBeEditedByAdmission(): bool
    {
        return $this === self::RETURNED;
    }

    public function canBeActionedByStaff1(): bool
    {
        return $this === self::SUBMITTED;
    }

    public function canBeActionedByStaff2(): bool
    {
        return in_array($this, [self::SUBMITTED, self::STAFF1_APPROVED]);
    }

    public function canBeActionedByDean(): bool
    {
        return $this === self::STAFF2_APPROVED;
    }

    public static function getAllCases(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->value => $case->getLabel()
        ])->toArray();
    }
}
