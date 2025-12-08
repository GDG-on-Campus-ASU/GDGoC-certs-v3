<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('info'))
                <div class="p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded-lg">
                    {{ session('info') }}
                </div>
            @endif

            @if (!auth()->user()->org_name)
                <div class="p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg">
                    <strong>{{ __('Action Required:') }}</strong> {{ __('Please set your organization name below to continue using the platform.') }}
                </div>
            @endif
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
