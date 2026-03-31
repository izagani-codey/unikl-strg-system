<?php

namespace App\View\Components;

use App\Enums\RequestStatus;
use App\Models\RequestType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;

class DashboardFilters extends Component
{
    public function __construct(
        public string $role = 'admission',
        public Collection|array $requestTypes = [],
        public string $title = 'Filter Requests',
        public string $description = 'Find specific requests quickly',
        public string $colorTheme = 'indigo'
    ) {}

    public function render(): View
    {
        $statuses = $this->getAvailableStatuses();
        $colorClasses = $this->getColorClasses();

        return view('components.dashboard-filters', [
            'statuses' => $statuses,
            'colorClasses' => $colorClasses,
        ]);
    }

    private function getAvailableStatuses(): array
    {
        if ($this->role === 'admission') {
            return [
                RequestStatus::PENDING_VERIFICATION->value => 'Pending Verification',
                RequestStatus::PENDING_RECOMMENDATION->value => 'With Staff 2',
                RequestStatus::RETURNED_TO_ADMISSION->value => 'Returned to Me',
                RequestStatus::RETURNED_TO_STAFF_1->value => 'Returned to Staff 1',
                RequestStatus::APPROVED->value => 'Approved',
                RequestStatus::DECLINED->value => 'Declined',
            ];
        }
        
        if ($this->role === 'staff1') {
            return [
                RequestStatus::PENDING_VERIFICATION->value => 'Pending Verification',
                RequestStatus::PENDING_RECOMMENDATION->value => 'With Staff 2',
                RequestStatus::RETURNED_TO_STAFF_1->value => 'Returned to Me',
                RequestStatus::APPROVED->value => 'Approved',
                RequestStatus::DECLINED->value => 'Declined',
            ];
        }
        
        if ($this->role === 'staff2') {
            return [
                RequestStatus::PENDING_RECOMMENDATION->value => 'Awaiting Review',
                RequestStatus::APPROVED->value => 'Approved',
                RequestStatus::DECLINED->value => 'Declined',
                RequestStatus::PENDING_VERIFICATION->value => 'Pending Verification',
                RequestStatus::RETURNED_TO_STAFF_1->value => 'Returned to Staff 1',
            ];
        }
        
        return [];
    }

    private function getColorClasses(): array
    {
        if ($this->colorTheme === 'indigo') {
            return [
                'focus' => 'ring-indigo-500 focus:border-indigo-500',
                'button' => 'bg-indigo-600 hover:bg-indigo-700',
                'icon' => 'text-indigo-600',
            ];
        }
        
        if ($this->colorTheme === 'purple') {
            return [
                'focus' => 'ring-purple-500 focus:border-purple-500',
                'button' => 'bg-purple-600 hover:bg-purple-700',
                'icon' => 'text-purple-600',
            ];
        }
        
        if ($this->colorTheme === 'green') {
            return [
                'focus' => 'ring-green-500 focus:border-green-500',
                'button' => 'bg-green-600 hover:bg-green-700',
                'icon' => 'text-green-600',
            ];
        }
        
        return [
            'focus' => 'ring-gray-500 focus:border-gray-500',
            'button' => 'bg-gray-600 hover:bg-gray-700',
            'icon' => 'text-gray-600',
        ];
    }
}
