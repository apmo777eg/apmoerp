{{-- resources/views/components/ui/form/input.blade.php --}}
@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'placeholder' => null,
    'required' => false,
    'error' => null,
    'hint' => null,
    'icon' => null,
    'iconPosition' => 'left', // left, right
    'autocomplete' => null,
    'wireModel' => null, // Livewire 4: wire:model binding
    'wireModifier' => 'blur', // Livewire 4: live, blur, change, defer
    'realTimeValidation' => false, // Enable real-time validation
    'showSuccess' => false, // Show success state when valid
])

@php
    $describedBy = trim(($error && $name ? $name . '-error ' : '') . ($hint && $name ? $name . '-hint' : ''));
    $hasValue = $attributes->get('value');
    $isValid = !$error && $hasValue && $showSuccess;
    
    // Build wire:model directive
    $wireDirective = '';
    if ($wireModel) {
        $wireDirective = match($wireModifier) {
            'live' => "wire:model.live=\"{$wireModel}\"",
            'blur' => "wire:model.blur=\"{$wireModel}\"",
            'change' => "wire:model.change=\"{$wireModel}\"",
            'defer' => "wire:model=\"{$wireModel}\"",
            default => "wire:model.blur=\"{$wireModel}\"",
        };
    }
@endphp

<div class="space-y-1">
    @if($label)
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
        {{ $label }}
        @if($required)
        <span class="text-red-500">*</span>
        @endif
    </label>
    @endif
    
    <div class="relative">
        @if($icon && $iconPosition === 'left')
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            {!! sanitize_svg_icon($icon) !!}
        </div>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $name }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $autocomplete ? "autocomplete=\"$autocomplete\"" : '' }}
            @if($wireModel) {!! $wireDirective !!} @endif
            @if($realTimeValidation && $wireModel) wire:blur="validateOnly('{{ $wireModel }}')" @endif
            {{ $attributes->merge([
                'class' => 'block w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 focus:ring-emerald-500 focus:border-emerald-500 placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm min-h-[44px] text-sm sm:text-base transition-colors ' .
                ($icon && $iconPosition === 'left' ? 'pl-10 ' : '') .
                ($icon && $iconPosition === 'right' ? 'pr-10 ' : '') .
                ($error ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50 dark:bg-red-900/10 ' : '') .
                ($isValid ? 'border-emerald-300 focus:border-emerald-500 focus:ring-emerald-500 ' : '')
            ])->merge([
                'aria-invalid' => $error ? 'true' : 'false',
                'aria-required' => $required ? 'true' : 'false',
                'aria-describedby' => $describedBy ?: null,
            ]) }}
        />

        @if($icon && $iconPosition === 'right')
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
            {!! sanitize_svg_icon($icon) !!}
        </div>
        @elseif($error)
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
        </div>
        @elseif($isValid)
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
        </div>
        @endif
    </div>

    @if($error)
    <p id="{{ $name }}-error" class="flex items-center gap-1 text-sm text-red-600 dark:text-red-400">
        <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        {{ $error }}
    </p>
    @endif

    @if($hint && !$error)
    <p id="{{ $name }}-hint" class="text-sm text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif
</div>
