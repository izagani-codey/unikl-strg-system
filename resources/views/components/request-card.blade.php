@props([
    'request',
    'showActions' => true,
    'compact' => false,
])

@php
    $statusColors = [
        1 => 'bg-blue-100 text-blue-800', // Pending Verification
        2 => 'bg-yellow-100 text-yellow-800', // Pending Recommendation
        3 => 'bg-purple-100 text-purple-800', // Pending Dean Approval
        4 => 'bg-indigo-100 text-indigo-800', // Pending Dean Verification
        5 => 'bg-green-100 text-green-800', // Returned to Admission
        6 => 'bg-orange-100 text-orange-800', // Returned to Staff 1
        7 => 'bg-red-100 text-red-800', // Returned to Staff 2
        8 => 'bg-emerald-100 text-emerald-800', // Approved
        9 => 'bg-rose-100 text-rose-800', // Declined
    ];
    
    $priorityColors = [
        'low' => 'bg-gray-100 text-gray-600',
        'medium' => 'bg-blue-100 text-blue-600',
        'high' => 'bg-orange-100 text-orange-600',
        'urgent' => 'bg-red-100 text-red-600',
    ];
@endphp

<div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-all duration-200 {{ $compact ? 'p-3' : 'p-4' }}">
    <!-- Header -->
    <div class="flex justify-between items-start mb-3">
        <div class="flex-1 min-w-0">
            <h3 class="text-sm font-medium text-gray-900 truncate {{ $compact ? 'text-xs' : '' }}">
                {{ $request->ref_number }}
            </h3>
            @if(!$compact)
                <p class="text-xs text-gray-500 mt-1 truncate">
                    {{ $request->title }}
                </p>
            @endif
        </div>
        <div class="flex flex-col items-end ml-3 space-y-1">
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$request->status_id] ?? 'bg-gray-100 text-gray-800' }}">
                {{ $request->statusLabel() }}
            </span>
            @if($request->priority && $request->priority !== 'low')
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $priorityColors[$request->priority] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ $request->priority }}
                </span>
            @endif
        </div>
    </div>

    <!-- Main Content -->
    @if(!$compact)
        <div class="space-y-2 mb-3">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Type:</span>
                <span class="text-gray-900">{{ $request->requestType->name ?? 'N/A' }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Amount:</span>
                <span class="text-gray-900 font-medium">RM{{ number_format($request->total_amount, 2) }}</span>
            </div>
            @if($request->deadline)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Deadline:</span>
                    <span class="text-gray-900 {{ $request->isOverdue() ? 'text-red-600 font-medium' : '' }}">
                        {{ $request->deadline->format('M d, Y') }}
                        {{ $request->isOverdue() ? '(Overdue)' : '' }}
                    </span>
                </div>
            @endif
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Submitted:</span>
                <span class="text-gray-900">{{ $request->created_at->format('M d, Y') }}</span>
            </div>
        </div>
    @endif

    <!-- Mobile Optimized Info -->
    <div class="lg:hidden space-y-1 text-xs">
        <div class="flex justify-between">
            <span class="text-gray-500">RM{{ number_format($request->total_amount, 2) }}</span>
            <span class="text-gray-500">{{ $request->requestType->name ?? 'N/A' }}</span>
        </div>
        @if($request->deadline && !$compact)
            <div class="flex justify-between">
                <span class="text-gray-500">Due: {{ $request->deadline->format('M d') }}</span>
                <span class="{{ $request->isOverdue() ? 'text-red-600' : 'text-gray-500' }}">
                    {{ $request->getDaysUntilDeadline() }} days
                </span>
            </div>
        @endif
    </div>

    <!-- Actions -->
    @if($showActions)
        <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-100">
            <div class="text-xs text-gray-500">
                by {{ $request->user->name ?? 'Unknown' }}
            </div>
            <div class="flex space-x-2">
                {{ $slot }}
            </div>
        </div>
    @endif
</div>
