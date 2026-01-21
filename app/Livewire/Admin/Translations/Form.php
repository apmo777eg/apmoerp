<?php

namespace App\Livewire\Admin\Translations;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    use AuthorizesRequests;
    
    public $translationKey = '';

    public $group = 'app';

    public $valueEn = '';

    public $valueAr = '';

    public $isEdit = false;

    public $originalKey = '';

    public function mount()
    {
        // V57-HIGH-01 FIX: Add authorization for translations management
        $user = Auth::user();
        if (! $user || ! $user->can('settings.translations.manage')) {
            abort(403);
        }
        
        // Handle query string parameters for editing
        if (request()->has('key') && request()->has('group')) {
            // URL decode the key since it's passed via URL
            $decodedKey = urldecode(request()->get('key'));
            $decodedGroup = request()->get('group');

            // Security: Validate key and group format before assignment
            if (! preg_match('/^[a-zA-Z0-9_.-]+$/', $decodedKey) || ! preg_match('/^[a-zA-Z0-9_-]+$/', $decodedGroup)) {
                // Invalid format, redirect back with error
                session()->flash('error', __('Invalid translation key or group format.'));
                $this->redirectRoute('admin.translations.index', navigate: true);
            }

            $this->isEdit = true;
            $this->originalKey = $decodedKey;
            $this->group = $decodedGroup;
            $this->loadTranslation($this->originalKey, $this->group);
        }
    }

    protected function loadTranslation($key, $group)
    {
        // Security: Validate group to prevent path traversal
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $group)) {
            return;
        }

        // Extract just the key without group prefix
        $keyWithoutGroup = $key;
        if (str_starts_with($key, $group.'.')) {
            $keyWithoutGroup = substr($key, strlen($group) + 1);
        }

        $this->translationKey = $keyWithoutGroup;

        // Load English value
        $enPath = lang_path("en/{$group}.php");
        if (File::exists($enPath)) {
            // Security: Verify the file is within the lang directory
            $realPath = realpath($enPath);
            $langBasePath = realpath(lang_path());
            if ($realPath && $langBasePath && str_starts_with($realPath, $langBasePath)) {
                $translations = include $enPath;
                $this->valueEn = $this->getNestedValue($translations, $keyWithoutGroup) ?? '';
            }
        }

        // Load Arabic value
        $arPath = lang_path("ar/{$group}.php");
        if (File::exists($arPath)) {
            // Security: Verify the file is within the lang directory
            $realPath = realpath($arPath);
            $langBasePath = realpath(lang_path());
            if ($realPath && $langBasePath && str_starts_with($realPath, $langBasePath)) {
                $translations = include $arPath;
                $this->valueAr = $this->getNestedValue($translations, $keyWithoutGroup) ?? '';
            }
        }
    }

    protected function getNestedValue($array, $key)
    {
        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $k) {
            if (! isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }

        return is_string($value) ? $value : null;
    }

    public function getTranslationGroups()
    {
        $groups = ['app']; // Always include 'app' as default
        $langPath = lang_path('en');

        if (File::isDirectory($langPath)) {
            $files = File::files($langPath);
            foreach ($files as $file) {
                $groups[] = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            }
        }

        // Return unique groups, ensuring 'app' is always included
        return array_values(array_unique($groups));
    }

    public function save()
    {
        $this->validate([
            'translationKey' => 'required|string|max:255|regex:/^[a-zA-Z0-9_.]+$/',
            'group' => 'required|string|max:50|alpha_dash',
            'valueEn' => 'required|string|max:2000',
            'valueAr' => 'required|string|max:2000',
        ], [
            'translationKey.regex' => __('Translation key can only contain letters, numbers, underscores, and dots.'),
        ]);

        // Sanitize the key to prevent code injection
        $sanitizedKey = preg_replace('/[^a-zA-Z0-9_.]/', '', $this->translationKey);

        // Check if key already exists when adding new
        if (! $this->isEdit) {
            $fullKey = "{$this->group}.{$sanitizedKey}";
            if ($this->translationExists($this->group, $sanitizedKey)) {
                $this->addError('translationKey', __('This translation key already exists.'));

                return;
            }
        }

        // Save English translation
        $this->saveToFile('en', $this->group, $sanitizedKey, $this->valueEn);

        // Save Arabic translation
        $this->saveToFile('ar', $this->group, $sanitizedKey, $this->valueAr);

        // Clear cache
        Cache::forget('translations.en');
        Cache::forget('translations.ar');

        $message = $this->isEdit
            ? __('Translation updated successfully.')
            : __('Translation added successfully.');

        session()->flash('success', $message);

        $this->redirectRoute('admin.translations.index', navigate: true);
    }

    protected function translationExists($group, $key)
    {
        // Security: Validate group to prevent path traversal
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $group)) {
            return false;
        }

        $filePath = lang_path("en/{$group}.php");

        if (! File::exists($filePath)) {
            return false;
        }

        // Security: Verify the file is within the lang directory
        $realPath = realpath($filePath);
        $langBasePath = realpath(lang_path());
        if (! $realPath || ! $langBasePath || ! str_starts_with($realPath, $langBasePath)) {
            return false;
        }

        $translations = include $filePath;

        return $this->getNestedValue($translations, $key) !== null;
    }

    protected function saveToFile($locale, $group, $key, $value)
    {
        // Security: Validate locale and group to prevent path traversal
        if (! preg_match('/^[a-z]{2}$/', $locale) || ! preg_match('/^[a-zA-Z0-9_-]+$/', $group)) {
            return;
        }

        $filePath = lang_path("{$locale}/{$group}.php");

        // Load existing translations or create empty array
        $translations = [];
        if (File::exists($filePath)) {
            // Security: Verify the file is within the lang directory before including
            $realPath = realpath($filePath);
            $langBasePath = realpath(lang_path());
            if ($realPath && $langBasePath && str_starts_with($realPath, $langBasePath)) {
                $translations = include $filePath;
            }
        }

        // Sanitize the value to prevent code injection
        // Remove any PHP tags or code-like patterns
        $sanitizedValue = $this->sanitizeTranslationValue($value);

        // Handle nested keys
        $keys = explode('.', $key);
        $current = &$translations;

        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $current[$k] = $sanitizedValue;
            } else {
                if (! isset($current[$k]) || ! is_array($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
        }

        // Save to file using var_export for safe PHP array generation
        // var_export properly escapes strings and generates valid PHP syntax
        $content = "<?php\n\nreturn ".var_export($translations, true).";\n";

        // Ensure directory exists
        $directory = dirname($filePath);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($filePath, $content);
    }

    /**
     * Sanitize translation value to prevent code injection
     */
    protected function sanitizeTranslationValue(string $value): string
    {
        // Remove PHP tags
        $value = preg_replace('/<\?php|\?>|<\?=?/i', '', $value);

        // Remove potential function calls pattern
        $value = preg_replace('/\b(eval|exec|system|shell_exec|passthru|proc_open|popen)\s*\(/i', '', $value);

        return $value;
    }

    public function cancel()
    {
        $this->redirectRoute('admin.translations.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.translations.form', [
            'groups' => $this->getTranslationGroups(),
        ]);
    }
}
