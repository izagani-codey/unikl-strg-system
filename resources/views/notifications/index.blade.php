<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Notifications</h2>
            <form action="{{ route('notifications.readAll') }}" method="POST">
                @csrf
                @method('PATCH')
                <button class="text-sm px-3 py-2 rounded-md bg-slate-800 text-white hover:bg-slate-700">
                    Mark all as read
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="space-y-3">
                    @forelse($notifications as $notification)
                        <a href="{{ route('notifications.open', $notification->id) }}"
                           class="block border rounded-lg p-4 transition hover:bg-slate-50 {{ $notification->is_read ? 'border-slate-200' : 'border-blue-200 bg-blue-50/40' }}">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $notification->title }}</p>
                                    <p class="text-sm text-slate-600 mt-1">{{ $notification->message }}</p>
                                </div>
                                <span class="text-xs text-slate-500 whitespace-nowrap">{{ $notification->created_at?->diffForHumans() }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="text-center py-12 text-slate-500">No notifications yet.</div>
                    @endforelse
                </div>

                <div class="mt-6">
                    {{ $notifications->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
