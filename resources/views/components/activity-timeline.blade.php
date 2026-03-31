<div class="flow-root">
    <ul role="list" class="-mb-8">
        @forelse ($activities as $index => $activity)
            <li>
                <div class="relative pb-8">
                    @if (!$loop->last)
                        <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                    @endif
                    
                    <div class="relative flex items-start space-x-3">
                        <!-- Activity Icon -->
                        <div class="relative">
                            @if ($activity['type'] === 'status_change')
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                    </svg>
                                </span>
                            @else
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
                                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                            @endif
                        </div>

                        <!-- Activity Content -->
                        <div class="min-w-0 flex-1">
                            <!-- Actor and Date -->
                            <div class="text-sm">
                                <span class="font-medium text-gray-900">{{ $activity['actor']->name }}</span>
                                @if ($activity['type'] === 'status_change')
                                    <span class="text-gray-500">changed status</span>
                                    @if ($activity['from_status'])
                                        from <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $activity['from_status']->getColor() }}">
                                            {{ $activity['from_status']->getLabel() }}
                                        </span>
                                    @endif
                                    to <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $activity['to_status']->getColor() }}">
                                        {{ $activity['to_status']->getLabel() }}
                                    </span>
                                @else
                                    <span class="text-gray-500">
                                        {{ $activity['is_internal'] ? 'added an internal comment' : 'added a comment' }}
                                    </span>
                                @endif
                                <span class="ml-2 text-gray-500">{{ $activity['date']->format('M j, Y g:i A') }}</span>
                            </div>

                            <!-- Activity Details -->
                            @if ($activity['type'] === 'status_change' && $activity['note'])
                                <div class="mt-2 text-sm text-gray-600">
                                    <p>{{ $activity['note'] }}</p>
                                </div>
                            @elseif ($activity['type'] === 'comment')
                                <div class="mt-2 text-sm text-gray-700 bg-gray-50 rounded-lg p-3">
                                    <p>{{ $activity['content'] }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </li>
        @empty
            <li>
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No activity yet</h3>
                    <p class="mt-1 text-sm text-gray-500">No status changes or comments have been recorded for this request.</p>
                </div>
            </li>
        @endforelse
    </ul>
</div>
