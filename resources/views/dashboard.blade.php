<x-app-layout>
    @php
        $formTemplates = $formTemplates ?? collect();
    @endphp
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('ServiceLink Approval System') }}
            </h2>
            <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-full uppercase">
                Role: {{ auth()->user()->role }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Dev Quick-Switch (local only) --}}
            @if(app()->environment('local') && Route::has('dev.login'))
                <div class="bg-gray-900 p-4 rounded-lg shadow-lg">
                    <h4 class="text-white text-xs font-bold uppercase tracking-wider mb-3">Developer Quick Switch</h4>
                    <div class="flex flex-wrap gap-3">
                        <form action="{{ route('dev.login') }}" method="POST">
                            @csrf
                            <input type="hidden" name="email" value="admission@unikl.edu.my">
                            <button type="submit" class="bg-gray-600 hover:bg-gray-500 text-white px-3 py-1 rounded text-xs">Become: Admission</button>
                        </form>
                        <form action="{{ route('dev.login') }}" method="POST">
                            @csrf
                            <input type="hidden" name="email" value="staff1@unikl.edu.my">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1 rounded text-xs">Become: Staff 1</button>
                        </form>
                        <form action="{{ route('dev.login') }}" method="POST">
                            @csrf
                            <input type="hidden" name="email" value="staff2@unikl.edu.my">
                            <button type="submit" class="bg-purple-600 hover:bg-purple-500 text-white px-3 py-1 rounded text-xs">Become: Staff 2</button>
                        </form>
                    </div>
                    <p class="text-gray-400 text-[10px] mt-2 italic">Local development helper only.</p>
                </div>
            @endif

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(($urgentRequests ?? collect())->count() > 0)
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <h3 class="font-bold text-red-700 mb-2">⏰ Deadline Reminder (next 3 days)</h3>
                    <div class="space-y-2">
                        @foreach($urgentRequests as $urgent)
                            <a href="{{ route('requests.show', $urgent->id) }}" class="flex items-center justify-between p-2 bg-white border rounded hover:bg-red-50">
                                <span class="text-sm font-semibold text-gray-700">{{ $urgent->ref_number }} · {{ $urgent->requestType->name ?? 'N/A' }}</span>
                                <span class="text-xs font-bold text-red-600">Due {{ optional($urgent->deadline)->format('d M Y') }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ================================ --}}
            {{-- ADMISSION DASHBOARD --}}
            {{-- ================================ --}}
            @if(auth()->user()->role === 'admission')

                {{-- Welcome Card --}}
                <div class="bg-blue-600 text-white rounded-lg p-6 shadow">
                    <h3 class="text-xl font-bold">Welcome, {{ auth()->user()->name }} 👋</h3>
                    <p class="text-blue-100 text-sm mt-1">{{ auth()->user()->email }}</p>
                    <div class="mt-4 flex gap-3">
                        <a href="{{ route('requests.create') }}"
                           class="bg-white text-blue-600 font-bold px-5 py-2 rounded hover:bg-blue-50 text-sm">
                            + Submit New Request
                        </a>
                    </div>
                </div>

                {{-- Blank Forms Download --}}
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4 border-b pb-2">
                        <h3 class="font-bold text-lg">📄 Blank Forms & Templates</h3>
                    </div>
                    <p class="text-sm text-gray-500 mb-4">Download, fill, and upload these forms with your request.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @forelse($formTemplates as $template)
                            <div class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50">
                                <span class="text-sm font-semibold text-gray-700">📎 {{ $template->title }}</span>
                                <a href="{{ asset('storage/' . $template->file_path) }}" target="_blank" class="text-xs font-bold text-blue-600 hover:underline">Download</a>
                            </div>
                        @empty
                            <div class="p-4 border border-dashed rounded text-sm text-gray-500">No templates uploaded yet. Ask Staff 2 to upload blank forms.</div>
                        @endforelse
                    </div>
                </div>

                {{-- Status Summary --}}
                @php
                    $pending = ($dashboardStats['pending_verification'] ?? 0) + ($dashboardStats['with_staff_2'] ?? 0);
                    $returned = $dashboardStats['returned_to_admission'] ?? 0;
                    $approved = $dashboardStats['approved'] ?? 0;
                    $declined = $dashboardStats['declined'] ?? 0;
                @endphp
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-orange-600">{{ $pending }}</p>
                        <p class="text-xs text-orange-500 mt-1">In Review</p>
                    </div>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-yellow-600">{{ $returned }}</p>
                        <p class="text-xs text-yellow-500 mt-1">Needs Revision</p>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-green-600">{{ $approved }}</p>
                        <p class="text-xs text-green-500 mt-1">Approved</p>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-red-600">{{ $declined }}</p>
                        <p class="text-xs text-red-500 mt-1">Declined</p>
                    </div>
                </div>
{{-- Filter Bar --}}
<form method="GET" action="{{ route('dashboard') }}" class="mb-4">
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">

        {{-- Search --}}
        <input type="text"
               name="search"
               value="{{ request('search') }}"
               placeholder="Search ref or name..."
               class="border rounded px-3 py-2 text-sm col-span-2 md:col-span-1">

        {{-- Status --}}
        <select name="status" class="border rounded px-3 py-2 text-sm">
            <option value="">All Statuses</option>
            <option value="1" {{ request('status') == 1 ? 'selected' : '' }}>Pending Verification</option>
            <option value="2" {{ request('status') == 2 ? 'selected' : '' }}>With Staff 2</option>
            <option value="3" {{ request('status') == 3 ? 'selected' : '' }}>Returned to Admission</option>
            <option value="4" {{ request('status') == 4 ? 'selected' : '' }}>Returned to Staff 1</option>
            <option value="5" {{ request('status') == 5 ? 'selected' : '' }}>Approved</option>
            <option value="6" {{ request('status') == 6 ? 'selected' : '' }}>Declined</option>
        </select>

        {{-- Type --}}
        <select name="type" class="border rounded px-3 py-2 text-sm">
            <option value="">All Types</option>
            @foreach($requestTypes as $type)
                <option value="{{ $type->id }}" {{ request('type') == $type->id ? 'selected' : '' }}>
                    {{ $type->name }}
                </option>
            @endforeach
        </select>

        {{-- Date From --}}
        <input type="date"
               name="date_from"
               value="{{ request('date_from') }}"
               class="border rounded px-3 py-2 text-sm">

        {{-- Date To + Submit --}}
        <div class="flex gap-2">
            <input type="date"
                   name="date_to"
                   value="{{ request('date_to') }}"
                   class="border rounded px-3 py-2 text-sm flex-1">
            <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-bold hover:bg-blue-700">
                Filter
            </button>
            <a href="{{ route('dashboard') }}"
               class="bg-gray-200 text-gray-600 px-3 py-2 rounded text-sm font-bold hover:bg-gray-300">
                ✕
            </a>
        </div>

    </div>
