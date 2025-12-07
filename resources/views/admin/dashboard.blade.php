<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success Message -->
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Total Leaders</div>
                        <div class="mt-2 text-3xl font-semibold text-gray-900">{{ $stats['total_users'] }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Active Users</div>
                        <div class="mt-2 text-3xl font-semibold text-green-600">{{ $stats['active_users'] }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Suspended Users</div>
                        <div class="mt-2 text-3xl font-semibold text-yellow-600">{{ $stats['suspended_users'] }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Terminated Users</div>
                        <div class="mt-2 text-3xl font-semibold text-red-600">{{ $stats['terminated_users'] }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Successful Logins</div>
                        <div class="mt-2 text-3xl font-semibold text-blue-600">{{ $stats['recent_logins'] }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Failed Login Attempts</div>
                        <div class="mt-2 text-3xl font-semibold text-red-600">{{ $stats['failed_logins'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <a href="{{ route('admin.users.index') }}" class="block p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition">
                            <h4 class="font-semibold text-blue-800">Manage Users</h4>
                            <p class="text-sm text-blue-600">Create, edit, and manage leader accounts</p>
                        </a>
                        
                        <a href="{{ route('admin.oidc.edit') }}" class="block p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition">
                            <h4 class="font-semibold text-purple-800">OIDC Settings</h4>
                            <p class="text-sm text-purple-600">Configure OAuth/OIDC authentication</p>
                        </a>
                        
                        <a href="{{ route('admin.logs.index') }}" class="block p-4 bg-green-50 hover:bg-green-100 rounded-lg transition">
                            <h4 class="font-semibold text-green-800">Login Logs</h4>
                            <p class="text-sm text-green-600">View authentication activity</p>
                        </a>

                        <a href="{{ route('admin.documentation.index') }}" class="block p-4 bg-orange-50 hover:bg-orange-100 rounded-lg transition">
                            <h4 class="font-semibold text-orange-800">Documentation</h4>
                            <p class="text-sm text-orange-600">Manage help documentation pages</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
