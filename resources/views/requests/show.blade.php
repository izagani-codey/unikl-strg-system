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

            {{-- Workflow Timeline --}}
            @php
                $current = (int) $grantRequest->status_id;
                $timeline = [
                    ['id' => 1, 'label' => 'Submitted'],
                    ['id' => 2, 'label' => 'Staff 1 Review'],
                    ['id' => 3, 'label' => 'Staff 2 Review'],
                    ['id' => 4, 'label' => 'Decision'],
                ];

                $stepState = fn (int $stepId) => match ($current) {
                    1 => $stepId === 1 ? 'current' : 'pending',
                    2 => in_array($stepId, [1, 2, 3], true) ? ($stepId === 3 ? 'current' : 'done') : 'pending',
                    3 => $stepId === 1 ? 'done' : ($stepId === 2 ? 'current' : 'pending'),
                    4 => in_array($stepId, [1, 2, 3], true) ? ($stepId === 2 ? 'current' : 'done') : 'pending',
                    5, 6 => $stepId < 4 ? 'done' : 'current',
                    default => 'pending',
                };
            @endphp
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Workflow Timeline</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach($timeline as $step)
                        @php($state = $stepState($step['id']))
                        <div class="p-3 rounded border {{ $state === 'done' ? 'bg-green-50 border-green-200' : ($state === 'current' ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200') }}">
                            <p class="text-xs font-bold {{ $state === 'done' ? 'text-green-700' : ($state === 'current' ? 'text-blue-700' : 'text-gray-500') }}">{{ $step['label'] }}</p>
                            <p class="text-[11px] mt-1 {{ $state === 'done' ? 'text-green-600' : ($state === 'current' ? 'text-blue-600' : 'text-gray-400') }}">
                                {{ $state === 'done' ? 'Completed' : ($state === 'current' ? 'In Progress' : 'Pending') }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

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

            {{-- WORKFLOW ACTION BUTTONS --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Actions</h3>

                {{-- ADMISSION: Edit if returned --}}
                @if(auth()->user()->role === 'admission' && $grantRequest->status_id == 3)
                    <a href="{{ route('requests.edit', $grantRequest->id) }}"
                       class="inline-block bg-yellow-500 text-white px-6 py-2 rounded font-bold hover:bg-yellow-600">
                        ✏ Edit & Resubmit
                    </a>
                @endif

                {{-- STAFF 1: Verify or Return to Admission --}}
                @if(auth()->user()->role === 'staff1' && in_array($grantRequest->status_id, [1, 4]))
                    <form action="{{ route('requests.updateStatus', $grantRequest->id) }}" method="POST" class="space-y-3">
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

                {{-- STAFF 2: Approve, Return to Staff 1, or Decline --}}
                @if(auth()->user()->role === 'staff2' && $grantRequest->status_id == 2)
                    <form action="{{ route('requests.updateStatus', $grantRequest->id) }}" method="POST" class="space-y-3">
                        @csrf
                        @method('PATCH')
                        <textarea name="notes" rows="2" placeholder="Recommendation notes (optional)"
                            class="w-full border rounded p-2 text-sm"></textarea>
                        <textarea name="rejection_reason" rows="2"
                            placeholder="Reason (required for Return or Decline)"
                            class="w-full border rounded p-2 text-sm"></textarea>
                        <input type="hidden" name="status_id" value="5" id="status2-input">
                        <div class="flex gap-3">
                            <button type="submit"
                                onclick="document.getElementById('status2-input').value='5'"
                                class="bg-green-600 text-white px-6 py-2 rounded font-bold hover:bg-green-700">
                                ✓ Approve & Finalise
                            </button>
                            <button type="submit"
                                onclick="document.getElementById('status2-input').value='4'"
                                class="bg-yellow-500 text-white px-6 py-2 rounded font-bold hover:bg-yellow-600">
                                ↩ Return to Staff 1
                            </button>
                            <button type="submit"
                                onclick="document.getElementById('status2-input').value='6'"
                                class="bg-red-600 text-white px-6 py-2 rounded font-bold hover:bg-red-700">
                                ✕ Decline
                            </button>
                        </div>
                    </form>
                @endif

                {{-- STAFF 2 OVERRIDE: can action any non-finalised request --}}
                @if(auth()->user()->role === 'staff2' && !in_array($grantRequest->status_id, [5, 6]))
                    <p class="text-xs text-gray-400 mt-3 italic">Staff 2 override available for all active requests.</p>
                @endif

                {{-- No actions available --}}
                @if(
                    (auth()->user()->role === 'admission' && $grantRequest->status_id != 3) ||
                    (auth()->user()->role === 'staff1' && !in_array($grantRequest->status_id, [1, 4])) ||
                    (auth()->user()->role === 'staff2' && $grantRequest->status_id == 5)
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
                        <p class="text-sm">{{ $comment->body }}</p>
                    </div>
                @empty
                    <p class="text-gray-400 italic text-sm">No comments yet.</p>
                @endforelse

                @if(auth()->user()->role === 'staff2')
                <form action="{{ route('requests.comment', $grantRequest->id) }}" method="POST" class="mt-4">
                    @csrf
                    <textarea name="body" rows="2" placeholder="Leave a comment for Staff 1..."
                        class="w-full border rounded p-2 text-sm"></textarea>
                    <button type="submit"
                        class="mt-2 bg-gray-700 text-white px-4 py-2 rounded text-sm font-bold hover:bg-gray-800">
                        Post Comment
                    </button>
                </form>
                @endif
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
                            Status {{ $log->from_status }} → {{ $log->to_status }}
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
                <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 text-sm">
                    ← Back to Dashboard
                </a>
            </div>

        </div>
    </div>
</x-app-layout>