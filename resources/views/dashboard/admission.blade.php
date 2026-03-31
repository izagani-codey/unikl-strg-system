<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Requests</h2>
            <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-full uppercase">Admission</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Dev Quick-Switch (local only) --}}
            @if(app()->environment('local') && Route::has('dev.login'))
                @include('dashboard._dev-switcher')
            @endif

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            {{-- Welcome + new request CTA --}}
            <div class="bg-blue-600 text-white rounded-lg p-6 shadow">
                <h3 class="text-xl font-bold">Welcome, {{ auth()->user()->name }} 👋</h3>
                <p class="text-blue-100 text-sm mt-1">{{ auth()->user()->email }}</p>
                <div class="mt-4">
                    <a href="{{ route('requests.create') }}"
                       class="bg-white text-blue-600 font-bold px-5 py-2 rounded hover:bg-blue-50 text-sm">
                        + Submit New Request
                    </a>
                </div>
            </div>

            {{-- Blank forms download --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-bold text-lg mb-1 border-b pb-2">📄 Blank Forms & Templates</h3>
                <p class="text-sm text-gray-500 mb-4">Download, fill, and upload these forms with your request.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @forelse($formTemplates as $template)
                        <div class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50">
                            <span class="text-sm font-semibold text-gray-700">📎 {{ $template->title }}</span>
                            <a href="{{ asset('storage/' . $template->file_path) }}" target="_blank"
                               class="text-xs font-bold text-blue-600 hover:underline">Download</a>
                        </div>
                    @empty
                        <div class="p-4 border border-dashed rounded text-sm text-gray-500">
                            No templates uploaded yet. Ask Staff 2 to upload blank forms.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Status summary cards --}}
            @php
                $inReview = ($dashboardStats['pending_verification'] ?? 0) + ($dashboardStats['with_staff_2'] ?? 0);
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-orange-600">{{ $inReview }}</p>
                    <p class="text-xs text-orange-500 mt-1">In Review</p>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-yellow-600">{{ $dashboardStats['returned_to_admission'] ?? 0 }}</p>
                    <p class="text-xs text-yellow-500 mt-1">Needs Revision</p>
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
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search ref or description..."
                           class="border rounded px-3 py-2 text-sm col-span-2 md:col-span-1">
                    <select name="status" class="border rounded px-3 py-2 text-sm">
                        <option value="">All Statuses</option>
                        <option value="1" @selected(request('status') == 1)>Pending Verification</option>
                        <option value="2" @selected(request('status') == 2)>With Staff 2</option>
                        <option value="3" @selected(request('status') == 3)>Returned to Admission</option>
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
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-bold hover:bg-blue-700">Filter</button>
                        <a href="{{ route('dashboard') }}" class="bg-gray-200 text-gray-600 px-3 py-2 rounded text-sm font-bold">✕</a>
                    </div>
                </div>
            </form>

            {{-- My submissions table --}}
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
                                               class="px-3 py-1 bg-blue-600 text-white rounded text-xs font-bold hover:bg-blue-700">View</a>
                                            @if($req->status_id == 3)
                                                <a href="{{ route('requests.edit', $req->id) }}"
                                                   class="px-3 py-1 bg-yellow-500 text-white rounded text-xs font-bold hover:bg-yellow-600">✏ Revise</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
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
                <div class="mt-4">{{ $displayRequests->links() }}</div>
            </div>

        </div>
    </div>
</x-app-layout>
