{{--
    Edit Email Template View
    
    Expected variables:
    @var \App\Models\EmailTemplate $emailTemplate - The email template being edited
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Email Template') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('dashboard.templates.email.update', $emailTemplate) }}">
                        @csrf
                        @method('PUT')

                        <!-- Name -->
                        <div class="mb-4">
                            <x-input-label for="name" :value="__('Template Name')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $emailTemplate->name)" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Subject -->
                        <div class="mb-4">
                            <x-input-label for="subject" :value="__('Email Subject')" />
                            <x-text-input id="subject" class="block mt-1 w-full" type="text" name="subject" :value="old('subject', $emailTemplate->subject)" required />
                            <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                        </div>

                        <!-- Body -->
                        <div class="mb-4">
                            <x-input-label for="body" :value="__('Email Body (Blade Template)')" />
                            <textarea id="body" name="body" rows="15" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-mono text-sm" required>{{ old('body', $emailTemplate->body) }}</textarea>
                            <x-input-error :messages="$errors->get('body')" class="mt-2" />
                            <p class="text-sm text-gray-600 mt-1">Enter your email body using Blade template syntax</p>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <button type="button" id="preview-btn" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 mr-3">
                                {{ __('Preview') }}
                            </button>

                            <a href="{{ route('dashboard.templates.email.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 mr-3">
                                Cancel
                            </a>

                            <x-primary-button>
                                {{ __('Update Template') }}
                            </x-primary-button>
                        </div>
                    </form>

                    <x-template-preview-modal :route="route('dashboard.templates.email.preview')" type="email" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
