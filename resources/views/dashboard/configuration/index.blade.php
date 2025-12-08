{{--
    Configuration Index View
    
    Expected variables:
    @var \Illuminate\Database\Eloquent\Collection<\App\Models\SmtpProvider> $smtpProviders - Collection of user's SMTP providers
    @var \Illuminate\Database\Eloquent\Collection<\App\Models\EmailTemplate> $userEmailTemplates - Collection of user's email templates
    @var \Illuminate\Database\Eloquent\Collection<\App\Models\EmailTemplate> $globalEmailTemplates - Collection of global email templates
    @var \Illuminate\Database\Eloquent\Collection<\App\Models\CertificateTemplate> $userCertificateTemplates - Collection of user's certificate templates
    @var \Illuminate\Database\Eloquent\Collection<\App\Models\CertificateTemplate> $globalCertificateTemplates - Collection of global certificate templates
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Configuration') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <!-- Tab Navigation -->
                <div x-data="{ activeTab: 'smtp' }" class="border-b border-gray-200">
                    <nav class="flex -mb-px" aria-label="Tabs">
                        <button 
                            @click="activeTab = 'smtp'" 
                            :class="activeTab === 'smtp' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm"
                        >
                            SMTP Settings
                        </button>
                        <button 
                            @click="activeTab = 'email'" 
                            :class="activeTab === 'email' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm"
                        >
                            Email Templates
                        </button>
                        <button 
                            @click="activeTab = 'certificate'" 
                            :class="activeTab === 'certificate' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm"
                        >
                            Certificate Templates
                        </button>
                    </nav>

                    <!-- Tab Content -->
                    <div class="p-6">
                        <!-- SMTP Settings Tab -->
                        <div x-show="activeTab === 'smtp'">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-lg font-semibold">SMTP Server Configuration</h3>
                                <a href="{{ route('dashboard.smtp.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Add SMTP Provider
                                </a>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Host</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From Email</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @forelse($smtpProviders as $provider)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ $provider->name }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $provider->host }}:{{ $provider->port }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $provider->from_address }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $provider->created_at->format('M d, Y') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                    <a href="{{ route('dashboard.smtp.edit', $provider) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                                    <form action="{{ route('dashboard.smtp.destroy', $provider) }}" method="POST" class="inline-block">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this SMTP provider?')">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                    No SMTP providers configured. Add one to start sending emails with your own server.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 text-sm text-gray-600">
                                <p><strong>Note:</strong> SMTP providers configured here will override the default SMTP settings for your account only.</p>
                            </div>
                        </div>

                        <!-- Email Templates Tab -->
                        <div x-show="activeTab === 'email'">
                            <div class="space-y-6">
                                <!-- Your Templates -->
                                <div>
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold">Your Email Templates</h3>
                                        <a href="{{ route('dashboard.templates.email.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            New Template
                                        </a>
                                    </div>

                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @forelse($userEmailTemplates as $template)
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                            {{ $template->name }}
                                                        </td>
                                                        <td class="px-6 py-4 text-sm text-gray-500">
                                                            {{ Str::limit($template->subject, 50) }}
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            {{ $template->created_at->format('M d, Y') }}
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                            <a href="{{ route('dashboard.templates.email.edit', $template) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                                            @if($template->original_template_id)
                                                                <form action="{{ route('dashboard.templates.email.reset', $template) }}" method="POST" class="inline-block">
                                                                    @csrf
                                                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900">Reset</button>
                                                                </form>
                                                            @endif
                                                            <form action="{{ route('dashboard.templates.email.destroy', $template) }}" method="POST" class="inline-block">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this template?')">
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                            No custom email templates. Create one or clone from global templates below.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Global Templates -->
                                <div>
                                    <h3 class="text-lg font-semibold mb-4">Global Email Templates</h3>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @forelse($globalEmailTemplates as $template)
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                            {{ $template->name }}
                                                        </td>
                                                        <td class="px-6 py-4 text-sm text-gray-500">
                                                            {{ Str::limit($template->subject, 50) }}
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                            <form action="{{ route('dashboard.templates.email.clone', $template) }}" method="POST" class="inline-block">
                                                                @csrf
                                                                <button type="submit" class="text-blue-600 hover:text-blue-900">Clone</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                            No global templates available.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Certificate Templates Tab -->
                        <div x-show="activeTab === 'certificate'">
                            <div class="space-y-6">
                                <!-- Your Templates -->
                                <div>
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold">Your Certificate Templates</h3>
                                        <a href="{{ route('dashboard.templates.certificates.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            New Template
                                        </a>
                                    </div>

                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @forelse($userCertificateTemplates as $template)
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                            {{ $template->name }}
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $template->type === 'svg' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800' }}">
                                                                {{ strtoupper($template->type) }}
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            {{ $template->created_at->format('M d, Y') }}
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                            <a href="{{ route('dashboard.templates.certificates.edit', $template) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                                            @if($template->original_template_id)
                                                                <form action="{{ route('dashboard.templates.certificates.reset', $template) }}" method="POST" class="inline-block">
                                                                    @csrf
                                                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900">Reset</button>
                                                                </form>
                                                            @endif
                                                            <form action="{{ route('dashboard.templates.certificates.destroy', $template) }}" method="POST" class="inline-block">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this template?')">
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                            No custom certificate templates. Create one or clone from global templates below.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Global Templates -->
                                <div>
                                    <h3 class="text-lg font-semibold mb-4">Global Certificate Templates</h3>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @forelse($globalCertificateTemplates as $template)
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                            {{ $template->name }}
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $template->type === 'svg' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800' }}">
                                                                {{ strtoupper($template->type) }}
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                            <form action="{{ route('dashboard.templates.certificates.clone', $template) }}" method="POST" class="inline-block">
                                                                @csrf
                                                                <button type="submit" class="text-blue-600 hover:text-blue-900">Clone</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                            No global templates available.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
