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
            @foreach($timelineSteps as $index => $step)
                @php
                    $stepStatus = $index < $currentStep ? 'completed' : ($index === $currentStep ? 'current' : 'pending');
                    $bgColor = $stepStatus === 'completed' ? 'bg-green-500' : ($stepStatus === 'current' ? 'bg-blue-500' : 'bg-gray-300');
                    $textColor = $stepStatus === 'completed' ? 'text-green-600' : ($stepStatus === 'current' ? 'text-blue-600' : 'text-gray-400');
                    $borderColor = $stepStatus === 'completed' ? 'border-green-500' : ($stepStatus === 'current' ? 'border-blue-500' : 'border-gray-300');
                @endphp
                
                <div class="flex items-start space-x-4">
                    <!-- Step Circle -->
                    <div class="flex-shrink-0 relative">
                        <div class="w-16 h-16 rounded-full {{ $bgColor }} flex items-center justify-center border-4 border-white shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}"/>
                            </svg>
                        </div>
                        @if($stepStatus === 'current')
                            <div class="absolute -top-1 -right-1 w-4 h-4 bg-blue-500 rounded-full animate-pulse border-2 border-white"></div>
                        @endif
                    </div>
                    
                    <!-- Step Content -->
                    <div class="flex-1 min-w-0 pb-8">
                        <div class="bg-gray-50 rounded-lg p-4 border-l-4 {{ $borderColor }}">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-bold text-gray-900">{{ $step['label'] }}</h4>
                                @if($stepStatus === 'completed')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Completed
                                    </span>
                                @elseif($stepStatus === 'current')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <svg class="w-3 h-3 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        In Progress
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                        Pending
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 mb-2">{{ $step['description'] }}</p>
                            
                            <!-- Show actual completion info if completed -->
                            @if($stepStatus === 'completed' && $step['id'] === 'verified' && $request->verifiedBy)
                                <div class="text-xs text-gray-500 mt-2">
                                    <span class="font-medium">Verified by:</span> {{ $request->verifiedBy->name }}
                                    @if($request->auditLogs()->where('to_status', RequestStatus::PENDING_RECOMMENDATION)->first())
                                        <span class="ml-2">on {{ $request->auditLogs()->where('to_status', RequestStatus::PENDING_RECOMMENDATION)->first()->created_at->format('d M Y, h:i A') }}</span>
                                    @endif
                                </div>
                            @elseif($stepStatus === 'completed' && $step['id'] === 'recommended' && $request->recommendedBy)
                                <div class="text-xs text-gray-500 mt-2">
                                    <span class="font-medium">Recommended by:</span> {{ $request->recommendedBy->name }}
                                    @if($request->auditLogs()->where('to_status', RequestStatus::APPROVED)->first())
                                        <span class="ml-2">on {{ $request->auditLogs()->where('to_status', RequestStatus::APPROVED)->first()->created_at->format('d M Y, h:i A') }}</span>
                                    @endif
                                </div>
                            @elseif($stepStatus === 'completed' && $step['id'] === 'submitted')
                                <div class="text-xs text-gray-500 mt-2">
                                    <span class="font-medium">Submitted by:</span> {{ $request->user->name }}
                                    <span class="ml-2">on {{ $request->created_at->format('d M Y, h:i A') }}</span>
                                </div>
                            @endif
                            
                            <!-- Show rejection info if declined -->
                            @if($request->status_id === RequestStatus::DECLINED->value && $step['id'] === 'recommended')
                                <div class="mt-2 p-2 bg-red-50 rounded border border-red-200">
                                    <p class="text-sm text-red-800">
                                        <span class="font-medium">Declined:</span> 
                                        @if($request->rejection_reason)
                                            {{ $request->rejection_reason }}
                                        @else
                                            No reason provided
                                        @endif
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    
    <!-- Summary -->
    <div class="mt-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-blue-800">
                <span class="font-medium">Current Status:</span> {{ $request->statusLabel() }}
                @if($request->deadline && $request->isUrgent())
                    <span class="ml-2 text-red-600 font-medium">⚠️ URGENT - Deadline: {{ $request->deadline->format('d M Y') }}</span>
                @endif
            </div>
        </div>
    </div>
</div>
