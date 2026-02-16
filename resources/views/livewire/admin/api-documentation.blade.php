<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('API Documentation') }}</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Complete API reference for integrations') }}</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar Navigation -->
        <div class="lg:w-64 flex-shrink-0">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                <nav class="space-y-1">
                    @foreach($sections as $key => $label)
                        <button type="button"
                            wire:click="switchSection('{{ $key }}')"
                            class="w-full text-left px-4 py-2 rounded-md text-sm font-medium transition-colors
                                {{ $activeSection === $key
                                    ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'
                                    : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                            {{ __($label) }}
                        </button>
                    @endforeach
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                @if($activeSection === 'overview')
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ __('API Overview') }}</h2>
                    
                    <div class="prose dark:prose-invert max-w-none">
                        <p>{{ __('Welcome to the ERP API documentation. This API allows you to integrate with external stores, manage products, inventory, orders, and customers programmatically.') }}</p>
                        
                        <h3>{{ __('Base URL') }}</h3>
                        <code class="block bg-gray-100 dark:bg-gray-700 p-3 rounded-md text-sm">
                            {{ url('/api/v1') }}
                        </code>

                        <h3 class="mt-6">{{ __('Supported Platforms') }}</h3>
                        <ul class="list-disc pl-5 space-y-2">
                            <li><strong>Shopify</strong> - {{ __('Full integration with products, inventory, and orders sync') }}</li>
                            <li><strong>WooCommerce</strong> - {{ __('Connect WordPress stores with full CRUD support') }}</li>
                            <li><strong>Laravel API</strong> - {{ __('Connect to other Laravel applications using bearer tokens') }}</li>
                            <li><strong>Custom API</strong> - {{ __('Build custom integrations using our REST endpoints') }}</li>
                        </ul>

                        <h3 class="mt-6">{{ __('Rate Limiting') }}</h3>
                        <p>{{ __('API requests are limited to 120 requests per minute. The following headers are included in all responses:') }}</p>
                        <ul class="list-disc pl-5">
                            <li><code>X-RateLimit-Limit</code> - {{ __('Maximum requests per window') }}</li>
                            <li><code>X-RateLimit-Remaining</code> - {{ __('Remaining requests in current window') }}</li>
                        </ul>
                    </div>

                @elseif($activeSection === 'authentication')
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ __('Authentication') }}</h2>
                    
                    <div class="prose dark:prose-invert max-w-none">
                        <p>{{ __('The API supports two authentication methods:') }}</p>
                        
                        <h3>{{ __('1. Store API Token') }}</h3>
                        <p>{{ __('For store integrations, use the API token generated in Admin → Stores. Include the token in the request header:') }}</p>
                        <code class="block bg-gray-100 dark:bg-gray-700 p-3 rounded-md text-sm">
                            Authorization: Bearer YOUR_STORE_API_TOKEN
                        </code>

                        <h3 class="mt-6">{{ __('2. Laravel Sanctum') }}</h3>
                        <p>{{ __('For user-authenticated requests, use Laravel Sanctum tokens:') }}</p>
                        <code class="block bg-gray-100 dark:bg-gray-700 p-3 rounded-md text-sm">
                            Authorization: Bearer YOUR_SANCTUM_TOKEN
                        </code>

                        <h3 class="mt-6">{{ __('Generating Store API Token') }}</h3>
                        <ol class="list-decimal pl-5 space-y-2">
                            <li>{{ __('Go to Admin → Stores') }}</li>
                            <li>{{ __('Create or edit a store') }}</li>
                            <li>{{ __('Click "Generate API Token"') }}</li>
                            <li>{{ __('Copy and securely store the token') }}</li>
                        </ol>

                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md p-4 mt-6">
                            <p class="text-yellow-800 dark:text-yellow-200 text-sm">
                                <strong>{{ __('Important:') }}</strong> {{ __('API tokens should be kept secret. Never expose them in client-side code or version control.') }}
                            </p>
                        </div>
                    </div>

                @elseif($activeSection === 'products')
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ __('Products API') }}</h2>
                    @include('livewire.admin.partials.api-endpoints', ['endpoints' => $endpoints['products']])

                @elseif($activeSection === 'inventory')
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ __('Inventory API') }}</h2>
                    @include('livewire.admin.partials.api-endpoints', ['endpoints' => $endpoints['inventory']])

                @elseif($activeSection === 'orders')
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ __('Orders API') }}</h2>
                    @include('livewire.admin.partials.api-endpoints', ['endpoints' => $endpoints['orders']])

                @elseif($activeSection === 'customers')
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ __('Customers API') }}</h2>
                    @include('livewire.admin.partials.api-endpoints', ['endpoints' => $endpoints['customers']])

                @elseif($activeSection === 'webhooks')
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ __('Webhooks') }}</h2>
                    
                    <div class="prose dark:prose-invert max-w-none mb-6">
                        <p>{{ __('Webhooks allow external platforms to send real-time updates to your ERP system. Configure webhooks in your Shopify or WooCommerce store to point to these endpoints.') }}</p>
                    </div>
                    
                    @include('livewire.admin.partials.api-endpoints', ['endpoints' => $endpoints['webhooks']])

                @elseif($activeSection === 'errors')
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ __('Error Handling') }}</h2>
                    
                    <div class="prose dark:prose-invert max-w-none">
                        <p>{{ __('The API uses standard HTTP status codes to indicate success or failure:') }}</p>
                        
                        <div class="overflow-x-auto mt-4">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Code') }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Status') }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('Description') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-green-600 font-mono">200</td>
                                        <td class="px-4 py-3 text-sm">{{ __('OK') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ __('Request succeeded') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-green-600 font-mono">201</td>
                                        <td class="px-4 py-3 text-sm">{{ __('Created') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ __('Resource created successfully') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-yellow-600 font-mono">400</td>
                                        <td class="px-4 py-3 text-sm">{{ __('Bad Request') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ __('Invalid request parameters') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-red-600 font-mono">401</td>
                                        <td class="px-4 py-3 text-sm">{{ __('Unauthorized') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ __('Missing or invalid authentication') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-red-600 font-mono">403</td>
                                        <td class="px-4 py-3 text-sm">{{ __('Forbidden') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ __('Insufficient permissions') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-red-600 font-mono">404</td>
                                        <td class="px-4 py-3 text-sm">{{ __('Not Found') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ __('Resource not found') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-red-600 font-mono">422</td>
                                        <td class="px-4 py-3 text-sm">{{ __('Unprocessable Entity') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ __('Validation errors') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-orange-600 font-mono">429</td>
                                        <td class="px-4 py-3 text-sm">{{ __('Too Many Requests') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ __('Rate limit exceeded') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-red-600 font-mono">500</td>
                                        <td class="px-4 py-3 text-sm">{{ __('Server Error') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ __('Internal server error') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h3 class="mt-6">{{ __('Error Response Format') }}</h3>
                        <pre class="bg-gray-100 dark:bg-gray-700 p-4 rounded-md text-sm overflow-x-auto">
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "name": ["The name field is required."],
        "email": ["The email must be a valid email address."]
    }
}</pre>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
