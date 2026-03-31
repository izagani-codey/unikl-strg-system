<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Verification Queue</h2>
            <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-full uppercase">Staff 1</span>
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
            @php
                $myQueue = ($dashboardStats['pending_verification'] ?? 0) + ($dashboardStats['returned_to_staff_1'] ?? 0);
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-orange-600">{{ $myQueue }}</p>
                    <p class="text-xs text-orange-500 mt-1">Needs My Action</p>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ $dashboardStats['with_staff_2'] ?? 0 }}</p>
                    <p class="text-xs text-blue-500 mt-1">With Staff 2</p>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ $dashboardStats['approved'] ?? 0 }}</p>
                    <p class="text-xs text-green-500 mt-1">Approved</p>
                </div>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-red-600">{{ $dashboardStats['declined'] ?? 0 }}</p>
                    <p class="text-xs text-red-500 mt-1">Declined</p>
                </div>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('dashboard') }}" class="bg-white shadow-sm rounded-lg p-4">
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
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="border rounded px-3 py-2 text-sm">
                    <div class="flex gap-2">
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="border rounded px-3 py-2 text-sm flex-1">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-blue-700">Filter</button>
                        <a href="{{ route('dashboard') }}" class="bg-gray-200 text-gray-700 px-3 py-2 rounded text-sm font-semibold">✕</a>
                    </div>
                </div>
            </form>

            {{-- Request table with tab filter --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex gap-4 mb-4 border-b">
                    <button onclick="filterTab('all')" id="tab-all"
                            class="pb-2 text-sm font-bold border-b-2 border-blue-600 text-blue-600">
                        All ({{ $dashboardStats['total'] ?? 0 }})
                    </button>
                    <button onclick="filterTab('action')" id="tab-action"
                            class="pb-2 text-sm font-semibold text-gray-400 hover:text-gray-600">
                        Needs Action ({{ $myQueue }})
                    </button>
                    <button onclick="filterTab('forwarded')" id="tab-forwarded"
                            class="pb-2 text-sm font-semibold text-gray-400 hover:text-gray-600">
                        With Staff 2 ({{ $dashboardStats['with_staff_2'] ?? 0 }})
                    </button>
                    <button onclick="filterTab('done')" id="tab-done"
                            class="pb-2 text-sm font-semibold text-gray-400 hover:text-gray-600">
                        Completed
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b">
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Ref Number</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Type</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Submitted By</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Amount</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Date</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Status</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($displayRequests as $req)
                                <tr class="border-b hover:bg-gray-50 request-row" data-status="{{ $req->status_id }}">
                                    <td class="px-4 py-3 font-mono text-xs">
                                        {{ $req->ref_number }}
                                        @if($req->is_priority)
                                            <span class="ml-1 px-1 py-0.5 bg-red-500 text-white text-[10px] rounded">!</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">{{ $req->requestType->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold">{{ $req->user->name }}</p>
                                        <p class="text-xs text-gray-400">{{ $req->user->email }}</p>
                                    </td>
                                    <td class="px-4 py-3 font-bold">RM {{ number_format($req->payload['amount'] ?? 0, 2) }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $req->created_at->format('d M Y') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold {{ $req->statusClass() }}">
                                            {{ $req->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('requests.show', $req->id) }}"
                                           class="px-3 py-1 bg-blue-600 text-white rounded text-xs font-bold hover:bg-blue-700">
                                            {{ in_array($req->status_id, [1, 4]) ? 'Review' : 'View' }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-400 italic">No requests found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $displayRequests->links() }}</div>
            </div>

        </div>
    </div>

    <script>
    function filterTab(filter) {
        document.querySelectorAll('[id^="tab-"]').forEach(t => {
            t.classList.remove('border-b-2', 'border-blue-600', 'text-blue-600');
            t.classList.add('text-gray-400');
        });
        const active = document.getElementById('tab-' + filter);
        active.classList.add('border-b-2', 'border-blue-600', 'text-blue-600');
        active.classList.remove('text-gray-400');

        document.querySelectorAll('.request-row').forEach(row => {
            const s = parseInt(row.dataset.status);
            const show = filter === 'all'
                || (filter === 'action'     && (s === 1 || s === 4))
                || (filter === 'forwarded'  && s === 2)
                || (filter === 'done'       && (s === 5 || s === 6));
            row.style.display = show ? '' : 'none';
        });
    }
    </script>
</x-app-layout>
