<!-- Preview Modal Component -->
@props(['route', 'type' => 'certificate'])

<div id="preview-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" id="preview-backdrop"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            {{ ucfirst($type) }} Template Preview
                        </h3>
                        <div class="mt-4 border-t border-gray-200 pt-4">
                            @if($type === 'email')
                                <div class="mb-4">
                                    <span class="font-bold">Subject:</span> <span id="preview-subject" class="text-gray-900"></span>
                                </div>
                            @endif

                            <div class="border border-gray-300 rounded-md p-4 min-h-[200px] bg-gray-50">
                                <!-- Secure Preview Iframe: sandboxed to prevent script execution -->
                                <iframe id="preview-frame" class="w-full h-96 border-0" sandbox="allow-same-origin"></iframe>
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

        // Certificate inputs
        const contentInput = document.getElementById('content');
        const typeInput = document.getElementById('type');

        // Unified preview frame
        const previewFrame = document.getElementById('preview-frame');

        // Email inputs
        const subjectInput = document.getElementById('subject');
        const bodyInput = document.getElementById('body');
        const previewSubject = document.getElementById('preview-subject');

        function toggleModal(show) {
            if (show) {
                previewModal.classList.remove('hidden');
            } else {
                previewModal.classList.add('hidden');
            }
        }

        previewBtn.addEventListener('click', function() {
            let payload = {};
            const isEmail = '{{ $type }}' === 'email';

            if (isEmail) {
                const subject = subjectInput.value;
                const body = bodyInput.value;

                if (!subject || !body) {
                    alert('Please fill in both subject and body to preview.');
                    return;
                }
                payload = { subject: subject, body: body };
            } else {
                const content = contentInput.value;
                const type = typeInput.value;

                if (!content) {
                    alert('Please fill in the template content to preview.');
                    return;
                }
                payload = { content: content, type: type };
            }

            // Show loading state
            // Write loading message to iframe
            const doc = previewFrame.contentWindow.document;
            doc.open();
            doc.write('<html><body style="font-family: sans-serif; color: #666; text-align: center; padding-top: 20px;">Loading preview...</body></html>');
            doc.close();

            fetch('{{ $route }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw err;
                    });
                }
                return response.json();
            })
            .then(data => {
                if (isEmail) {
                    previewSubject.textContent = data.subject;
                    const doc = previewFrame.contentWindow.document;
                    doc.open();
                    doc.write(data.body);
                    doc.close();
                } else {
                    // For SVG/HTML
                    const doc = previewFrame.contentWindow.document;
                    doc.open();
                    doc.write(data.content);
                    doc.close();
                }
                toggleModal(true);
            })
            .catch(error => {
                console.error('Error:', error);
                let errorMsg = 'An error occurred while generating the preview.';
                if (error.message) {
                    errorMsg = error.message;
                }
                if (error.errors) {
                    // Laravel validation errors
                    errorMsg = Object.values(error.errors).flat().join('\n');
                }

                alert(errorMsg);

                // Clear loading state on error
                 const doc = previewFrame.contentWindow.document;
                 doc.open();
                 doc.write('');
                 doc.close();
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
