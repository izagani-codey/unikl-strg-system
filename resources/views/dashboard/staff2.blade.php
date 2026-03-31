<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Recommendation Queue</h2>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 bg-purple-100 text-purple-800 text-xs font-bold rounded-full uppercase">Staff 2</span>
                <a href="{{ route('staff2.admin') }}" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-bold hover:bg-blue-700">
                    Admin Panel
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(app()->environment('local') && Route::has('dev.login'))
                @include('dashboard._dev-switcher')
            @endif

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            {{-- Deadline reminders --}}
            @if($urgentRequests->count() > 0)
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <h3 class="font-bold text-red-700 mb-2">⏰ Deadlines within 3 days</h3>
                    <div class="space-y-2">
                        @foreach($urgentRequests as $urgent)
                            <a href="{{ route('requests.show', $urgent->id) }}"
                               class="flex items-center justify-between p-2 bg-white border rounded hover:bg-red-50">
                                <span class="text-sm font-semibold text-gray-700">{{ $urgent->ref_number }} · {{ $urgent->requestType->name ?? 'N/A' }}</span>
                                <span class="text-xs font-bold text-red-600">Due {{ $urgent->deadline?->format('d M Y') }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ $dashboardStats['with_staff_2'] ?? 0 }}</p>
                    <p class="text-xs text-blue-500 mt-1">Awaiting My Review</p>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ $dashboardStats['approved'] ?? 0 }}</p>
                    <p class="text-xs text-green-500 mt-1">Approved</p>
                </div>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-red-600">{{ $dashboardStats['declined'] ?? 0 }}</p>
                    <p class="text-xs text-red-500 mt-1">Declined</p>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-600">{{ $dashboardStats['total'] ?? 0 }}</p>
                    <p class="text-xs text-gray-500 mt-1">Total Requests</p>
                </div>
            </div>

            {{-- Filters + export --}}
            <div class="bg-white shadow-sm rounded-lg p-4 space-y-3">
                <form method="GET" action="{{ route('dashboard') }}">
                    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Search ref / name / email"
                               class="border rounded px-3 py-2 text-sm col-span-2">
                        <select name="status" class="border rounded px-3 py-2 text-sm">
                            <option value="">All Statuses</option>
                            <option value="1" @selected(request('status') == 1)>Pending Verification</option>
                            <option value="2" @selected(request('status') == 2)>With Staff 2</option>
                            <option value="3" @selected(request('status') == 3)>Returned to Admission</option>
                            <option value="4" @selected(request('status') == 4)>Returned to Staff 1</option>
                            <option value="5" @selected(request('status') == 5)>Approved</option>
                            <option value="6" @selected(request('status') == 6)>Declined</option>
                        </select>
                        <select name="type" class="border rounded px-3 py-2 text-sm">
                            <option value="">All Types</option>
                            @foreach($requestTypes as $type)
                                <option value="{{ $type->id }}" @selected(request('type') == $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <select name="priority" class="border rounded px-3 py-2 text-sm">
                            <option value="">All Priority</option>
                            <option value="1" @selected(request('priority') === '1')>High Priority</option>
                            <option value="0" @selected(request('priority') === '0')>Normal</option>
                        </select>
                        <div class="flex gap-2">
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-bold hover:bg-blue-700">Filter</button>
                            <a href="{{ route('dashboard') }}" class="bg-gray-200 text-gray-600 px-3 py-2 rounded text-sm font-bold">✕</a>
                        </div>
                    </div>
                </form>
                <div class="flex justify-end">
                    <a href="{{ route('requests.exportCsv') . '?' . http_build_query(request()->query()) }}"
                       class="text-sm px-3 py-2 rounded-md bg-slate-800 text-white hover:bg-slate-700">
                        Export CSV
                    </a>
                </div>
            </div>

            {{-- Evaluation queue table --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">⚖️ All Requests</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b">
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Ref Number</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Type</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Priority</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Submitted By</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Amount</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Verified By</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Status</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($displayRequests as $req)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono text-xs">{{ $req->ref_number }}</td>
                                    <td class="px-4 py-3">{{ $req->requestType->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold {{ $req->priorityBadgeClass() }}">
                                            {{ $req->priorityLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold">{{ $req->user->name }}</p>
                                        <p class="text-xs text-gray-400">{{ $req->user->email }}</p>
                                    </td>
                                    <td class="px-4 py-3 font-bold">RM {{ number_format($req->payload['amount'] ?? 0, 2) }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ $req->verifiedBy?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold {{ $req->statusClass() }}">
                                            {{ $req->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('requests.show', $req->id) }}"
                                           class="px-3 py-1 bg-purple-600 text-white rounded text-xs font-bold hover:bg-purple-700">
                                            {{ $req->status_id === 2 ? 'Review' : 'View' }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-400 italic">No requests found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $displayRequests->links() }}</div>
            </div>

        </div>
    </div>
</x-app-layout>