</form>
                {{-- My Requests Table --}}
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-bold text-lg mb-4 border-b pb-2">📥 My Submissions</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 border-b">
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Ref Number</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Type</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Amount</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Submitted</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Status</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($displayRequests as $req)
                                <tr class="border-b hover:bg-gray-50 {{ $req->status_id == 3 ? 'bg-yellow-50' : '' }}">
                                    <td class="px-4 py-3 font-mono text-xs">{{ $req->ref_number }}</td>
                                    <td class="px-4 py-3">{{ $req->requestType->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 font-bold">RM {{ number_format($req->payload['amount'] ?? 0, 2) }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $req->created_at->format('d M Y') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold {{ $req->statusClass() }}">
                                            {{ $req->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            <a href="{{ route('requests.show', $req->id) }}"
                                               class="px-3 py-1 bg-blue-600 text-white rounded text-xs font-bold hover:bg-blue-700">
                                                View
                                            </a>
                                            @if($req->status_id == 3)
                                            <a href="{{ route('requests.edit', $req->id) }}"
                                               class="px-3 py-1 bg-yellow-500 text-white rounded text-xs font-bold hover:bg-yellow-600">
                                                ✏ Revise
                                            </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                {{-- Show rejection reason inline --}}
                                @if($req->rejection_reason)
                                <tr class="bg-red-50">
                                    <td colspan="6" class="px-4 py-2 text-xs text-red-600">
                                        ⚠ <span class="font-bold">Reason:</span> {{ $req->rejection_reason }}
                                    </td>
                                </tr>
                                @endif
                                @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400 italic">
                                        No submissions yet.
                                        <a href="{{ route('requests.create') }}" class="text-blue-600 hover:underline ml-1">Submit your first request →</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4">{{ $displayRequests->links() }}</div>

            {{-- ================================ --}}
            {{-- STAFF 1 DASHBOARD --}}
            {{-- ================================ --}}
          @elseif(auth()->user()->role === 'staff1')

    {{-- Summary Stats --}}
    @php
        $myQueueCount = ($dashboardStats['pending_verification'] ?? 0) + ($dashboardStats['returned_to_staff_1'] ?? 0);
        $withStaff2Count = $dashboardStats['with_staff_2'] ?? 0;
        $approved = $dashboardStats['approved'] ?? 0;
        $declined = $dashboardStats['declined'] ?? 0;
        $total = $dashboardStats['total'] ?? 0;
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-orange-600">{{ $myQueueCount }}</p>
            <p class="text-xs text-orange-500 mt-1">Needs My Action</p>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $withStaff2Count }}</p>
            <p class="text-xs text-blue-500 mt-1">With Staff 2</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ $approved }}</p>
            <p class="text-xs text-green-500 mt-1">Approved</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-red-600">{{ $declined }}</p>
            <p class="text-xs text-red-500 mt-1">Declined</p>
        </div>
    </div>

    <form method="GET" action="{{ route('dashboard') }}" class="bg-white shadow-sm rounded-lg p-4">
        <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search ref / name / email" class="border rounded px-3 py-2 text-sm col-span-2 md:col-span-2">
            <select name="status" class="border rounded px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="1" {{ request('status') == 1 ? 'selected' : '' }}>Pending Verification</option>
                <option value="2" {{ request('status') == 2 ? 'selected' : '' }}>With Staff 2</option>
                <option value="3" {{ request('status') == 3 ? 'selected' : '' }}>Returned to Admission</option>
                <option value="4" {{ request('status') == 4 ? 'selected' : '' }}>Returned to Staff 1</option>
                <option value="5" {{ request('status') == 5 ? 'selected' : '' }}>Approved</option>
                <option value="6" {{ request('status') == 6 ? 'selected' : '' }}>Declined</option>
            </select>
            <select name="type" class="border rounded px-3 py-2 text-sm">
                <option value="">All Types</option>
                @foreach($requestTypes as $type)
                    <option value="{{ $type->id }}" {{ request('type') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="border rounded px-3 py-2 text-sm">
            <div class="flex gap-2">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="border rounded px-3 py-2 text-sm flex-1">
                <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-blue-700">Filter</button>
                <a href="{{ route('dashboard') }}" class="bg-gray-200 text-gray-700 px-3 py-2 rounded text-sm font-semibold">✕</a>
            </div>
        </div>
    </form>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex gap-4 mb-4 border-b">
            <button onclick="filterTable('all')"
                id="tab-all"
                class="pb-2 text-sm font-bold border-b-2 border-blue-600 text-blue-600">
                All ({{ $total }})
            </button>
            <button onclick="filterTable('action')"
                id="tab-action"
                class="pb-2 text-sm font-semibold text-gray-400 hover:text-gray-600">
                Needs Action ({{ $myQueueCount }})
            </button>
            <button onclick="filterTable('staff2')"
                id="tab-staff2"
                class="pb-2 text-sm font-semibold text-gray-400 hover:text-gray-600">
                With Staff 2
            </button>
            <button onclick="filterTable('done')"
                id="tab-done"
                class="pb-2 text-sm font-semibold text-gray-400 hover:text-gray-600">
                Completed
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm" id="requests-table">
                <thead>
                    <tr class="bg-gray-50 border-b">
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Ref Number</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Type</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Submitted By</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Amount</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Date</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Status</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($displayRequests as $req)
                    <tr class="border-b hover:bg-gray-50 request-row"
                        data-status="{{ $req->status_id }}">
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
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400 italic">
                            No requests found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $displayRequests->links() }}</div>

    {{-- Tab filter JS --}}
    <script>
        function filterTable(filter) {
            const rows = document.querySelectorAll('.request-row');
            const tabs = document.querySelectorAll('[id^="tab-"]');

            tabs.forEach(t => {
                t.classList.remove('border-b-2', 'border-blue-600', 'text-blue-600');
                t.classList.add('text-gray-400');
            });
            document.getElementById('tab-' + filter).classList.add('border-b-2', 'border-blue-600', 'text-blue-600');
            document.getElementById('tab-' + filter).classList.remove('text-gray-400');

            rows.forEach(row => {
                const status = parseInt(row.dataset.status);
                if (filter === 'all') {
                    row.style.display = '';
                } else if (filter === 'action') {
                    row.style.display = (status === 1 || status === 4) ? '' : 'none';
                } else if (filter === 'staff2') {
                    row.style.display = (status === 2) ? '' : 'none';
                } else if (filter === 'done') {
                    row.style.display = (status === 5 || status === 6) ? '' : 'none';
                }
            });
        }
    </script>

            {{-- ================================ --}}
            {{-- STAFF 2 DASHBOARD --}}
            {{-- ================================ --}}
            @elseif(auth()->user()->role === 'staff2')

                <div class="flex items-center justify-between mb-4 gap-3">
                    <h3 class="font-bold text-lg">Staff 2 Tools</h3>
                    <div class="flex gap-2">
                        <a href="{{ route('staff2.admin') }}" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-bold hover:bg-blue-700">
                            Staff 2 Admin
                        </a>
                        <a href="{{ route('form-templates.index') }}" class="bg-white border border-gray-300 text-gray-800 px-4 py-2 rounded text-sm font-bold hover:bg-gray-50">
                            Manage Blank Forms
                        </a>
                    </div>
                </div>

                {{-- Summary Stats --}}
                @php
                    $withStaff2  = $dashboardStats['with_staff_2'] ?? 0;
                    $approved    = $dashboardStats['approved'] ?? 0;
                    $declined    = $dashboardStats['declined'] ?? 0;
                    $total       = $dashboardStats['total'] ?? 0;
                @endphp
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-blue-600">{{ $withStaff2 }}</p>
                        <p class="text-xs text-blue-500 mt-1">Awaiting My Review</p>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-green-600">{{ $approved }}</p>
                        <p class="text-xs text-green-500 mt-1">Approved</p>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-red-600">{{ $declined }}</p>
                        <p class="text-xs text-red-500 mt-1">Declined</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-gray-600">{{ $total }}</p>
                        <p class="text-xs text-gray-500 mt-1">Total Requests</p>
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-bold text-lg mb-4">⚖️ Evaluation Queue</h3>
                    <div class="overflow-x-auto">
                        <form method="GET" action="{{ route('dashboard') }}" class="mb-4">
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search ref / name / email" class="border rounded px-3 py-2 text-sm col-span-2 md:col-span-2">
        <select name="status" class="border rounded px-3 py-2 text-sm">
            <option value="">All Statuses</option>
            <option value="1" {{ request('status') == 1 ? 'selected' : '' }}>Pending Verification</option>
            <option value="2" {{ request('status') == 2 ? 'selected' : '' }}>With Staff 2</option>
            <option value="3" {{ request('status') == 3 ? 'selected' : '' }}>Returned to Admission</option>
            <option value="4" {{ request('status') == 4 ? 'selected' : '' }}>Returned to Staff 1</option>
            <option value="5" {{ request('status') == 5 ? 'selected' : '' }}>Approved</option>
            <option value="6" {{ request('status') == 6 ? 'selected' : '' }}>Declined</option>
        </select>
        <select name="type" class="border rounded px-3 py-2 text-sm">
            <option value="">All Types</option>
            @foreach($requestTypes as $type)
                <option value="{{ $type->id }}" {{ request('type') == $type->id ? 'selected' : '' }}>
                    {{ $type->name }}
                </option>
            @endforeach
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="border rounded px-3 py-2 text-sm">
        <div class="flex gap-2">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="border rounded px-3 py-2 text-sm flex-1">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-bold hover:bg-blue-700">Filter</button>
            <a href="{{ route('dashboard') }}" class="bg-gray-200 text-gray-600 px-3 py-2 rounded text-sm font-bold hover:bg-gray-300">✕</a>
        </div>
    </div>
</form>

                        <div class="mt-3 flex justify-end">
                            <a href="{{ route('requests.exportCsv') . '?' . http_build_query(request()->query()) }}"
                               class="text-sm px-3 py-2 rounded-md bg-slate-800 text-white hover:bg-slate-700">
                                Export CSV
                            </a>
                        </div>
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 border-b">
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Ref Number</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Type</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Submitted By</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Amount</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Verified By</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Status</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($displayRequests as $req)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono text-xs">{{ $req->ref_number }}</td>
                                    <td class="px-4 py-3">{{ $req->requestType->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold">{{ $req->user->name }}</p>
                                        <p class="text-xs text-gray-400">{{ $req->user->email }}</p>
                                    </td>
                                    <td class="px-4 py-3 font-bold">RM {{ number_format($req->payload['amount'] ?? 0, 2) }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ $req->verifiedBy?->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold {{ $req->statusClass() }}">
                                            {{ $req->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('requests.show', $req->id) }}"
                                           class="px-3 py-1 bg-purple-600 text-white rounded text-xs font-bold hover:bg-purple-700">
                                            Review
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-400 italic">
                                        No requests awaiting evaluation.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $displayRequests->links() }}</div>
                </div>

            @endif

        </div>
    </div>
</x-app-layout>