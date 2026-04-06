<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-green-600 to-blue-600 bg-clip-text text-transparent">System Health</h1>
                <p class="text-gray-600 mt-1">Monitor system performance and status</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- System Overview -->
            <div class="mb-8">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">System Overview</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-full p-2">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800">System Status</p>
                                    <p class="text-lg font-semibold text-green-900">{{ ucfirst($health['system']['status']) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-full p-2">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-blue-800">Uptime</p>
                                    <p class="text-lg font-semibold text-blue-900">{{ $health['system']['uptime'] }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-purple-500 rounded-full p-2">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 12v3c0 1.657 3.134 3 7 3s7-1.343 7-3v-3c0 1.657-3.134 3-7 3s-7-1.343-7-3z"/>
                                        <path d="M3 7v3c0 1.657 3.134 3 7 3s7-1.343 7-3V7c0 1.657-3.134 3-7 3S3 8.657 3 7z"/>
                                        <path d="M17 5c0 1.657-3.134 3-7 3S3 6.657 3 5s3.134-3 7-3 7 1.343 7 3z"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-purple-800">Memory Usage</p>
                                    <p class="text-lg font-semibold text-purple-900">{{ $health['system']['memory_usage']['current_mb'] }} MB</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-orange-50 rounded-lg p-4 border border-orange-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-orange-500 rounded-full p-2">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-orange-800">Disk Usage</p>
                                    <p class="text-lg font-semibold text-orange-900">{{ $health['system']['disk_usage']['used_percent'] }}%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Database Health -->
            <div class="mb-8">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Database Health</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="border-l-4 {{ $health['database']['status'] === 'connected' ? 'border-green-500' : 'border-red-500' }} pl-4">
                            <p class="text-sm text-gray-600">Connection Status</p>
                            <p class="text-lg font-semibold {{ $health['database']['status'] === 'connected' ? 'text-green-600' : 'text-red-600' }}">
                                {{ ucfirst($health['database']['status']) }}
                            </p>
                        </div>
                        <div class="border-l-4 border-blue-500 pl-4">
                            <p class="text-sm text-gray-600">Database Size</p>
                            <p class="text-lg font-semibold text-gray-900">{{ number_format($health['database']['size_mb'], 2) }} MB</p>
                        </div>
                        <div class="border-l-4 border-purple-500 pl-4">
                            <p class="text-sm text-gray-600">Tables Count</p>
                            <p class="text-lg font-semibold text-gray-900">{{ $health['database']['tables_count'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cache Health -->
            <div class="mb-8">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Cache Health</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border-l-4 {{ $health['cache']['status'] === 'working' ? 'border-green-500' : 'border-red-500' }} pl-4">
                            <p class="text-sm text-gray-600">Cache Status</p>
                            <p class="text-lg font-semibold {{ $health['cache']['status'] === 'working' ? 'text-green-600' : 'text-red-600' }}">
                                {{ ucfirst($health['cache']['status']) }}
                            </p>
                        </div>
                        <div class="border-l-4 border-blue-500 pl-4">
                            <p class="text-sm text-gray-600">Cache Driver</p>
                            <p class="text-lg font-semibold text-gray-900">{{ $health['cache']['driver'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="mb-8">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Performance Metrics</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ $health['performance']['requests_today'] }}</p>
                            <p class="text-sm text-gray-600">Requests Today</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ $health['performance']['active_users_today'] }}</p>
                            <p class="text-sm text-gray-600">Active Users Today</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ $health['performance']['avg_response_time'] }}ms</p>
                            <p class="text-sm text-gray-600">Avg Response Time</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ $health['performance']['memory_peak'] }}MB</p>
                            <p class="text-sm text-gray-600">Memory Peak</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Status -->
            <div class="mb-8">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Security Status</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($health['security'] as $key => $value)
                            <div class="flex items-center justify-between p-3 rounded-lg border {{ $value ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
                                <span class="text-sm font-medium text-gray-700">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $value ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $value ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">System Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Laravel Version</p>
                            <p class="font-semibold text-gray-900">{{ $health['system']['laravel_version'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">PHP Version</p>
                            <p class="font-semibold text-gray-900">{{ $health['system']['php_version'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Environment</p>
                            <p class="font-semibold text-gray-900">{{ $health['system']['environment'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Debug Mode</p>
                            <p class="font-semibold {{ $health['system']['debug_mode'] ? 'text-red-600' : 'text-green-600' }}">
                                {{ $health['system']['debug_mode'] ? 'Enabled' : 'Disabled' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Maintenance Mode</p>
                            <p class="font-semibold {{ $health['system']['maintenance_mode'] ? 'text-orange-600' : 'text-green-600' }}">
                                {{ $health['system']['maintenance_mode'] ? 'Enabled' : 'Disabled' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Database Version</p>
                            <p class="font-semibold text-gray-900">{{ $health['database']['version'] ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
