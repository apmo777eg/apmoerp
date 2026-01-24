<div>
{{-- resources/views/livewire/shared/error-message.blade.php --}}
@if ($message)
    <div class="rounded-xl bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700">
        {{ $message }}
    </div>
@endif
</div>
