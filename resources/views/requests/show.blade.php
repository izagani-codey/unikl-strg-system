<x-app-layout>
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
                        <p class="text-gray-500">Amount Requested</p>
                        <p class="font-bold text-lg">RM {{ number_format($grantRequest->payload['amount'] ?? 0, 2) }}</p>
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
                            <div class="w-16 h-16 {{ $grantRequest->status_id >= 2 ? 'bg-green-500' : ($grantRequest->status_id == 1 ? 'bg-blue-500' : 'bg-gray-300') }} rounded-full flex items-center justify-center text-white">
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
                            <div class="w-16 h-16 {{ $grantRequest->status_id >= 5 ? 'bg-green-500' : ($grantRequest->status_id == 2 ? 'bg-blue-500' : 'bg-gray-300') }} rounded-full flex items-center justify-center text-white">
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
                                @if($grantRequest->status_id === 6)
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
                            <div class="w-16 h-16 {{ $grantRequest->status_id == 5 ? 'bg-green-500' : 'bg-gray-300' }} rounded-full flex items-center justify-center text-white">
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
                    $statusLabels = [
                        1 => 'Pending Verification',
                        2 => 'With Staff 2',
                        3 => 'Returned to Admission',
                        4 => 'Returned to Staff 1',
                        5 => 'Approved',
                        6 => 'Declined',
                    ];

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
                                    onclick="document.getElementById('s1-status').value='3'"
                                    class="bg-yellow-500 text-white px-5 py-2 rounded font-bold hover:bg-yellow-600">
                                    ↩ Return to Admission
                                </button>
                                <button type="submit"
                                    onclick="document.getElementById('s1-status').value='6'"
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
                            <input type="hidden" name="status_id" value="5" id="status2-input">
                            <div class="flex gap-3 flex-wrap">
                                <button type="submit"
                                    onclick="document.getElementById('status2-input').value='5'"
                                    class="bg-green-600 text-white px-6 py-2 rounded font-bold hover:bg-green-700">
                                    ✓ Approve & Finalise
                                </button>
                                
                                @if($grantRequest->status_id === 2)
                                    <button type="submit"
                                        onclick="document.getElementById('status2-input').value='4'"
                                        class="bg-yellow-500 text-white px-6 py-2 rounded font-bold hover:bg-yellow-600">
                                        ↩ Return to Staff 1
                                    </button>
                                @endif
                                
                                @if($grantRequest->status_id === 1)
                                    <button type="submit"
                                        onclick="document.getElementById('status2-input').value='2'"
                                        class="bg-blue-600 text-white px-6 py-2 rounded font-bold hover:bg-blue-700">
                                        ⚡ Override: Send to Staff 2 Review
                                    </button>
                                @endif
                                
                                <button type="submit"
                                    onclick="document.getElementById('status2-input').value='6'"
                                    class="bg-red-600 text-white px-6 py-2 rounded font-bold hover:bg-red-700">
                                    ✕ Decline
                                </button>
                            </div>
                        </form>
                        <p class="text-xs text-gray-400 mt-3 italic">Staff 2 override is active on all active requests and finalises the review.</p>
                    @endif
                @endcan

                {{-- No actions available --}}
                @if(
                    (auth()->user()->role === 'admission' && $grantRequest->status_id != 3) ||
                    (auth()->user()->role === 'staff1' && !in_array($grantRequest->status_id, [1, 4])) ||
                    (auth()->user()->role === 'staff2' && in_array($grantRequest->status_id, [5, 6], true))
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
                            Status {{ $log->from_status }} -> {{ $log->to_status }}
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