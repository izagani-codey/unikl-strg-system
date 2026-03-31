<?php

namespace App\View\Components;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use Illuminate\View\Component;
use Illuminate\View\View;

class RequestTimeline extends Component
{
    public function __construct(
        public GrantRequest $request
    ) {}

    public function render(): View
    {
        $timelineSteps = $this->getTimelineSteps();
        $currentStep = $this->getCurrentStep();

        return view('components.request-timeline', [
            'timelineSteps' => $timelineSteps,
            'currentStep' => $currentStep,
        ]);
    }

    private function getTimelineSteps(): array
    {
        return [
            [
                'id' => 'submitted',
                'status' => RequestStatus::PENDING_VERIFICATION,
                'label' => 'Submitted',
                'description' => 'Request submitted by applicant',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            ],
            [
                'id' => 'verified',
                'status' => RequestStatus::PENDING_RECOMMENDATION,
                'label' => 'Staff 1 Verification',
                'description' => 'Request verified by Staff 1',
                'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'id' => 'recommended',
                'status' => RequestStatus::APPROVED,
                'label' => 'Staff 2 Recommendation',
                'description' => 'Request reviewed and recommended by Staff 2',
                'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'id' => 'completed',
                'status' => RequestStatus::APPROVED,
                'label' => 'Completed',
                'description' => 'Request approved and completed',
                'icon' => 'M5 13l4 4L19 7',
            ],
        ];
    }

    private function getCurrentStep(): int
    {
        $currentStatus = $this->request->getStatus();
        
        return match($currentStatus) {
            RequestStatus::PENDING_VERIFICATION => 0,
            RequestStatus::PENDING_RECOMMENDATION => 1,
            RequestStatus::RETURNED_TO_ADMISSION, RequestStatus::RETURNED_TO_STAFF_1 => 1, // Back to verification
            RequestStatus::APPROVED => 3,
            RequestStatus::DECLINED => 2, // Declined at recommendation step
            default => 0,
        };
    }

    private function getStepStatus(int $stepIndex): string
    {
        $currentStep = $this->getCurrentStep();
        
        if ($stepIndex < $currentStep) {
            return 'completed';
        } elseif ($stepIndex === $currentStep) {
            return 'current';
        } else {
            return 'pending';
        }
    }
}
