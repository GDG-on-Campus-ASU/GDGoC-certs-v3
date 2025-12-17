<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Email Template') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('dashboard.templates.email.store') }}">
                        @csrf

                        <!-- Name -->
                        <div class="mb-4">
                            <x-input-label for="name" :value="__('Template Name')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Subject -->
                        <div class="mb-4">
                            <x-input-label for="subject" :value="__('Email Subject')" />
                            <x-text-input id="subject" class="block mt-1 w-full" type="text" name="subject" :value="old('subject')" required />
                            <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                        </div>

                        <!-- Body -->
                        <div class="mb-4">
                            <x-input-label for="body" :value="__('Email Body (Blade Template)')" />
                            <textarea id="body" name="body" rows="15" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-mono text-sm" required>{{ old('body') }}</textarea>
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
                                {{ __('Create Template') }}
                            </x-primary-button>
                        </div>
                    </form>

                    <!-- Preview Modal -->
                    <div id="preview-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" id="preview-backdrop"></div>
                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <div class="sm:flex sm:items-start">
                                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                                Email Template Preview
                                            </h3>
                                            <div class="mt-4 border-t border-gray-200 pt-4">
                                                <div class="mb-4">
                                                    <span class="font-bold">Subject:</span> <span id="preview-subject" class="text-gray-900"></span>
                                                </div>
                                                <div class="border border-gray-300 rounded-md p-4 min-h-[200px] bg-gray-50">
                                                    <iframe id="preview-body-frame" class="w-full h-96 border-0"></iframe>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button type="button" id="close-preview-btn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const previewBtn = document.getElementById('preview-btn');
                            const previewModal = document.getElementById('preview-modal');
                            const closePreviewBtn = document.getElementById('close-preview-btn');
                            const previewBackdrop = document.getElementById('preview-backdrop');
                            const subjectInput = document.getElementById('subject');
                            const bodyInput = document.getElementById('body');
                            const previewSubject = document.getElementById('preview-subject');
                            const previewBodyFrame = document.getElementById('preview-body-frame');

                            function toggleModal(show) {
                                if (show) {
                                    previewModal.classList.remove('hidden');
                                } else {
                                    previewModal.classList.add('hidden');
                                }
                            }

                            previewBtn.addEventListener('click', function() {
                                const subject = subjectInput.value;
                                const body = bodyInput.value;

                                if (!subject || !body) {
                                    alert('Please fill in both subject and body to preview.');
                                    return;
                                }

                                // Show loading state or similar if desired

                                fetch('{{ route("dashboard.templates.email.preview") }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        subject: subject,
                                        body: body
                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.error) {
                                        alert(data.error);
                                    } else {
                                        previewSubject.textContent = data.subject;
                                        // Use iframe to display HTML content safely and isolated
                                        const doc = previewBodyFrame.contentWindow.document;
                                        doc.open();
                                        doc.write(data.body);
                                        doc.close();
                                        toggleModal(true);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('An error occurred while generating the preview.');
                                });
                            });

                            closePreviewBtn.addEventListener('click', function() {
                                toggleModal(false);
                            });

                            previewBackdrop.addEventListener('click', function() {
                                toggleModal(false);
                            });
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
