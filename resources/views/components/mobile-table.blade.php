@props([
    'headers' => [],
    'rows' => [],
    'actions' => true,
    'responsive' => true,
])

<div class="overflow-x-auto bg-white rounded-lg shadow">
    <!-- Desktop Table -->
    <table class="min-w-full divide-y divide-gray-200 hidden lg:table">
        <thead class="bg-gray-50">
            <tr>
                @foreach($headers as $header)
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ $header['label'] }}
                    </th>
                @endforeach
                @if($actions)
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                @endif
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach($rows as $row)
                <tr class="hover:bg-gray-50 transition-colors">
                    @foreach($headers as $header)
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $row[$header['key']] ?? '' }}
                        </td>
                    @endforeach
                    @if($actions)
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            {{ $slot }}
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Mobile Cards -->
    <div class="lg:hidden">
        @foreach($rows as $index => $row)
            <div class="border-b border-gray-200 p-4 hover:bg-gray-50 transition-colors">
                <!-- Primary Info (Always Visible) -->
                <div class="flex justify-between items-start mb-3">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900">
                            {{ $row[$headers[0]['key']] ?? '' }}
                        </h3>
                        @if(isset($row[$headers[1]['key']]))
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $row[$headers[1]['key']] }}
                            </p>
                        @endif
                    </div>
                    @if($actions)
                        <div class="ml-4">
                            {{ $slot }}
                        </div>
                    @endif
                </div>

                <!-- Expandable Details -->
                <div class="space-y-2">
                    @foreach($headers as $index => $header)
                        @if($index > 1 && isset($row[$header['key']]))
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">{{ $header['label'] }}:</span>
                                <span class="text-gray-900">{{ $row[$header['key']] }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
