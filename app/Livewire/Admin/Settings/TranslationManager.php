<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class TranslationManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $selectedLang = 'ar';

    public string $newKey = '';

    public string $newValueAr = '';

    public string $newValueEn = '';

    public array $translations = [];

    public array $editingTranslations = [];

    public bool $showAddModal = false;

    public bool $showEditModal = false;

    public string $editKey = '';

    public string $editValueAr = '';

    public string $editValueEn = '';

    protected function rules(): array
    {
        return [
            'newKey' => ['required', 'string', 'min:2', 'max:255', 'regex:/^[a-zA-Z0-9\s\.\-\_\:\,\!\?\(\)\'\"]+$/'],
            'newValueAr' => ['required', 'string', 'max:1000'],
            'newValueEn' => ['required', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'newKey.regex' => __('Translation key can only contain letters, numbers, spaces, and basic punctuation.'),
        ];
    }

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('settings.translations.manage')) {
            abort(403, 'Unauthorized');
        }

        $this->loadTranslations();
    }

    protected function sanitizeKey(string $key): string
    {
        $key = trim($key);
        $key = preg_replace('/[^\p{L}\p{N}\s\.\-\_\:\,\!\?\(\)\'\"]/u', '', $key);
        $key = preg_replace('/\.{2,}/', '.', $key);
        $key = preg_replace('/[\/\\\\]/', '', $key);

        return mb_substr($key, 0, 255);
    }

    protected function isValidKey(string $key): bool
    {
        if (empty($key) || mb_strlen($key) < 2 || mb_strlen($key) > 255) {
            return false;
        }

        if (preg_match('/[\/\\\\]|\.\./', $key)) {
            return false;
        }

        return true;
    }

    public function loadTranslations(): void
    {
        $arPath = lang_path('ar.json');
        $enPath = lang_path('en.json');

        $arTranslations = File::exists($arPath) ? json_decode(File::get($arPath), true) ?? [] : [];
        $enTranslations = File::exists($enPath) ? json_decode(File::get($enPath), true) ?? [] : [];

        $allKeys = array_unique(array_merge(array_keys($arTranslations), array_keys($enTranslations)));
        sort($allKeys);

        $this->translations = [];
        foreach ($allKeys as $key) {
            $this->translations[$key] = [
                'ar' => $arTranslations[$key] ?? '',
                'en' => $enTranslations[$key] ?? $key,
            ];
        }
    }

    public function getFilteredTranslations(): array
    {
        if (empty($this->search)) {
            return $this->translations;
        }

        $search = mb_strtolower($this->search);

        return array_filter($this->translations, function ($values, $key) use ($search) {
            return str_contains(mb_strtolower($key), $search)
                || str_contains(mb_strtolower($values['ar']), $search)
                || str_contains(mb_strtolower($values['en']), $search);
        }, ARRAY_FILTER_USE_BOTH);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openAddModal(): void
    {
        $this->reset(['newKey', 'newValueAr', 'newValueEn']);
        $this->showAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->reset(['newKey', 'newValueAr', 'newValueEn']);
    }

    public function addTranslation(): void
    {
        $this->validate();

        $sanitizedKey = $this->sanitizeKey($this->newKey);

        if (! $this->isValidKey($sanitizedKey)) {
            $this->addError('newKey', __('Invalid translation key. Please use only letters, numbers, spaces, and basic punctuation.'));

            return;
        }

        if (isset($this->translations[$sanitizedKey])) {
            $this->addError('newKey', __('This translation key already exists.'));

            return;
        }

        $this->saveTranslation($sanitizedKey, $this->newValueAr, $this->newValueEn);
        $this->closeAddModal();
        $this->loadTranslations();

        session()->flash('success', __('Translation added successfully.'));
    }

    public function openEditModal(string $key): void
    {
        $this->editKey = $key;
        $this->editValueAr = $this->translations[$key]['ar'] ?? '';
        $this->editValueEn = $this->translations[$key]['en'] ?? $key;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->reset(['editKey', 'editValueAr', 'editValueEn']);
    }

    public function updateTranslation(): void
    {
        $this->validate([
            'editValueAr' => ['required', 'string', 'max:1000'],
            'editValueEn' => ['required', 'string', 'max:1000'],
        ]);

        if (! $this->isValidKey($this->editKey)) {
            $this->addError('editValueAr', __('Invalid translation key.'));

            return;
        }

        if (! isset($this->translations[$this->editKey])) {
            $this->addError('editValueAr', __('Translation key not found.'));

            return;
        }

        $this->saveTranslation($this->editKey, $this->editValueAr, $this->editValueEn);
        $this->closeEditModal();
        $this->loadTranslations();

        session()->flash('success', __('Translation updated successfully.'));
    }

    public function deleteTranslation(string $key): void
    {
        if (! $this->isValidKey($key)) {
            session()->flash('error', __('Invalid translation key.'));

            return;
        }

        if (! isset($this->translations[$key])) {
            session()->flash('error', __('Translation key not found.'));

            return;
        }

        $arPath = lang_path('ar.json');
        $enPath = lang_path('en.json');

        $arTranslations = File::exists($arPath) ? json_decode(File::get($arPath), true) ?? [] : [];
        $enTranslations = File::exists($enPath) ? json_decode(File::get($enPath), true) ?? [] : [];

        unset($arTranslations[$key], $enTranslations[$key]);

        File::put($arPath, json_encode($arTranslations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        File::put($enPath, json_encode($enTranslations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $this->loadTranslations();

        session()->flash('success', __('Translation deleted successfully.'));
    }

    protected function saveTranslation(string $key, string $arValue, string $enValue): void
    {
        $sanitizedKey = $this->sanitizeKey($key);

        if (! $this->isValidKey($sanitizedKey)) {
            throw new \InvalidArgumentException('Invalid translation key');
        }

        $arPath = lang_path('ar.json');
        $enPath = lang_path('en.json');

        $arTranslations = File::exists($arPath) ? json_decode(File::get($arPath), true) ?? [] : [];
        $enTranslations = File::exists($enPath) ? json_decode(File::get($enPath), true) ?? [] : [];

        $arTranslations[$sanitizedKey] = strip_tags($arValue);
        $enTranslations[$sanitizedKey] = strip_tags($enValue);

        ksort($arTranslations);
        ksort($enTranslations);

        File::put($arPath, json_encode($arTranslations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        File::put($enPath, json_encode($enTranslations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public function render()
    {
        $filtered = $this->getFilteredTranslations();
        $totalCount = count($filtered);

        // Paginate the filtered translations manually
        $perPage = 50;
        $page = $this->getPage();
        $offset = ($page - 1) * $perPage;

        $paginated = array_slice($filtered, $offset, $perPage, true);

        // Calculate pagination data
        $hasMore = $totalCount > ($offset + $perPage);
        $hasPrevious = $page > 1;

        return view('livewire.admin.settings.translation-manager', [
            'filteredTranslations' => $paginated,
            'totalCount' => $totalCount,
            'perPage' => $perPage,
            'currentPage' => $page,
            'hasMore' => $hasMore,
            'hasPrevious' => $hasPrevious,
            'lastPage' => (int) ceil($totalCount / $perPage),
        ]);
    }
}
