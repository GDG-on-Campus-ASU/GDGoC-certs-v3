<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Bulk Certificate Upload') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('errors') && count(session('errors')) > 0)
                <div class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                    <p class="font-bold">Some rows had errors:</p>
                    <ul class="list-disc list-inside mt-2">
                        @foreach(session('errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">CSV File Format</h3>
                    <p class="text-sm text-gray-600 mb-2">Your CSV file must have the following columns (in any order):</p>
                    <ul class="list-disc list-inside text-sm text-gray-600 mb-4">
                        <li><strong>recipient_name</strong> - Name of the recipient (required)</li>
                        <li><strong>recipient_email</strong> - Email address of the recipient (optional)</li>
                        <li><strong>state</strong> - Either "attending" or "completing" (required)</li>
                        <li><strong>event_type</strong> - Either "workshop" or "course" (required)</li>
                        <li><strong>event_title</strong> - Title of the event (required)</li>
                        <li><strong>issue_date</strong> - Date in YYYY-MM-DD format (required)</li>
                    </ul>
                    <div class="bg-gray-50 p-4 rounded border border-gray-200">
                        <p class="text-sm font-semibold mb-2">Example CSV:</p>
                        <pre class="text-xs">recipient_name,recipient_email,state,event_type,event_title,issue_date
John Doe,john@example.com,completing,workshop,Web Development 101,2025-01-15
Jane Smith,jane@example.com,attending,course,Machine Learning,2025-02-20</pre>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('dashboard.certificates.bulk.store') }}" enctype="multipart/form-data">
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

                        <!-- CSV File -->
                        <div class="mb-4">
                            <x-input-label for="csv_file" :value="__('CSV File')" />
                            <input id="csv_file" type="file" name="csv_file" accept=".csv,.txt" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required />
                            <x-input-error :messages="$errors->get('csv_file')" class="mt-2" />
                            <p class="text-sm text-gray-600 mt-1">Upload a CSV file (max 10MB)</p>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button>
                                {{ __('Upload and Process') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
