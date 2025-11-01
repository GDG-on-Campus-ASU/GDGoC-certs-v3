<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit SMTP Provider') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('dashboard.smtp.update', $smtpProvider) }}">
                        @csrf
                        @method('PUT')

                        <!-- Name -->
                        <div class="mb-4">
                            <x-input-label for="name" :value="__('Provider Name')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $smtpProvider->name)" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            <p class="text-sm text-gray-600 mt-1">A friendly name for this SMTP provider</p>
                        </div>

                        <!-- Host -->
                        <div class="mb-4">
                            <x-input-label for="host" :value="__('SMTP Host')" />
                            <x-text-input id="host" class="block mt-1 w-full" type="text" name="host" :value="old('host', $smtpProvider->host)" required />
                            <x-input-error :messages="$errors->get('host')" class="mt-2" />
                            <p class="text-sm text-gray-600 mt-1">e.g., smtp.gmail.com, smtp-relay.brevo.com</p>
                        </div>

                        <!-- Port -->
                        <div class="mb-4">
                            <x-input-label for="port" :value="__('SMTP Port')" />
                            <x-text-input id="port" class="block mt-1 w-full" type="number" name="port" :value="old('port', $smtpProvider->port)" required />
                            <x-input-error :messages="$errors->get('port')" class="mt-2" />
                            <p class="text-sm text-gray-600 mt-1">Common ports: 587 (TLS), 465 (SSL), 25</p>
                        </div>

                        <!-- Username -->
                        <div class="mb-4">
                            <x-input-label for="username" :value="__('Username')" />
                            <x-text-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username', $smtpProvider->username)" required />
                            <x-input-error :messages="$errors->get('username')" class="mt-2" />
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <x-input-label for="password" :value="__('Password')" />
                            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            <p class="text-sm text-gray-600 mt-1">Leave blank to keep current password</p>
                        </div>

                        <!-- Encryption -->
                        <div class="mb-4">
                            <x-input-label for="encryption" :value="__('Encryption')" />
                            <select id="encryption" name="encryption" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="tls" {{ old('encryption', $smtpProvider->encryption) === 'tls' ? 'selected' : '' }}>TLS</option>
                                <option value="ssl" {{ old('encryption', $smtpProvider->encryption) === 'ssl' ? 'selected' : '' }}>SSL</option>
                                <option value="none" {{ old('encryption', $smtpProvider->encryption) === 'none' ? 'selected' : '' }}>None</option>
                            </select>
                            <x-input-error :messages="$errors->get('encryption')" class="mt-2" />
                        </div>

                        <!-- From Address -->
                        <div class="mb-4">
                            <x-input-label for="from_address" :value="__('From Email Address')" />
                            <x-text-input id="from_address" class="block mt-1 w-full" type="email" name="from_address" :value="old('from_address', $smtpProvider->from_address)" required />
                            <x-input-error :messages="$errors->get('from_address')" class="mt-2" />
                            <p class="text-sm text-gray-600 mt-1">Email address to send from</p>
                        </div>

                        <!-- From Name -->
                        <div class="mb-4">
                            <x-input-label for="from_name" :value="__('From Name')" />
                            <x-text-input id="from_name" class="block mt-1 w-full" type="text" name="from_name" :value="old('from_name', $smtpProvider->from_name)" required />
                            <x-input-error :messages="$errors->get('from_name')" class="mt-2" />
                            <p class="text-sm text-gray-600 mt-1">Name to display in sent emails</p>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('dashboard.smtp.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 mr-3">
                                Cancel
                            </a>

                            <x-primary-button>
                                {{ __('Update Provider') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
