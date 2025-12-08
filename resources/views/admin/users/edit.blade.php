<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit User') }}: {{ $user->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <!-- Name -->
                        <div class="mb-4">
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $user->name)" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Org Name -->
                        <div class="mb-4">
                            <x-input-label for="org_name" :value="__('Organization Name')" />
                            <x-text-input id="org_name" class="block mt-1 w-full" type="text" name="org_name" :value="old('org_name', $user->org_name)" />
                            <x-input-error :messages="$errors->get('org_name')" class="mt-2" />
                            <p class="text-sm text-gray-600 mt-1">This is the only way to set/change the organization name</p>
                        </div>

                        <!-- Role (Display only) -->
                        <div class="mb-4">
                            <x-input-label for="role_display" :value="__('Role')" />
                            <div class="mt-1 px-4 py-2 bg-gray-100 rounded-md">
                                @if($user->role === 'admin')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                        Admin
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Leader
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 mt-1">User role cannot be changed after creation</p>
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <x-input-label for="password" :value="__('New Password (optional)')" />
                            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            <p class="text-sm text-gray-600 mt-1">Leave blank to keep current password. Minimum 8 characters if changing.</p>
                        </div>

                        <!-- Status -->
                        <div class="mb-4">
                            <x-input-label for="status" :value="__('Status')" />
                            <select id="status" name="status" class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm w-full" onchange="toggleTerminationReason()">
                                <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="suspended" {{ old('status', $user->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                <option value="terminated" {{ old('status', $user->status) === 'terminated' ? 'selected' : '' }}>Terminated</option>
                            </select>
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>

                        <!-- Termination Reason -->
                        <div class="mb-4" id="termination-reason-field" style="display: {{ old('status', $user->status) === 'terminated' ? 'block' : 'none' }};">
                            <x-input-label for="termination_reason" :value="__('Termination Reason')" />
                            <textarea id="termination_reason" name="termination_reason" rows="3" class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm w-full">{{ old('termination_reason', $user->termination_reason) }}</textarea>
                            <x-input-error :messages="$errors->get('termination_reason')" class="mt-2" />
                            <p class="text-sm text-gray-600 mt-1">Required when status is Terminated</p>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 mr-3">
                                Cancel
                            </a>

                            <x-primary-button>
                                {{ __('Update User') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleTerminationReason() {
            const status = document.getElementById('status').value;
            const terminationReasonField = document.getElementById('termination-reason-field');
            
            if (status === 'terminated') {
                terminationReasonField.style.display = 'block';
            } else {
                terminationReasonField.style.display = 'none';
            }
        }
    </script>
</x-app-layout>
