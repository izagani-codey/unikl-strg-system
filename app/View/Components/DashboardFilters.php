<?php

namespace App\View\Components;

use App\Enums\RequestStatus;
use App\Models\RequestType;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;

class DashboardFilters extends Component
{
    public string $role = 'admission';
    public Collection $requestTypes;
    public string $title = 'Filter Requests';
    public string $description = 'Find specific requests quickly';
    public string $colorTheme = 'indigo';

    public function __construct(
        string $role = 'admission',
        Collection $requestTypes = null,
        string $title = 'Filter Requests',
        string $description = 'Find specific requests quickly',
        string $colorTheme = 'indigo'
    ) {
        $this->role = $role;
        $this->requestTypes = $requestTypes ?: new Collection();
        $this->title = $title;
        $this->description = $description;
        $this->colorTheme = $colorTheme;
    }

    public function render(): View
    {
        $statuses = $this->getAvailableStatuses();
        $colorClasses = $this->getColorClasses();

        return view('components.dashboard-filters', [
            'statuses' => $statuses,
            'colorClasses' => $colorClasses,
            'requestTypes' => $this->requestTypes,
        ]);
    }

    private function getAvailableStatuses(): array
    {
        if ($this->role === 'admission') {
            return [
                RequestStatus::SUBMITTED->value => 'Pending Verification',
                RequestStatus::STAFF1_APPROVED->value => 'With Staff 2',
                RequestStatus::STAFF2_APPROVED->value => 'With Dean',
                RequestStatus::RETURNED->value => 'Returned to Me',
                RequestStatus::DEAN_APPROVED->value => 'Approved',
                RequestStatus::REJECTED->value => 'Rejected',
            ];
        }
        
        if ($this->role === 'staff1') {
            return [
                RequestStatus::SUBMITTED->value => 'Pending Verification',
                RequestStatus::STAFF1_APPROVED->value => 'With Staff 2',
                RequestStatus::RETURNED->value => 'Returned to Me',
                RequestStatus::DEAN_APPROVED->value => 'Approved',
                RequestStatus::REJECTED->value => 'Rejected',
            ];
        }
        
        if ($this->role === 'staff2') {
            return [
                RequestStatus::STAFF1_APPROVED->value => 'Awaiting Review',
                RequestStatus::DEAN_APPROVED->value => 'Approved',
                RequestStatus::REJECTED->value => 'Rejected',
                RequestStatus::SUBMITTED->value => 'Pending Verification',
                RequestStatus::RETURNED->value => 'Returned to Staff 1',
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
