<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Global SMTP Provider') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.smtp.update', $smtp) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Is Global -->
                        <div class="flex items-center">
                            <input id="is_global" name="is_global" type="checkbox" value="1" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" {{ old('is_global', $smtp->is_global) ? 'checked' : '' }}>
                            <label for="is_global" class="ml-2 block text-sm text-gray-900">
                                {{ __('Global Provider') }}
                            </label>
                        </div>
                        <p class="text-sm text-gray-500">Global providers are available to all users who haven't configured their own SMTP.</p>

                        <!-- Name -->
                        <div>
                            <x-input-label for="name" :value="__('Provider Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $smtp->name)" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <!-- Host -->
                        <div>
                            <x-input-label for="host" :value="__('SMTP Host')" />
                            <x-text-input id="host" name="host" type="text" class="mt-1 block w-full" :value="old('host', $smtp->host)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('host')" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Port -->
                            <div>
                                <x-input-label for="port" :value="__('Port')" />
                                <x-text-input id="port" name="port" type="number" class="mt-1 block w-full" :value="old('port', $smtp->port)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('port')" />
                            </div>

                            <!-- Encryption -->
                            <div>
                                <x-input-label for="encryption" :value="__('Encryption')" />
                                <select id="encryption" name="encryption" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="tls" {{ old('encryption', $smtp->encryption) === 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ old('encryption', $smtp->encryption) === 'ssl' ? 'selected' : '' }}>SSL</option>
                                    <option value="none" {{ old('encryption', $smtp->encryption) === 'none' ? 'selected' : '' }}>None</option>
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('encryption')" />
                            </div>
                        </div>

                        <!-- Username -->
                        <div>
                            <x-input-label for="username" :value="__('Username')" />
                            <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', $smtp->username)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('username')" />
                        </div>

                        <!-- Password -->
                        <div>
                            <x-input-label for="password" :value="__('Password')" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" />
                            <p class="text-sm text-gray-500 mt-1">Leave blank to keep the current password.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('password')" />
                        </div>

                        <!-- From Address -->
                        <div>
                            <x-input-label for="from_address" :value="__('From Email Address')" />
                            <x-text-input id="from_address" name="from_address" type="email" class="mt-1 block w-full" :value="old('from_address', $smtp->from_address)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('from_address')" />
                        </div>

                        <!-- From Name -->
                        <div>
                            <x-input-label for="from_name" :value="__('From Name')" />
                            <x-text-input id="from_name" name="from_name" type="text" class="mt-1 block w-full" :value="old('from_name', $smtp->from_name)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('from_name')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Update Provider') }}</x-primary-button>
                            <a href="{{ route('admin.smtp.index') }}" class="text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
