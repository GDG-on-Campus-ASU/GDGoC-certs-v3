<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="org_name" :value="__('Organization Name')" />
            @if($user->org_name)
                <x-text-input id="org_name" name="org_name" type="text" class="mt-1 block w-full bg-gray-100" :value="old('org_name', $user->org_name)" readonly />
                <p class="mt-1 text-sm text-gray-600">
                    {{ __('Organization name cannot be changed. Contact a super admin if you need to update it.') }}
                </p>
            @else
                <x-text-input id="org_name" name="org_name" type="text" class="mt-1 block w-full" :value="old('org_name', $user->org_name)" autocomplete="organization" />
                <div class="mt-2 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                    <strong>{{ __('Warning:') }}</strong> {{ __('You can\'t change your Organization Name later') }}
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('org_name')" />
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
