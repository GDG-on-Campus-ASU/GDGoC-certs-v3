{{--
    Certificate Management View
    
    Expected variables:
    @var \Illuminate\Pagination\LengthAwarePaginator<\App\Models\Certificate> $certificates - Paginated collection of certificates
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Certificates') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{
        revokeModalOpen: false,
        revokeCertificateId: '',
        revokeRecipientName: '',
        revokeActionUrl: '',
        baseRevokeUrl: '{{ route('dashboard.certificates.revoke', ['certificate' => 'CERTIFICATE_ID_PLACEHOLDER']) }}',
        openRevokeModal(id, name) {
            this.revokeCertificateId = id;
            this.revokeRecipientName = name;
            this.revokeActionUrl = this.baseRevokeUrl.replace('CERTIFICATE_ID_PLACEHOLDER', id);
            this.revokeModalOpen = true;
            // Focus the reason input on open
            this.$nextTick(() => {
                if (this.$refs.revokeReasonInput) {
                    this.$refs.revokeReasonInput.focus();
                }
            });
        },
        closeRevokeModal() {
            this.revokeModalOpen = false;
            // Clear data after transition to avoid flicker
            setTimeout(() => {
                this.revokeCertificateId = '';
                this.revokeRecipientName = '';
            }, 300);
        }
    }"
    x-on:keydown.escape.window="closeRevokeModal()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Recipient
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Event
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Issued
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($certificates as $certificate)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $certificate->recipient_name }}
                                            </div>
                                            @if($certificate->recipient_email)
                                                <div class="text-sm text-gray-500">
                                                    {{ $certificate->recipient_email }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">{{ $certificate->event_title }}</div>
                                            <div class="text-sm text-gray-500">
                                                {{ ucfirst($certificate->event_type) }} - {{ ucfirst($certificate->state) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($certificate->status === 'issued')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Issued
                                                </span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Revoked
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $certificate->issue_date->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            @if($certificate->status === 'issued')
                                                <button type="button" 
                                                    @click="openRevokeModal('{{ $certificate->id }}', '{{ addslashes($certificate->recipient_name) }}')"
                                                    class="text-red-600 hover:text-red-900"
                                                    data-name="{{ $certificate->recipient_name }}">
                                                    Revoke
                                                </button>
                                            @else
                                                <div class="text-sm text-gray-500">
                                                    <div>Revoked on {{ $certificate->revoked_at ? $certificate->revoked_at->format('M d, Y') : 'Unknown' }}</div>
                                                    <div class="text-xs mt-1">Reason: {{ $certificate->revocation_reason ?? 'No reason provided' }}</div>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No certificates found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $certificates->links() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Revoke Modal -->
        <div
            x-show="revokeModalOpen"
            style="display: none;"
            x-cloak
            id="revokeModal"
            class="fixed z-10 inset-0 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

                <!-- Background overlay -->
                <div
                    x-show="revokeModalOpen"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    @click="closeRevokeModal()"
                    aria-hidden="true"
                ></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal panel -->
                <div
                    x-show="revokeModalOpen"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                >
                    <form :action="revokeActionUrl" method="POST">
                        @csrf
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Revoke Certificate
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            Are you sure you want to revoke the certificate for <span x-text="revokeRecipientName" class="font-semibold"></span>?
                                        </p>
                                        <div class="mt-4">
                                            <label for="revocation_reason" class="block text-sm font-medium text-gray-700">
                                                Reason for revocation
                                            </label>
                                            <input type="text"
                                                name="revocation_reason"
                                                id="revocation_reason"
                                                x-ref="revokeReasonInput"
                                                required
                                                maxlength="255"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                placeholder="Enter reason for revocation">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Confirm Revoke
                            </button>
                            <button type="button" @click="closeRevokeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
