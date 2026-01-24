<div>
@php
    $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
    $gridCols = match($columns ?? 1) {
        2 => 'md:grid-cols-2',
        3 => 'md:grid-cols-3',
        4 => 'md:grid-cols-4',
        default => 'grid-cols-1'
    };
@endphp
<form wire:submit.prevent="submit" class="space-y-6 {{ $dir === 'rtl' ? 'text-right' : 'text-left' }}">

    <div class="grid {{ $gridCols }} gap-4">
        @foreach ($schema as $field)
            @php
                $name = $field['name'] ?? null;
                $label = $field['label'] ?? $name;
                $type = $field['type'] ?? 'text';
                $options = $field['options'] ?? [];
                $required = (bool) ($field['required'] ?? false);
                $placeholder = $field['placeholder'] ?? '';
                $hint = $field['hint'] ?? '';
                $icon = $field['icon'] ?? null;
                $colSpan = $field['col_span'] ?? 1;
                $rows = $field['rows'] ?? 3;
                $min = $field['min'] ?? null;
                $max = $field['max'] ?? null;
                $step = $field['step'] ?? null;
                $disabled = $field['disabled'] ?? false;
                $readonly = $field['readonly'] ?? false;
            @endphp

            @if ($name)
                <div class="{{ $colSpan > 1 ? 'md:col-span-' . $colSpan : '' }} space-y-1.5">
                    {{-- Label --}}
                    @if ($label && $type !== 'hidden')
                        <label for="field-{{ $name }}" class="erp-label flex items-center gap-1.5">
                            @if ($icon)
                                {{-- SECURITY: sanitize_svg_icon uses allow-list based DOM sanitization --}}
                                <span class="text-slate-400">{!! sanitize_svg_icon($icon) !!}</span>
                            @endif
                            <span>{{ __($label) }}</span>
                            @if ($required)
                                <span class="text-red-500 text-xs">*</span>
                            @endif
                        </label>
                    @endif

                    {{-- Input Field --}}
                    <div class="relative">
                        @switch($type)
                            @case('hidden')
                                <input type="hidden" wire:model="data.{{ $name }}" id="field-{{ $name }}">
                                @break

                            @case('textarea')
                                <textarea 
                                    wire:model="data.{{ $name }}"
                                    id="field-{{ $name }}"
                                    rows="{{ $rows }}"
                                    placeholder="{{ __($placeholder) }}"
                                    class="erp-input min-h-[80px] resize-y @error('data.' . $name) border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    {{ $disabled ? 'disabled' : '' }}
                                    {{ $readonly ? 'readonly' : '' }}
                                >{{ $data[$name] ?? '' }}</textarea>
                                @break

                            @case('select')
                                <select 
                                    wire:model="data.{{ $name }}"
                                    id="field-{{ $name }}"
                                    class="erp-input @error('data.' . $name) border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    {{ $disabled ? 'disabled' : '' }}
                                >
                                    <option value="">{{ __($placeholder ?: 'Choose...') }}</option>
                                    @foreach ($options as $value => $text)
                                        <option value="{{ $value }}" {{ isset($data[$name]) && $data[$name] == $value ? 'selected' : '' }}>{{ __($text) }}</option>
                                    @endforeach
                                </select>
                                @break

                            @case('radio')
                                <div class="flex flex-wrap gap-4 mt-1">
                                    @foreach ($options as $value => $text)
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input 
                                                type="radio" 
                                                wire:model="data.{{ $name }}"
                                                value="{{ $value }}"
                                                {{ isset($data[$name]) && $data[$name] == $value ? 'checked' : '' }}
                                                class="w-4 h-4 text-emerald-600 border-slate-300 focus:ring-emerald-500"
                                                {{ $disabled ? 'disabled' : '' }}
                                            >
                                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ __($text) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @break

                            @case('checkbox')
                            @case('boolean')
                                <label class="inline-flex items-center gap-2.5 cursor-pointer mt-1">
                                    <input 
                                        type="checkbox"
                                        wire:model="data.{{ $name }}"
                                        id="field-{{ $name }}"
                                        class="w-5 h-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 focus:ring-offset-0"
                                        value="1"
                                        {{ isset($data[$name]) && $data[$name] ? 'checked' : '' }}
                                        {{ $disabled ? 'disabled' : '' }}
                                    >
                                    <span class="text-sm text-slate-600 dark:text-slate-400">{{ __($hint ?: $label) }}</span>
                                </label>
                                @break

                            @case('number')
                                <input 
                                    type="number"
                                    wire:model="data.{{ $name }}"
                                    id="field-{{ $name }}"
                                    placeholder="{{ __($placeholder) }}"
                                    value="{{ $data[$name] ?? '' }}"
                                    class="erp-input @error('data.' . $name) border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    {{ $min !== null ? 'min=' . $min : '' }}
                                    {{ $max !== null ? 'max=' . $max : '' }}
                                    {{ $step !== null ? 'step=' . $step : '' }}
                                    {{ $disabled ? 'disabled' : '' }}
                                    {{ $readonly ? 'readonly' : '' }}
                                >
                                @break

                            @case('date')
                                <input 
                                    type="date"
                                    wire:model="data.{{ $name }}"
                                    id="field-{{ $name }}"
                                    value="{{ $data[$name] ?? '' }}"
                                    class="erp-input @error('data.' . $name) border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    {{ $min ? 'min=' . $min : '' }}
                                    {{ $max ? 'max=' . $max : '' }}
                                    {{ $disabled ? 'disabled' : '' }}
                                    {{ $readonly ? 'readonly' : '' }}
                                >
                                @break

                            @case('datetime')
                                <input 
                                    type="datetime-local"
                                    wire:model.blur="data.{{ $name }}"
                                    id="field-{{ $name }}"
                                    class="erp-input @error('data.' . $name) border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    {{ $disabled ? 'disabled' : '' }}
                                    {{ $readonly ? 'readonly' : '' }}
                                >
                                @break

                            @case('time')
                                <input 
                                    type="time"
                                    wire:model.blur="data.{{ $name }}"
                                    id="field-{{ $name }}"
                                    class="erp-input @error('data.' . $name) border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    {{ $disabled ? 'disabled' : '' }}
                                    {{ $readonly ? 'readonly' : '' }}
                                >
                                @break

                            @case('email')
                                <input 
                                    type="email"
                                    wire:model="data.{{ $name }}"
                                    id="field-{{ $name }}"
                                    placeholder="{{ __($placeholder) }}"
                                    value="{{ $data[$name] ?? '' }}"
                                    class="erp-input @error('data.' . $name) border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    {{ $disabled ? 'disabled' : '' }}
                                    {{ $readonly ? 'readonly' : '' }}
                                >
                                @break

                            @case('password')
                                <input 
                                    type="password"
                                    wire:model.blur="data.{{ $name }}"
                                    id="field-{{ $name }}"
                                    placeholder="{{ __($placeholder) }}"
                                    autocomplete="new-password"
                                    class="erp-input @error('data.' . $name) border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    {{ $disabled ? 'disabled' : '' }}
                                >
                                @break

                            @case('tel')
                            @case('phone')
                                <input 
                                    type="tel"
                                    wire:model="data.{{ $name }}"
                                    id="field-{{ $name }}"
                                    placeholder="{{ __($placeholder) }}"
                                    value="{{ $data[$name] ?? '' }}"
                                    class="erp-input @error('data.' . $name) border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    {{ $disabled ? 'disabled' : '' }}
                                    {{ $readonly ? 'readonly' : '' }}
                                >
                                @break

                            @case('url')
                                <input 
                                    type="url"
                                    wire:model="data.{{ $name }}"
                                    id="field-{{ $name }}"
                                    placeholder="{{ __($placeholder ?: 'https://') }}"
                                    value="{{ $data[$name] ?? '' }}"
                                    class="erp-input @error('data.' . $name) border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    {{ $disabled ? 'disabled' : '' }}
                                    {{ $readonly ? 'readonly' : '' }}
                                >
                                @break

                            @case('color')
                                <input 
                                    type="color"
                                    wire:model.blur="data.{{ $name }}"
                                    id="field-{{ $name }}"
                                    class="h-10 w-20 rounded-lg border border-slate-200 cursor-pointer"
                                    {{ $disabled ? 'disabled' : '' }}
                                >
                                @break

                            @case('file')
                                <input 
                                    type="file"
                                    wire:model="data.{{ $name }}"
                                    id="field-{{ $name }}"
                                    class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer"
                                    {{ $disabled ? 'disabled' : '' }}
                                >
                                @break

                            @case('money')
                            @case('currency')
                                <div class="relative">
                                    <span class="absolute {{ $dir === 'rtl' ? 'right-3' : 'left-3' }} top-1/2 -translate-y-1/2 text-slate-400 text-sm">{{ $field['currency'] ?? '$' }}</span>
                                    <input 
                                        type="number"
                                        wire:model.blur="data.{{ $name }}"
                                        id="field-{{ $name }}"
                                        placeholder="0.00"
                                        step="0.01"
                                        min="0"
                                        class="erp-input {{ $dir === 'rtl' ? 'pr-8' : 'pl-8' }} @error('data.' . $name) border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                        {{ $disabled ? 'disabled' : '' }}
                                        {{ $readonly ? 'readonly' : '' }}
                                    >
                                </div>
                                @break

                            @default
                                <input 
                                    type="text"
                                    wire:model="data.{{ $name }}"
                                    id="field-{{ $name }}"
                                    placeholder="{{ __($placeholder) }}"
                                    value="{{ $data[$name] ?? '' }}"
                                    class="erp-input @error('data.' . $name) border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    {{ $disabled ? 'disabled' : '' }}
                                    {{ $readonly ? 'readonly' : '' }}
                                >
                        @endswitch

                        {{-- Loading indicator --}}
                        <div wire:loading.delay wire:target="data.{{ $name }}" class="absolute {{ $dir === 'rtl' ? 'left-3' : 'right-3' }} top-1/2 -translate-y-1/2">
                            <svg class="animate-spin h-4 w-4 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>

                    {{-- Hint Text --}}
                    @if ($hint && !in_array($type, ['checkbox', 'boolean']))
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __($hint) }}</p>
                    @endif

                    {{-- Error Message --}}
                    @error('data.' . $name)
                        <p class="text-xs text-red-500 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            @endif
        @endforeach
    </div>

    {{-- Form Actions --}}
    <div class="flex items-center gap-3 pt-4 border-t border-slate-200 dark:border-slate-700 {{ $dir === 'rtl' ? 'flex-row-reverse' : '' }}">
        <button type="submit" class="erp-btn-primary flex items-center gap-2" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="submit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </span>
            <span wire:loading wire:target="submit">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </span>
            {{ $submitLabel }}
        </button>

        @if ($showCancel)
            @if ($cancelRoute)
                <a href="{{ $cancelRoute }}" class="erp-btn-secondary">
                    {{ $cancelLabel }}
                </a>
            @else
                <button type="button" wire:click="$dispatch('formCancelled')" class="erp-btn-secondary">
                    {{ $cancelLabel }}
                </button>
            @endif
        @endif
    </div>
</form>
</div>