<div class="lw-component">
    <button type="button" wire:click="openModal" class="erp-btn erp-btn-secondary flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        {{ __('Export') }}
    </button>

    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="relative inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-slate-800">
                            {{ __('Select Columns to Export') }}
                        </h3>
                        <button type="button" wire:click="closeModal" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex gap-2 mb-4">
                        <button type="button" wire:click="selectAll" class="text-sm text-emerald-600 hover:text-emerald-700">
                            {{ __('Select All') }}
                        </button>
                        <span class="text-slate-300">|</span>
                        <button type="button" wire:click="deselectAll" class="text-sm text-slate-600 hover:text-slate-700">
                            {{ __('Deselect All') }}
                        </button>
                    </div>

                    <div class="max-h-64 overflow-y-auto space-y-2 border rounded-lg p-3">
                        @foreach($availableColumns as $key => $label)
                            <label class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded cursor-pointer">
                                <input type="checkbox" 
                                       wire:click="toggleColumn('{{ e($key) }}')"
                                       @checked(in_array($key, $selectedColumns))
                                       class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500">
                                <span class="text-sm text-slate-700">{{ __($label) }}</span>
                            </label>
                        @endforeach
                    </div>

                    @if(session('error'))
                        <div class="mt-3 p-2 bg-red-50 text-red-700 text-sm rounded">
                            {{ session('error') }}
                        </div>
                    @endif
                </div>

                <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button type="button" wire:click="export" class="erp-btn-primary">
                        {{ __('Export') }} ({{ count($selectedColumns) }} {{ __('columns') }})
                    </button>
                    <button type="button" wire:click="closeModal" class="erp-btn erp-btn-secondary">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
