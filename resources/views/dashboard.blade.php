<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
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
                        <div class="text-sm font-medium text-gray-500">Total Certificates</div>
                        <div class="mt-2 text-3xl font-semibold text-gray-900">{{ $stats['total_certificates'] }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Active Certificates</div>
                        <div class="mt-2 text-3xl font-semibold text-green-600">{{ $stats['active_certificates'] }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Emails Sent</div>
                        <div class="mt-2 text-3xl font-semibold text-blue-600">{{ $stats['emails_sent'] }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Certificate Templates</div>
                        <div class="mt-2 text-3xl font-semibold text-purple-600">{{ $stats['certificate_templates'] }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Email Templates</div>
                        <div class="mt-2 text-3xl font-semibold text-indigo-600">{{ $stats['email_templates'] }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Revoked Certificates</div>
                        <div class="mt-2 text-3xl font-semibold text-red-600">{{ $stats['revoked_certificates'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="{{ route('dashboard.certificates.index') }}" class="block p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition">
                            <h4 class="font-semibold text-blue-800">Certificates</h4>
                            <p class="text-sm text-blue-600">View and manage your certificates</p>
                        </a>
                        
                        <a href="{{ route('dashboard.configuration.index') }}" class="block p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition">
                            <h4 class="font-semibold text-purple-800">Private Configuration</h4>
                            <p class="text-sm text-purple-600">Configure your organization settings</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
