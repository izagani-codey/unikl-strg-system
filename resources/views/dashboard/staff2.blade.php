<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-green-600 to-teal-600 bg-clip-text text-transparent">Recommendation Dashboard</h1>
                <p class="text-gray-600 mt-1">Review and approve grant applications</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('staff2.admin') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-semibold rounded-lg hover:bg-gray-900 transition-colors shadow-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Admin Panel
                </a>
                <span class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-500 to-teal-500 text-white text-sm font-semibold rounded-full shadow-lg">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                    Staff 2
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            @if(app()->environment('local') && Route::has('dev.login'))
                @include('dashboard._dev-switcher')
            @endif

            @if(session('success'))
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 text-green-800 px-6 py-4 rounded-xl shadow-sm flex items-center">
                    <svg class="w-5 h-5 mr-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            {{-- Urgent Deadlines Alert --}}
            @if($urgentRequests->count() > 0)
                <div class="bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 rounded-2xl p-6 shadow-lg">
                    <div class="flex items-start">
                        <div class="bg-red-100 rounded-full p-3 mr-4">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-red-900 mb-2">⏰ Urgent: Deadlines within 3 days</h3>
                            <p class="text-red-700 text-sm mb-4">These requests require immediate attention:</p>
                            <div class="space-y-2">
                                @foreach($urgentRequests as $urgent)
                                    <a href="{{ route('requests.show', $urgent->id) }}"
                                       class="flex items-center justify-between p-3 bg-white rounded-lg border border-red-200 hover:bg-red-50 transition-colors">
                                        <div class="flex items-center space-x-3">
                                            <span class="font-semibold text-gray-900">{{ $urgent->ref_number }}</span>
                                            <span class="text-sm text-gray-600">{{ $urgent->requestType->name }}</span>
                                            <span class="text-sm text-gray-500">from {{ $urgent->user->name }}</span>
                                        </div>
                                        <span class="text-sm font-bold text-red-600 bg-red-100 px-2 py-1 rounded">
                                            Due {{ $urgent->deadline?->format('M j') }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Welcome Section with Stats --}}
            <div class="bg-gradient-to-br from-green-600 via-teal-600 to-cyan-600 rounded-2xl p-8 text-white shadow-2xl">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div class="mb-6 md:mb-0">
                        <h2 class="text-3xl font-bold mb-2">Welcome back, {{ auth()->user()->name }}! 👋</h2>
                        <p class="text-green-100 text-lg">{{ auth()->user()->email }}</p>
                        <div class="mt-4 flex flex-wrap gap-4">
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                                <div class="text-2xl font-bold">{{ $dashboardStats['with_staff_2'] }}</div>
                                <div class="text-sm text-green-100">Awaiting Review</div>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                                <div class="text-2xl font-bold">{{ $dashboardStats['approved'] }}</div>
                                <div class="text-sm text-green-100">Approved</div>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                                <div class="text-2xl font-bold">{{ $dashboardStats['declined'] }}</div>
                                <div class="text-sm text-green-100">Declined</div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-green-100 mb-2">Queue Status</div>
                        @if($dashboardStats['with_staff_2'] > 0)
                            <div class="bg-yellow-400 text-yellow-900 px-4 py-2 rounded-lg font-bold">
                                {{ $dashboardStats['with_staff_2'] }} pending review
                            </div>
                        @else
                            <div class="bg-green-400 text-green-900 px-4 py-2 rounded-lg font-bold">
                                All caught up! 🎉
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Status Overview Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Awaiting Review</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $dashboardStats['with_staff_2'] }}</p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Approved</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $dashboardStats['approved'] }}</p>
                        </div>
                        <div class="bg-green-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Declined</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $dashboardStats['declined'] }}</p>
                        </div>
                        <div class="bg-red-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Processed</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ ($dashboardStats['approved'] ?? 0) + ($dashboardStats['declined'] ?? 0) }}</p>
                        </div>
                        <div class="bg-purple-100 rounded-full p-3">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Deadline Reminder Widget --}}
            @if($urgentRequests->count() > 0)
                <div class="bg-gradient-to-r from-red-500 to-orange-500 rounded-2xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="text-xl font-bold">⚠️ Urgent Deadlines</h3>
                        </div>
                        <span class="bg-white/20 backdrop-blur-sm rounded-full px-3 py-1 text-sm font-bold">
                            {{ $urgentRequests->count() }} requests
                        </span>
                    </div>
                    
                    <div class="space-y-3">
                        @foreach($urgentRequests->take(3) as $urgent)
                            @php
                                $daysLeft = $urgent->daysUntilDeadline();
                                $urgencyColor = $daysLeft <= 1 ? 'bg-white/30' : 'bg-white/20';
                            @endphp
                            <div class="{{ $urgencyColor }} backdrop-blur-sm rounded-lg p-3 border border-white/20">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold">{{ $urgent->ref_number }}</p>
                                        <p class="text-sm text-red-100">{{ $urgent->user->name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold">
                                            @if($daysLeft < 0)
                                                OVERDUE
                                            @elseif($daysLeft === 0)
                                                DUE TODAY
                                            @elseif($daysLeft === 1)
                                                TOMORROW
                                            @else
                                                {{ $daysLeft }} days
                                            @endif
                                        </p>
                                        <p class="text-xs text-red-100">{{ $urgent->deadline->format('M j') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    @if($urgentRequests->count() > 3)
                        <div class="mt-3 text-center">
                            <a href="{{ route('dashboard') }}?urgent=1" class="text-white/90 hover:text-white text-sm font-medium underline">
                                View all {{ $urgentRequests->count() }} urgent requests →
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            <x-dashboard-filters 
                role="staff2" 
                :request-types="$requestTypes"
                title="Filter Recommendation Queue"
                description="Find requests to review and approve"
                color-theme="green"
            />

            {{-- Enhanced Export Tools --}}
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export & Reports
                        </h3>
                        <p class="text-gray-600 text-sm mt-1">Download data for analysis and university reporting</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Quick Export -->
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4">
                        <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Quick CSV Export
                        </h4>
                        <form method="GET" action="{{ route('requests.exportExcel') }}" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date Range (Optional)</label>
                                <div class="flex space-x-2">
                                    <input type="date" name="date_from" value="{{ request('date_from') }}" 
                                           placeholder="From" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-green-500 focus:border-green-500">
                                    <input type="date" name="date_to" value="{{ request('date_to') }}" 
                                           placeholder="To" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-green-500 focus:border-green-500">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2">
                                <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-green-500 focus:border-green-500">
                                    <option value="">All Statuses</option>
                                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Pending Verification</option>
                                    <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>With Staff 2</option>
                                    <option value="5" {{ request('status') == '5' ? 'selected' : '' }}>Approved</option>
                                    <option value="6" {{ request('status') == '6' ? 'selected' : '' }}>Declined</option>
                                </select>
                                <select name="type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-green-500 focus:border-green-500">
                                    <option value="">All Types</option>
                                    @foreach($requestTypes as $type)
                                        <option value="{{ $type->id }}" {{ request('type') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <button type="submit" 
                                    class="w-full bg-gradient-to-r from-green-600 to-emerald-600 text-white px-4 py-3 rounded-lg font-semibold hover:from-green-700 hover:to-emerald-700 transition-all shadow-lg">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Export Filtered Data
                            </button>
                        </form>
                    </div>
                    
                    <!-- Admin Panel Access -->
                    <div class="bg-gradient-to-br from-purple-50 to-indigo-50 border border-purple-200 rounded-xl p-4">
                        <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Admin Functions
                        </h4>
                        <div class="space-y-3">
                            <a href="{{ route('staff2.admin') }}" 
                               class="block w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-4 py-3 rounded-lg font-semibold hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg text-center">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                Admin Panel
                            </a>
                            <a href="{{ route('form-templates.index') }}" 
                               class="block w-full bg-gradient-to-r from-pink-600 to-rose-600 text-white px-4 py-3 rounded-lg font-semibold hover:from-pink-700 hover:to-rose-700 transition-all shadow-lg text-center">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                Manage Templates
                            </a>
                            <div class="text-center text-sm text-gray-600 mt-2">
                                <p>Full system management and reporting capabilities</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Export Info -->
                <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm text-blue-800">
                            <p class="font-semibold mb-1">Export Information:</p>
                            <ul class="list-disc list-inside space-y-1 text-blue-700">
                                <li>CSV format compatible with Excel and spreadsheet applications</li>
                                <li>Includes all request details, verification trail, and staff assignments</li>
                                <li>Respects current dashboard filters for targeted exports</li>
                                <li>Perfect for university reporting and data analysis</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recommendation Queue Table --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900">Recommendation Queue</h3>
                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                        <span>{{ $dashboardStats['with_staff_2'] }} pending</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verified By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($displayRequests as $request)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $request->ref_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $request->user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $request->user->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $request->requestType->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        RM {{ number_format($request->payload['amount'] ?? 0, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        @if($request->verifiedBy)
                                            <div class="text-sm font-medium text-gray-900">{{ $request->verifiedBy->name }}</div>
                                            <div class="text-xs text-gray-500">Staff 1</div>
                                        @else
                                            <span class="text-gray-400">Not verified</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $request->priorityBadgeClass() }}">
                                            {{ $request->priorityLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        @if($request->deadline)
                                            <div class="flex items-center">
                                                {{ $request->deadline->format('M j, Y') }}
                                                @if($request->isUrgent())
                                                    <span class="ml-2 text-xs text-red-600 font-bold">⚠️</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-gray-400">No deadline</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('requests.show', $request->id) }}" 
                                           class="text-green-600 hover:text-green-900 transition-colors">
                                            {{ $request->status_id === 2 ? 'Review Request' : 'View Details' }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        <h3 class="text-lg font-medium text-gray-900 mb-2">No requests in queue</h3>
                                        <p class="text-gray-600">All caught up! New requests will appear here.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($displayRequests->hasPages())
                    <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="flex-1 flex justify-between sm:hidden">
                            {{ $displayRequests->links() }}
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ $displayRequests->firstItem() }}</span>
                                    to
                                    <span class="font-medium">{{ $displayRequests->lastItem() }}</span>
                                    of
                                    <span class="font-medium">{{ $displayRequests->total() }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                {{ $displayRequests->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
