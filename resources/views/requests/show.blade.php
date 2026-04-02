<x-app-layout>
    @php
        $isFinalStatus = in_array($grantRequest->status_id, [\App\Enums\RequestStatus::APPROVED->value, \App\Enums\RequestStatus::DECLINED->value], true);
        $staff1Active = in_array($grantRequest->status_id, [\App\Enums\RequestStatus::PENDING_VERIFICATION->value, \App\Enums\RequestStatus::RETURNED_TO_STAFF_1->value], true);
        $staff1Completed = in_array($grantRequest->status_id, [
            \App\Enums\RequestStatus::PENDING_RECOMMENDATION->value,
            \App\Enums\RequestStatus::PENDING_DEAN_APPROVAL->value,
            \App\Enums\RequestStatus::RETURNED_TO_ADMISSION->value,
            \App\Enums\RequestStatus::RETURNED_TO_STAFF_2->value,
            \App\Enums\RequestStatus::APPROVED->value,
            \App\Enums\RequestStatus::DECLINED->value,
        ], true);
        $staff2Active = in_array($grantRequest->status_id, [\App\Enums\RequestStatus::PENDING_RECOMMENDATION->value, \App\Enums\RequestStatus::RETURNED_TO_STAFF_2->value], true);
        $staff2Completed = in_array($grantRequest->status_id, [
            \App\Enums\RequestStatus::PENDING_DEAN_APPROVAL->value,
            \App\Enums\RequestStatus::APPROVED->value,
            \App\Enums\RequestStatus::DECLINED->value,
        ], true);
    @endphp

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Request: {{ $grantRequest->ref_number }}
                @if($grantRequest->is_priority)
                    <span class="ml-2 px-2 py-1 bg-red-500 text-white text-xs rounded-full">⚠ PRIORITY</span>
                @endif
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('requests.print', $grantRequest->id) }}" target="_blank"
                   class="px-3 py-1 text-xs font-semibold rounded bg-slate-100 text-slate-700 hover:bg-slate-200">
                    Printable Summary
                </a>
                <span class="px-3 py-1 text-xs font-bold rounded-full {{ $grantRequest->statusClass() }}">
                    {{ $grantRequest->statusLabel() }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Success Message --}}
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Request Details --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Request Details</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Submitted By</p>
                        <p class="font-semibold">{{ $grantRequest->user->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Email</p>
                        <p class="font-semibold">{{ $grantRequest->payload['email'] ?? $grantRequest->user->email }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Request Type</p>
                        <p class="font-semibold">{{ $grantRequest->requestType->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Staff ID</p>
                        <p class="font-semibold">{{ $grantRequest->submitter_staff_id ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Designation</p>
                        <p class="font-semibold">{{ $grantRequest->submitter_designation ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Phone Number</p>
                        <p class="font-semibold">{{ $grantRequest->submitter_phone ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Amount Requested</p>
                        <p class="font-bold text-lg">RM {{ number_format((float) ($grantRequest->total_amount ?? 0), 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Date Submitted</p>
                        <p class="font-semibold">{{ $grantRequest->created_at->format('d M Y, h:i A') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Deadline</p>
                        <p class="font-semibold {{ $grantRequest->is_priority ? 'text-red-600 font-bold' : '' }}">
                            {{ $grantRequest->deadline ? $grantRequest->deadline->format('d M Y') : 'None' }}
                        </p>
                    </div>
                    @if($grantRequest->revision_count > 0)
                    <div>
                        <p class="text-gray-500">Revisions</p>
                        <p class="font-semibold text-yellow-600">{{ $grantRequest->revision_count }} revision(s)</p>
                    </div>
                    @endif
                </div>

                <div class="mt-4">
                    <p class="text-gray-500 text-sm">Justification / Description</p>
                    <div class="mt-1 p-3 bg-gray-50 rounded border text-sm">
                        {{ $grantRequest->payload['description'] ?? 'No description provided.' }}
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-gray-500 text-sm mb-2">VOT Breakdown</p>
                    <div class="overflow-hidden rounded border">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="text-left px-3 py-2">VOT Code</th>
                                    <th class="text-left px-3 py-2">Description</th>
                                    <th class="text-right px-3 py-2">Amount (RM)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($grantRequest->vot_items ?? []) as $item)
                                    <tr class="border-t">
                                        <td class="px-3 py-2 font-semibold">{{ $item['vot_code'] ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $item['description'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-right">{{ number_format((float) ($item['amount'] ?? 0), 2) }}</td>
                                    </tr>
                                @empty
                                    <tr class="border-t">
                                        <td colspan="3" class="px-3 py-2 text-center text-gray-500">No VOT items provided</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Request Timeline --}}
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    Request Timeline
                </h3>
                
                <div class="relative">
                    <!-- Timeline Line -->
                    <div class="absolute left-8 top-8 bottom-8 w-0.5 bg-gray-300"></div>
                    
                    <!-- Timeline Steps -->
                    <div class="space-y-8">
                        <!-- Step 1: Submitted -->
                        <div class="flex items-center">
                            <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center text-white">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="ml-6 flex-1">
                                <h4 class="font-semibold text-gray-900">Submitted</h4>
                                <p class="text-sm text-gray-600">Request submitted by applicant</p>
                                <div class="text-xs text-gray-500 mt-2">
                                    <span class="font-medium">Submitted by:</span> {{ $grantRequest->user->name }}
                                    <span class="ml-2">on {{ $grantRequest->created_at->format('d M Y, h:i A') }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 2: Staff 1 Verification -->
                        <div class="flex items-center">
                            <div class="w-16 h-16 {{ $staff1Completed ? 'bg-green-500' : ($staff1Active ? 'bg-blue-500' : 'bg-gray-300') }} rounded-full flex items-center justify-center text-white">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-6 flex-1">
                                <h4 class="font-semibold text-gray-900">Staff 1 Verification</h4>
                                <p class="text-sm text-gray-600">Request verified by Staff 1</p>
                                @if($grantRequest->verifiedBy)
                                    <div class="text-xs text-gray-500 mt-2">
                                        <span class="font-medium">Verified by:</span> {{ $grantRequest->verifiedBy->name }}
                                        @if($grantRequest->verified_at)
                                            <span class="ml-2">on {{ $grantRequest->verified_at->format('d M Y, h:i A') }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Step 3: Staff 2 Recommendation -->
                        <div class="flex items-center">
                            <div class="w-16 h-16 {{ $staff2Completed ? 'bg-green-500' : ($staff2Active ? 'bg-blue-500' : 'bg-gray-300') }} rounded-full flex items-center justify-center text-white">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-6 flex-1">
                                <h4 class="font-semibold text-gray-900">Staff 2 Recommendation</h4>
                                <p class="text-sm text-gray-600">Request reviewed and recommended by Staff 2</p>
                                @if($grantRequest->recommendedBy)
                                    <div class="text-xs text-gray-500 mt-2">
                                        <span class="font-medium">Recommended by:</span> {{ $grantRequest->recommendedBy->name }}
                                        @if($grantRequest->recommended_at)
                                            <span class="ml-2">on {{ $grantRequest->recommended_at->format('d M Y, h:i A') }}</span>
                                        @endif
                                    </div>
                                @endif
                                
                                <!-- Show rejection info if declined -->
                                @if($grantRequest->status_id === \App\Enums\RequestStatus::DECLINED->value)
                                    <div class="mt-2 p-2 bg-red-50 rounded border border-red-200">
                                        <p class="text-sm text-red-800">
                                            <span class="font-medium">Declined:</span> 
                                            @if($grantRequest->rejection_reason)
                                                {{ $grantRequest->rejection_reason }}
                                            @else
                                                No reason provided
                                            @endif
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Step 4: Completed -->
                        <div class="flex items-center">
                            <div class="w-16 h-16 {{ $isFinalStatus ? 'bg-green-500' : 'bg-gray-300' }} rounded-full flex items-center justify-center text-white">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="ml-6 flex-1">
                                <h4 class="font-semibold text-gray-900">Completed</h4>
                                <p class="text-sm text-gray-600">Request approved and completed</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Uploaded Document --}}
            @if($grantRequest->file_path)
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Uploaded Document</h3>
                @php
                    $ext = pathinfo($grantRequest->file_path, PATHINFO_EXTENSION);
                @endphp

                @if(in_array(strtolower($ext), ['jpg', 'jpeg', 'png']))
                    <img src="{{ asset('storage/' . $grantRequest->file_path) }}"
                         class="max-w-full rounded border" alt="Uploaded document">
                @elseif(strtolower($ext) === 'pdf')
                    <iframe src="{{ asset('storage/' . $grantRequest->file_path) }}"
                            class="w-full h-96 border rounded" title="PDF Viewer"></iframe>
                @endif

                <a href="{{ asset('storage/' . $grantRequest->file_path) }}"
                   target="_blank"
                   class="mt-3 inline-block text-blue-600 hover:underline text-sm font-semibold">
                    ↗ Open in new tab
                </a>
            </div>
            @endif

            {{-- Rejection Reason (visible to admission) --}}
            @if($grantRequest->rejection_reason)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h3 class="font-bold text-red-700 mb-1">⚠ Returned / Rejected — Reason:</h3>
                <p class="text-red-600 text-sm">{{ $grantRequest->rejection_reason }}</p>
            </div>
            @endif

            {{-- Staff Notes (staff only) --}}
           @if($grantRequest->staff_notes && in_array(auth()->user()->role, ['staff1', 'staff2']))
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h3 class="font-bold text-yellow-700 mb-1">Internal Staff Notes</h3>
                <p class="text-yellow-800 text-sm">{{ $grantRequest->staff_notes }}</p>
            </div>
            @endif

            {{-- Staff Notes History (from audit logs) --}}
            @if(in_array(auth()->user()->role, ['staff1', 'staff2']))
                @php
                    $staffNoteLogs = ($grantRequest->auditLogs ?? collect())
                        ->filter(fn ($log) => (int) $log->from_status !== 0 && !empty($log->note))
                        ->values();
                @endphp

                @if($staffNoteLogs->count() > 0)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h3 class="font-bold text-yellow-700 mb-3">Staff Notes History</h3>
                        <div class="space-y-3">
                            @foreach($staffNoteLogs as $log)
                                <div class="text-sm">
                                    <div class="text-xs text-yellow-800">
                                        {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y, h:i A') }}
                                        , {{ $log->actor?->name ?? 'Unknown' }}
                                    </div>
                                    <div class="mt-1 text-yellow-900">
                                        <span class="font-semibold">Status:</span> {{ $log->from_status }} -> {{ $log->to_status }}
                                    </div>
                                    <div class="mt-1 text-yellow-800">
                                        {{ $log->note }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif

            {{-- Verified / Recommended By (staff only) --}}
            @if(auth()->user()->role !== 'admission')
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Verification Trail</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Verified By (Staff 1)</p>
                        <p class="font-semibold">{{ $grantRequest->verifiedBy?->name ?? 'Pending' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Recommended By (Staff 2)</p>
                        <p class="font-semibold">{{ $grantRequest->recommendedBy?->name ?? 'Pending' }}</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Workflow Timeline (Audit events + internal comments) --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Workflow Timeline</h3>

                @php
                    $isStaff = auth()->user()->role !== 'admission';
                    $statusLabels = \App\Enums\RequestStatus::getAllCases();

                    $events = collect();

                    foreach (($grantRequest->auditLogs ?? collect()) as $log) {
                        $events->push([
                            'type' => 'status',
                            'at' => $log->created_at,
                            'actor' => $log->actor?->name ?? 'Unknown',
                            'from' => $log->from_status,
                            'to' => $log->to_status,
                            // Keep staff notes internal; admissions should only see status transitions.
                            'note' => $isStaff ? $log->note : null,
                        ]);
                    }

                    if ($isStaff) {
                        foreach (($grantRequest->comments ?? collect()) as $comment) {
                            // Comments are internal and staff-facing; show them in the timeline as "comment" events.
                            $events->push([
                                'type' => 'comment',
                                'at' => $comment->created_at,
                                'actor' => $comment->user?->name ?? 'Unknown',
                                'note' => $comment->content,
                            ]);
                        }
                    }

                    $events = $events->sortBy('at');
                @endphp

                @if($events->count() === 0)
                    <p class="text-gray-400 italic text-sm">No timeline events yet.</p>
                @else
                    <div class="space-y-4">
                        @foreach($events as $event)
                            <div class="flex gap-3">
                                <div class="mt-1">
                                    <div class="w-3 h-3 rounded-full {{ $event['type'] === 'status' ? 'bg-blue-600' : 'bg-gray-500' }}"></div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between gap-3 text-xs text-gray-600">
                                        <span>
                                            {{ \Carbon\Carbon::parse($event['at'])->format('d M Y, h:i A') }}
                                        </span>
                                        <span class="font-semibold">{{ $event['actor'] }}</span>
                                    </div>

                                    @if($event['type'] === 'status')
                                        <div class="mt-1 text-sm text-gray-800">
                                            <span class="font-semibold">Status:</span>
                                            {{ $statusLabels[$event['from']] ?? $event['from'] }}
                                            ->
                                            {{ $statusLabels[$event['to']] ?? $event['to'] }}
                                        </div>
                                        @if(!empty($event['note']))
                                            <div class="mt-1 text-sm text-gray-700">
                                                <span class="font-semibold">Note:</span> {{ $event['note'] }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="mt-1 text-sm text-gray-800">
                                            <span class="font-semibold">Comment:</span> {{ \Illuminate\Support\Str::limit($event['note'] ?? '', 180) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- WORKFLOW ACTION BUTTONS --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Actions</h3>

                {{-- ADMISSION: Edit if returned --}}
                @can('revise', $grantRequest)
                    <a href="{{ route('requests.edit', $grantRequest->id) }}"
                       class="inline-block bg-yellow-500 text-white px-6 py-2 rounded font-bold hover:bg-yellow-600">
                        ✏ Edit & Resubmit
                    </a>
                @endcan

                {{-- STAFF 1: Verify or Return to Admission --}}
                @can('changeStatus', $grantRequest)
                    @if(auth()->user()->role === 'staff1')
                        <form action="{{ route('requests.updateStatus', $grantRequest->id) }}" method="POST" class="space-y-3" onsubmit="return handleFormSubmit(this, 'Submitting...')">
                            @csrf
                            @method('PATCH')
                            <textarea name="notes" rows="2"
                                placeholder="Internal notes (optional)"
                                class="w-full border rounded p-2 text-sm"></textarea>
                            <textarea name="rejection_reason" rows="2"
                                placeholder="Reason for returning or rejecting (visible to admission)"
                                class="w-full border rounded p-2 text-sm"></textarea>
                            <input type="hidden" name="status_id" value="2" id="s1-status">
                            <div class="flex gap-3 flex-wrap">
                                <button type="submit"
                                    onclick="document.getElementById('s1-status').value='2'"
                                    class="bg-blue-600 text-white px-5 py-2 rounded font-bold hover:bg-blue-700">
                                    ✓ Verify & Send to Staff 2
                                </button>
                                <button type="submit"
                                    onclick="document.getElementById('s1-status').value='5'"
                                    class="bg-yellow-500 text-white px-5 py-2 rounded font-bold hover:bg-yellow-600">
                                    ↩ Return to Admission
                                </button>
                                <button type="submit"
                                    onclick="document.getElementById('s1-status').value='9'"
                                    class="bg-red-600 text-white px-5 py-2 rounded font-bold hover:bg-red-700">
                                    ✕ Reject
                                </button>
                            </div>
                        </form>
                    @endif
                @endcan

                {{-- STAFF 2: Approve, Return to Staff 1, or Decline --}}
                @can('changeStatus', $grantRequest)
                    @if(auth()->user()->role === 'staff2')
                        <form action="{{ route('requests.updateStatus', $grantRequest->id) }}" method="POST" class="space-y-3" onsubmit="return handleFormSubmit(this, 'Submitting...')">
                            @csrf
                            @method('PATCH')
                            <textarea name="notes" rows="2" placeholder="Recommendation notes (optional)"
                                class="w-full border rounded p-2 text-sm"></textarea>
                            <textarea name="rejection_reason" rows="2"
                                placeholder="Reason (required for Decline or Return)"
                                class="w-full border rounded p-2 text-sm"></textarea>
                            <input type="hidden" name="status_id" value="{{ \App\Enums\RequestStatus::PENDING_DEAN_APPROVAL->value }}" id="status2-input">
                            <div class="flex gap-3 flex-wrap">
                                <button type="submit"
                                    onclick="document.getElementById('status2-input').value='3'"
                                    class="bg-purple-600 text-white px-6 py-2 rounded font-bold hover:bg-purple-700">
                                    ✓ Send to Dean
                                </button>
                                
                                @if($grantRequest->status_id === \App\Enums\RequestStatus::PENDING_RECOMMENDATION->value)
                                    <button type="submit"
                                        onclick="document.getElementById('status2-input').value='6'"
                                        class="bg-yellow-500 text-white px-6 py-2 rounded font-bold hover:bg-yellow-600">
                                        ↩ Return to Staff 1
                                    </button>
                                @endif
                                
                                @if($grantRequest->status_id === \App\Enums\RequestStatus::PENDING_VERIFICATION->value)
                                    <button type="submit"
                                        onclick="document.getElementById('status2-input').value='2'"
                                        class="bg-blue-600 text-white px-6 py-2 rounded font-bold hover:bg-blue-700">
                                        ⚡ Override: Send to Staff 2 Review
                                    </button>
                                @endif
                                
                                <button type="submit"
                                    onclick="document.getElementById('status2-input').value='9'"
                                    class="bg-red-600 text-white px-6 py-2 rounded font-bold hover:bg-red-700">
                                    ✕ Decline
                                </button>
                            </div>
                        </form>
                        <p class="text-xs text-gray-400 mt-3 italic">Primary flow: Admission → Staff 1 → Staff 2 → Dean. Staff 2 can use override when Staff 1 is unavailable.</p>
                    @endif
                @endcan

                {{-- STAFF 2 OVERRIDE ACTIONS --}}
                @can('override', $grantRequest)
                    @if(auth()->user()->isStaff2() && auth()->user()->override_enabled)
                        <div class="mt-6 p-4 bg-purple-50 border-l-4 border-purple-500 rounded">
                            <h4 class="font-bold text-purple-800 mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                Override Actions
                            </h4>
                            
                            <form action="{{ route('requests.override', $grantRequest->id) }}" method="POST" class="space-y-3">
                                @csrf
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-purple-700">Override Action:</label>
                                    <select name="action_type" class="w-full border-purple-300 rounded p-2 text-sm" required id="override-action-type">
                                        <option value="">Select override action...</option>
                                        @if($grantRequest->status_id === \App\Enums\RequestStatus::DECLINED->value)
                                            <option value="reject_reverse">↩ Reverse Rejection</option>
                                        @endif
                                        @if($grantRequest->status_id === \App\Enums\RequestStatus::PENDING_VERIFICATION->value)
                                            <option value="bypass_verification">⚡ Bypass Staff 1 Verification</option>
                                        @endif
                                        @if(in_array($grantRequest->status_id, [\App\Enums\RequestStatus::PENDING_VERIFICATION->value, \App\Enums\RequestStatus::PENDING_RECOMMENDATION->value], true))
                                            <option value="approve">✓ Direct Approval</option>
                                        @endif
                                        <option value="priority_override">🔥 Toggle Priority</option>
                                    </select>
                                </div>

                                <div id="reinstate-double-confirmation" class="hidden space-y-2 p-3 bg-red-50 border border-red-200 rounded">
                                    <p class="text-xs font-semibold text-red-700">Double confirmation required for reinstatement.</p>
                                    <label class="flex items-center gap-2 text-sm text-red-800">
                                        <input type="checkbox" name="confirm_reinstate" value="1" class="rounded border-red-300">
                                        I confirm this rejected request should be reinstated.
                                    </label>
                                    <div>
                                        <label class="block text-sm font-medium text-red-700">Type <code>REINSTATE</code> to continue:</label>
                                        <input type="text" name="confirmation_phrase" class="w-full border-red-300 rounded p-2 text-sm" placeholder="REINSTATE">
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-purple-700">Override Reason:</label>
                                    <textarea name="reason" rows="3" placeholder="Please provide a detailed reason for this override action (10-500 characters)..."
                                        class="w-full border-purple-300 rounded p-2 text-sm" required minlength="10" maxlength="500"></textarea>
                                </div>
                                
                                <button type="submit" 
                                    class="bg-purple-600 text-white px-6 py-2 rounded font-bold hover:bg-purple-700 transition-colors"
                                    onclick="return confirm('This override action will be logged and notifications will be sent to affected staff members. Are you sure?')">
                                    ⚡ Execute Override
                                </button>
                            </form>
                        </div>
                    @endif
                @endcan

                {{-- MANUAL PRIORITY CONTROLS --}}
                @if(in_array(auth()->user()->role, ['staff1', 'staff2']) && !$isFinalStatus)
                    <div class="mt-6 p-4 bg-orange-50 border-l-4 border-orange-500 rounded">
                        <h4 class="font-bold text-orange-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Priority Management
                        </h4>
                        
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-orange-700">Current Priority:</p>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $grantRequest->priorityBadgeClass() }}">
                                        {{ $grantRequest->priorityLabel() }}
                                    </span>
                                </div>
                                
                                @if($grantRequest->deadline)
                                    <div class="text-sm text-gray-600">
                                        <span class="font-medium">Days until deadline:</span> 
                                        <span class="{{ $grantRequest->daysUntilDeadline() <= 3 ? 'text-red-600 font-bold' : ($grantRequest->daysUntilDeadline() <= 5 ? 'text-orange-600 font-semibold' : '') }}">
                                            {{ $grantRequest->daysUntilDeadline() }} days
                                        </span>
                                    </div>
                                @endif
                            </div>
                            
                            <form method="POST" action="{{ route('requests.updatePriority', $grantRequest->id) }}" class="flex items-center gap-3">
                                @csrf
                                @method('PATCH')
                                
                                <input type="hidden" name="is_priority" value="{{ $grantRequest->is_priority ? '0' : '1' }}">
                                
                                <button type="submit" 
                                    class="{{ $grantRequest->is_priority ? 'bg-gray-600 hover:bg-gray-700' : 'bg-orange-600 hover:bg-orange-700' }} text-white px-4 py-2 rounded text-sm font-medium transition-colors"
                                    onclick="return confirm('Are you sure you want to ' . ($grantRequest->is_priority ? 'remove' : 'set') . ' high priority for this request?')">
                                    {{ $grantRequest->is_priority ? '🔻 Remove Priority' : '🔺 Set High Priority' }}
                                </button>
                                
                                <span class="text-xs text-gray-500">
                                    {{ $grantRequest->deadline ? 'Auto: ≤5 days = HIGH' : 'Manual control only' }}
                                </span>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- No actions available --}}
                @if(
                    (auth()->user()->role === 'admission' && $grantRequest->status_id != \App\Enums\RequestStatus::RETURNED_TO_ADMISSION->value) ||
                    (auth()->user()->role === 'staff1' && !in_array($grantRequest->status_id, [\App\Enums\RequestStatus::PENDING_VERIFICATION->value, \App\Enums\RequestStatus::RETURNED_TO_STAFF_1->value], true)) ||
                    (auth()->user()->role === 'staff2' && $isFinalStatus)
                )
                    <p class="text-gray-400 italic text-sm">No actions available at this stage.</p>
                @endif
            </div>

            {{-- Comments (Staff only) --}}
            @if(auth()->user()->role !== 'admission')
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Internal Comments</h3>

                @forelse($grantRequest->comments as $comment)
                    <div class="mb-3 p-3 bg-gray-50 rounded border">
                        <p class="text-xs text-gray-500 mb-1">
                            <span class="font-bold">{{ $comment->user->name }}</span>
                            · {{ \Carbon\Carbon::parse($comment->created_at)->format('d M Y, h:i A') }}
                        </p>
                        <p class="text-sm">{{ $comment->content }}</p>
                    </div>
                @empty
                    <p class="text-gray-400 italic text-sm">No comments yet.</p>
                @endforelse

                @can('addComment', $grantRequest)
                    <form action="{{ route('requests.comment', $grantRequest->id) }}" method="POST" class="mt-4" onsubmit="return handleFormSubmit(this, 'Posting comment...')">
                        @csrf
                        <textarea name="content" rows="2" placeholder="Leave an internal comment for the review team..."
                            class="w-full border rounded p-2 text-sm"></textarea>
                        <button type="submit"
                            class="mt-2 bg-gray-700 text-white px-4 py-2 rounded text-sm font-bold hover:bg-gray-800">
                            Post Comment
                        </button>
                    </form>
                @endcan
            </div>
            @endif

            {{-- Audit Log (staff only) --}}
            @if(auth()->user()->role !== 'admission')
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Audit Trail</h3>
                @forelse($grantRequest->auditLogs as $log)
                    <div class="flex items-start gap-3 mb-3 text-sm">
                        <span class="text-gray-400 w-32 shrink-0">
                            {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y') }}
                        </span>
                        <span class="font-semibold w-32 shrink-0">{{ $log->actor->name }}</span>
                        <span class="text-gray-600">
                            Status
                            {{ \App\Enums\RequestStatus::tryFrom((int) $log->from_status)?->getLabel() ?? $log->from_status }}
                            ->
                            {{ \App\Enums\RequestStatus::tryFrom((int) $log->to_status)?->getLabel() ?? $log->to_status }}
                            @if($log->note) · {{ $log->note }} @endif
                        </span>
                    </div>
                @empty
                    <p class="text-gray-400 italic text-sm">No audit trail yet.</p>
                @endforelse
            </div>
            @endif

            {{-- Back button --}}
            <div class="pb-6">
                <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 text-sm">&lt;- Back to Dashboard</a>
            </div>

        </div>
    </div>

    <script>
        const overrideActionSelect = document.getElementById('override-action-type');
        const reinstateDoubleConfirmation = document.getElementById('reinstate-double-confirmation');

        if (overrideActionSelect && reinstateDoubleConfirmation) {
            const toggleDoubleConfirmation = () => {
                if (overrideActionSelect.value === 'reject_reverse') {
                    reinstateDoubleConfirmation.classList.remove('hidden');
                } else {
                    reinstateDoubleConfirmation.classList.add('hidden');
                }
            };

            overrideActionSelect.addEventListener('change', toggleDoubleConfirmation);
            toggleDoubleConfirmation();
        }

        function handleFormSubmit(form, message) {
            const submitButtons = form.querySelectorAll('button[type="submit"]');
            submitButtons.forEach((btn) => {
                btn.disabled = true;
                btn.classList.add('opacity-70', 'cursor-not-allowed');
            });

            const active = document.activeElement;
            if (active && active.tagName && active.tagName.toLowerCase() === 'button' && active.form === form) {
                if (!active.dataset.originalText) {
                    active.dataset.originalText = active.textContent.trim();
                }
                active.textContent = message;
            }

            return true;
        }
    </script>
</x-app-layout>
