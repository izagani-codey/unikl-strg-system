<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Audit Log Viewer</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <form method="GET" action="{{ route('audit-logs.index') }}" class="bg-white p-4 rounded-lg shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                    <input type="text" name="reference" value="{{ request('reference') }}" placeholder="Reference no." class="border rounded px-3 py-2 text-sm">
                    <input type="text" name="actor" value="{{ request('actor') }}" placeholder="Actor name/email" class="border rounded px-3 py-2 text-sm">
                    <select name="status" class="border rounded px-3 py-2 text-sm">
                        <option value="">Any Status</option>
                        @for($i = 1; $i <= 6; $i++)
                            <option value="{{ $i }}" @selected(request('status') == $i)>Status {{ $i }}</option>
                        @endfor
                    </select>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="border rounded px-3 py-2 text-sm">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="border rounded px-3 py-2 text-sm">
                    <div class="flex gap-2">
                        <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-blue-700">Filter</button>
                        <a href="{{ route('audit-logs.index') }}" class="bg-slate-200 text-slate-700 px-3 py-2 rounded text-sm font-semibold">Reset</a>
                    </div>
                </div>
            </form>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Request</th>
                            <th class="px-4 py-3 text-left">Actor</th>
                            <th class="px-4 py-3 text-left">Transition</th>
                            <th class="px-4 py-3 text-left">Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr class="border-b hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-600">{{ $log->created_at?->format('d M Y, h:i A') }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $log->request?->ref_number ?? 'N/A' }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $log->actor?->name ?? 'System' }}</div>
                                    <div class="text-xs text-slate-500">{{ $log->actor?->email }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $log->from_status }} → {{ $log->to_status }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $log->note ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">No audit logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="p-4">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
