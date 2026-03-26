<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Staff 2 Admin Panel</h2>
            <a href="{{ route('form-templates.index') }}" class="text-sm bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700">Manage Blank Forms</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-4"><p class="text-xs text-gray-500">Total Requests</p><p class="text-2xl font-bold">{{ $totalRequests }}</p></div>
                <div class="bg-white rounded-lg shadow-sm p-4"><p class="text-xs text-gray-500">With Staff 2</p><p class="text-2xl font-bold text-blue-600">{{ $withStaff2 }}</p></div>
                <div class="bg-white rounded-lg shadow-sm p-4"><p class="text-xs text-gray-500">Approved</p><p class="text-2xl font-bold text-green-600">{{ $approved }}</p></div>
                <div class="bg-white rounded-lg shadow-sm p-4"><p class="text-xs text-gray-500">Declined</p><p class="text-2xl font-bold text-red-600">{{ $declined }}</p></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="font-bold mb-4">Top Request Types</h3>
                    <div class="space-y-3">
                        @foreach($byType as $type)
                            <div class="flex items-center justify-between">
                                <span>{{ $type->name }}</span>
                                <span class="text-sm text-gray-500">{{ $type->requests_count }} requests</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="font-bold mb-4">Recent High Priority</h3>
                    <div class="space-y-3">
                        @forelse($recentHighPriority as $req)
                            <a href="{{ route('requests.show', $req->id) }}" class="block p-3 rounded border hover:bg-gray-50">
                                <p class="font-semibold text-sm">{{ $req->ref_number }} · {{ $req->requestType->name ?? 'N/A' }}</p>
                                <p class="text-xs text-gray-500">{{ $req->user->name }} · {{ $req->created_at->format('d M Y') }}</p>
                            </a>
                        @empty
                            <p class="text-sm text-gray-500">No high-priority requests.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
