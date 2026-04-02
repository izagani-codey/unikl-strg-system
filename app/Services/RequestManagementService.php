<?php

namespace App\Services;

use App\Http\Requests\StoreRequestRequest;
use App\Http\Requests\UpdateRequestRequest;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RequestManagementService
{
    public function getUserRequests()
    {
        $user = Auth::user();
        
        if ($user->role === 'admission') {
            return GrantRequest::where('user_id', $user->id)
                ->with(['requestType', 'verifiedBy', 'recommendedBy'])
                ->latest()
                ->get();
        }
        
        // For staff users, return all requests
        return GrantRequest::with(['requestType', 'user', 'verifiedBy', 'recommendedBy'])
            ->latest()
            ->get();
    }

    public function createRequest(StoreRequestRequest $request)
    {
        $user = Auth::user();
        
        // Normalize VOT items
        $votItems = $this->normalizeVotItems($request->input('vot_items', []));
        
        // Calculate total amount
        $totalAmount = collect($votItems)->sum('amount');
        
        // Determine priority based on deadline
        $priority = $this->calculatePriority($request->input('deadline'));
        
        // Generate reference number
        $refNumber = $this->generateReferenceNumber();
        
        // Create the request
        $grantRequest = GrantRequest::create([
            'user_id' => $user->id,
            'request_type_id' => $request->input('request_type_id'),
            'description' => $request->input('description'),
            'vot_items' => $votItems,
            'total_amount' => $totalAmount,
            'deadline' => $request->input('deadline'),
            'priority' => $priority,
            'signature_data' => $request->input('signature_data'),
            'signed_at' => now(),
            'submitted_at' => now(),
            'ref_number' => $refNumber,
            'status_id' => 1, // PENDING_VERIFICATION
            // Submitter profile snapshot
            'submitter_staff_id' => $user->staff_id,
            'submitter_designation' => $user->designation,
            'submitter_department' => $user->department,
            'submitter_phone' => $user->phone,
            'submitter_employee_level' => $user->employee_level,
        ]);
        
        // Handle file upload if present
        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('documents', 'public');
            $grantRequest->update(['file_path' => $path]);
        }
        
        return $grantRequest;
    }

    public function getRequestWithRelations($id)
    {
        return GrantRequest::with([
            'user',
            'requestType',
            'verifiedBy',
            'recommendedBy',
            'auditLogs' => function ($query) {
                $query->with('actor')->latest();
            }
        ])->findOrFail($id);
    }

    public function getRequestForEdit($id)
    {
        return GrantRequest::with(['requestType', 'user'])->findOrFail($id);
    }

    public function updateRequest(UpdateRequestRequest $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        
        $updateData = [
            'request_type_id' => $request->input('request_type_id'),
            'description' => $request->input('description'),
            'deadline' => $request->input('deadline'),
            'priority' => $request->input('priority'),
        ];
        
        // Update VOT items if provided
        if ($request->has('vot_items')) {
            $votItems = $this->normalizeVotItems($request->input('vot_items', []));
            $updateData['vot_items'] = $votItems;
            $updateData['total_amount'] = collect($votItems)->sum('amount');
        }
        
        $grantRequest->update($updateData);
        
        return $grantRequest;
    }

    public function deleteRequest($id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        
        // Delete associated file if exists
        if ($grantRequest->file_path) {
            \Storage::disk('public')->delete($grantRequest->file_path);
        }
        
        $grantRequest->delete();
    }

    private function normalizeVotItems(array $votItems): array
    {
        return collect($votItems)->map(function ($item) {
            return [
                'vot_code' => $item['vot_code'] ?? null,
                'amount' => (float) ($item['amount'] ?? 0),
                'description' => $item['description'] ?? '',
            ];
        })->filter(function ($item) {
            return !empty($item['vot_code']) && $item['amount'] > 0;
        })->values()->all();
    }

    private function calculatePriority($deadline): bool
    {
        if (!$deadline) {
            return false;
        }
        
        $deadlineDate = Carbon::parse($deadline);
        $daysUntilDeadline = Carbon::now()->diffInDays($deadlineDate, false);
        
        return $daysUntilDeadline <= 5; // HIGH priority if deadline is within 5 days
    }

    private function generateReferenceNumber(): string
    {
        $prefix = 'STRG';
        $year = date('Y');
        $sequence = GrantRequest::whereYear('created_at', $year)->count() + 1;
        return sprintf('%s-%s-%04d', $prefix, $year, $sequence);
    }
}
