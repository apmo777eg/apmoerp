@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'placeholder' => null,
    'hint' => null,
    'error' => null,
    'icon' => null,
    'iconPosition' => 'left', // left, right
    'autocomplete' => null,
    'value' => null,
    'min' => null,
    'max' => null,
    'step' => null,
    'pattern' => null,
    'maxlength' => null,
    'dir' => null,
])

@php
$inputId = $name ?? 'input-' . Str::random(8);
$hasError = $error || ($errors->has($name) && $name);
$errorMessage = $error ?? ($name ? $errors->first($name) : null);
$hasIcon = !empty($icon);
$inputClasses = 'erp-input w-full transition-colors duration-200';

if ($hasError) {
    $inputClasses .= ' border-red-500 focus:border-red-600 focus:ring-red-500';
} else {
    $inputClasses .= ' border-slate-300 focus:border-emerald-500 focus:ring-emerald-500';
}

if ($disabled) {
    $inputClasses .= ' bg-slate-100 cursor-not-allowed opacity-60';
}

if ($hasIcon && $iconPosition === 'left') {
    $inputClasses .= ' pl-10';
}

if ($hasIcon && $iconPosition === 'right') {
    $inputClasses .= ' pr-10';
}

@endphp

<div {{ $attributes->only('class') }}>
    @if($label)
    <label for="{{ $inputId }}" class="erp-label block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
        {{ $label }}
        @if($required)
        <span class="text-red-500 ml-0.5" aria-label="{{ __('Required field') }}">*</span>
        @endif
    </label>
    @endif

    <div class="relative">
        @if($hasIcon && $iconPosition === 'left')
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <span class="text-slate-400">
                {!! sanitize_svg_icon($icon) !!}
            </span>
        </div>
        @endif

        <input
            type="{{ $type }}"
            id="{{ $inputId }}"
            name="{{ $name }}"
            class="{{ $inputClasses }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if($value !== null) value="{{ $value }}" @endif
            @if($min !== null) min="{{ $min }}" @endif
            @if($max !== null) max="{{ $max }}" @endif
            @if($step !== null) step="{{ $step }}" @endif
            @if($pattern) pattern="{{ $pattern }}" @endif
            @if($maxlength) maxlength="{{ $maxlength }}" @endif
            @if($dir) dir="{{ $dir }}" @endif
            @if($hasError) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @endif
            @if($hint && !$hasError) aria-describedby="{{ $inputId }}-hint" @endif
            {{ $attributes->except(['class', 'label', 'name', 'type']) }}
        />

        @if($hasIcon && $iconPosition === 'right')
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
            <span class="text-slate-400">
                {!! sanitize_svg_icon($icon) !!}
            </span>
        </div>
        @endif
    </div>

    @if($hint && !$hasError)
    <p id="{{ $inputId }}-hint" class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
        {{ $hint }}
    </p>
    @endif

    @if($hasError)
    <p id="{{ $inputId }}-error" class="mt-1.5 text-xs text-red-600 dark:text-red-400 flex items-start gap-1" role="alert">
        <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <span>{{ $errorMessage }}</span>
    </p>
    @endif
</div>
