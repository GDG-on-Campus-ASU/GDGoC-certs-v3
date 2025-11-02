{{--
    Create Certificate View
    
    Expected variables:
    @var \Illuminate\Database\Eloquent\Collection<\App\Models\CertificateTemplate> $userCertTemplates - Collection of user's certificate templates
    @var \Illuminate\Database\Eloquent\Collection<\App\Models\CertificateTemplate> $globalCertTemplates - Collection of global certificate templates
    @var \Illuminate\Database\Eloquent\Collection<\App\Models\EmailTemplate> $userEmailTemplates - Collection of user's email templates
    @var \Illuminate\Database\Eloquent\Collection<\App\Models\EmailTemplate> $globalEmailTemplates - Collection of global email templates
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Certificate') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('dashboard.certificates.store') }}">
                        @csrf

                        <!-- Certificate Template -->
                        <div class="mb-4">
                            <x-input-label for="certificate_template_id" :value="__('Certificate Template')" />
                            <select id="certificate_template_id" name="certificate_template_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">Select a template</option>
                                @if($userCertTemplates->count() > 0)
                                    <optgroup label="Your Templates">
                                        @foreach($userCertTemplates as $template)
                                            <option value="{{ $template->id }}" {{ old('certificate_template_id') == $template->id ? 'selected' : '' }}>
                                                {{ $template->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if($globalCertTemplates->count() > 0)
                                    <optgroup label="Global Templates">
                                        @foreach($globalCertTemplates as $template)
                                            <option value="{{ $template->id }}" {{ old('certificate_template_id') == $template->id ? 'selected' : '' }}>
                                                {{ $template->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                            <x-input-error :messages="$errors->get('certificate_template_id')" class="mt-2" />
                        </div>

                        <!-- Email Template -->
                        <div class="mb-4">
                            <x-input-label for="email_template_id" :value="__('Email Template')" />
                            <select id="email_template_id" name="email_template_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">Select a template</option>
                                @if($userEmailTemplates->count() > 0)
                                    <optgroup label="Your Templates">
                                        @foreach($userEmailTemplates as $template)
                                            <option value="{{ $template->id }}" {{ old('email_template_id') == $template->id ? 'selected' : '' }}>
                                                {{ $template->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if($globalEmailTemplates->count() > 0)
                                    <optgroup label="Global Templates">
                                        @foreach($globalEmailTemplates as $template)
                                            <option value="{{ $template->id }}" {{ old('email_template_id') == $template->id ? 'selected' : '' }}>
                                                {{ $template->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                            <x-input-error :messages="$errors->get('email_template_id')" class="mt-2" />
                        </div>

                        <!-- Recipient Name -->
                        <div class="mb-4">
                            <x-input-label for="recipient_name" :value="__('Recipient Name')" />
                            <x-text-input id="recipient_name" class="block mt-1 w-full" type="text" name="recipient_name" :value="old('recipient_name')" required />
                            <x-input-error :messages="$errors->get('recipient_name')" class="mt-2" />
                        </div>

                        <!-- Recipient Email -->
                        <div class="mb-4">
                            <x-input-label for="recipient_email" :value="__('Recipient Email (Optional)')" />
                            <x-text-input id="recipient_email" class="block mt-1 w-full" type="email" name="recipient_email" :value="old('recipient_email')" />
                            <x-input-error :messages="$errors->get('recipient_email')" class="mt-2" />
                            <p class="text-sm text-gray-600 mt-1">Email address to send the certificate to</p>
                        </div>

                        <!-- State -->
                        <div class="mb-4">
                            <x-input-label for="state" :value="__('State')" />
                            <select id="state" name="state" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">Select state</option>
                                <option value="attending" {{ old('state') === 'attending' ? 'selected' : '' }}>Attending</option>
                                <option value="completing" {{ old('state') === 'completing' ? 'selected' : '' }}>Completing</option>
                            </select>
                            <x-input-error :messages="$errors->get('state')" class="mt-2" />
                        </div>

                        <!-- Event Type -->
                        <div class="mb-4">
                            <x-input-label for="event_type" :value="__('Event Type')" />
                            <select id="event_type" name="event_type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">Select event type</option>
                                <option value="workshop" {{ old('event_type') === 'workshop' ? 'selected' : '' }}>Workshop</option>
                                <option value="course" {{ old('event_type') === 'course' ? 'selected' : '' }}>Course</option>
                            </select>
                            <x-input-error :messages="$errors->get('event_type')" class="mt-2" />
                        </div>

                        <!-- Event Title -->
                        <div class="mb-4">
                            <x-input-label for="event_title" :value="__('Event Title')" />
                            <x-text-input id="event_title" class="block mt-1 w-full" type="text" name="event_title" :value="old('event_title')" required />
                            <x-input-error :messages="$errors->get('event_title')" class="mt-2" />
                        </div>

                        <!-- Issue Date -->
                        <div class="mb-4">
                            <x-input-label for="issue_date" :value="__('Issue Date')" />
                            <x-text-input id="issue_date" class="block mt-1 w-full" type="date" name="issue_date" :value="old('issue_date', date('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('issue_date')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button>
                                {{ __('Create Certificate') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
