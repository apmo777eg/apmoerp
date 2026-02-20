<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\BaseComponent as Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class TranslationManager extends Component
{
    use WithPagination;

    #[Url]
    public $search = '';

    #[Url]
    public $selectedGroup = '';

    public $selectedLocale = 'en';

    public $showAddModal = false;

    public $showEditModal = false;

    // Add form fields
    public $newKey = '';

    public $newGroup = 'app';

    public $newValueEn = '';

    public $newValueAr = '';

    // Edit form fields
    public $editKey = '';

    public $editGroup = '';

    public $editValueEn = '';

    public $editValueAr = '';

    public function mount()
    {
        $this->loadGroups();
    }

    public function loadGroups()
    {
        return $this->getTranslationGroups();
    }

    public function getTranslationGroups()
    {
        $groups = [];
        $langPath = lang_path('en');

        if (File::isDirectory($langPath)) {
            $files = File::files($langPath);
            foreach ($files as $file) {
                $groups[] = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            }
        }

        return array_unique($groups);
    }

    public function getTranslations()
    {
        $translations = [];
        $locales = ['en', 'ar'];

        foreach ($locales as $locale) {
            $langPath = lang_path($locale);

            if (! File::isDirectory($langPath)) {
                continue;
            }

            $files = File::files($langPath);

            foreach ($files as $file) {
                $group = pathinfo($file->getFilename(), PATHINFO_FILENAME);

                // Filter by selected group
                if ($this->selectedGroup && $group !== $this->selectedGroup) {
                    continue;
                }

                $content = include $file->getPathname();

                if (is_array($content)) {
                    $this->flattenTranslations($content, $group, $locale, $translations);
                }
            }
        }

        // Filter by search term
        if ($this->search) {
            $search = strtolower($this->search);
            $translations = array_filter($translations, function ($item) use ($search) {
                return str_contains(strtolower($item['key']), $search) ||
                       str_contains(strtolower($item['en'] ?? ''), $search) ||
                       str_contains(strtolower($item['ar'] ?? ''), $search);
            });
        }

        return collect($translations)->values();
    }

    protected function flattenTranslations($array, $group, $locale, &$translations, $prefix = '')
    {
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            $translationKey = "{$group}.{$fullKey}";

            if (is_array($value)) {
                $this->flattenTranslations($value, $group, $locale, $translations, $fullKey);
            } else {
                if (! isset($translations[$translationKey])) {
                    $translations[$translationKey] = [
                        'key' => $translationKey,
                        'group' => $group,
                        'en' => '',
                        'ar' => '',
                    ];
                }
                $translations[$translationKey][$locale] = $value;
            }
        }
    }

    public function openAddModal()
    {
        $this->reset(['newKey', 'newGroup', 'newValueEn', 'newValueAr']);
        $this->newGroup = 'app';
        $this->showAddModal = true;
    }

    public function closeAddModal()
    {
        $this->showAddModal = false;
    }

    public function addTranslation()
    {
        $this->validate([
            'newKey' => 'required|string|max:255',
            'newGroup' => 'required|string|max:50|alpha_dash',
            'newValueEn' => 'required|string|max:2000',
            'newValueAr' => 'required|string|max:2000',
        ]);

        // Sanitize the key to prevent code injection
        $sanitizedKey = preg_replace('/[^a-zA-Z0-9_.]/', '', $this->newKey);

        // Save English translation
        $this->saveToFile('en', $this->newGroup, $sanitizedKey, $this->newValueEn);

        // Save Arabic translation
        $this->saveToFile('ar', $this->newGroup, $sanitizedKey, $this->newValueAr);

        $this->showAddModal = false;
        $this->reset(['newKey', 'newGroup', 'newValueEn', 'newValueAr']);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Translation added successfully!'),
        ]);
    }

    public function openEditModal($key, $group, $en, $ar)
    {
        $this->editKey = $key;
        $this->editGroup = $group;
        $this->editValueEn = $en;
        $this->editValueAr = $ar;
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
    }

    public function updateTranslation()
    {
        $this->validate([
            'editValueEn' => 'required|string|max:2000',
            'editValueAr' => 'required|string|max:2000',
        ]);

        // Extract just the key without group prefix
        $keyParts = explode('.', $this->editKey);
        array_shift($keyParts); // Remove group prefix
        $keyWithoutGroup = implode('.', $keyParts);

        // Save English translation
        $this->saveToFile('en', $this->editGroup, $keyWithoutGroup, $this->editValueEn);

        // Save Arabic translation
        $this->saveToFile('ar', $this->editGroup, $keyWithoutGroup, $this->editValueAr);

        $this->showEditModal = false;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Translation updated successfully!'),
        ]);
    }

    protected function saveToFile($locale, $group, $key, $value)
    {
        $filePath = lang_path("{$locale}/{$group}.php");

        // Load existing translations or create empty array
        $translations = [];
        if (File::exists($filePath)) {
            $translations = include $filePath;
        }

        // Handle nested keys
        $keys = explode('.', $key);
        $current = &$translations;

        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $current[$k] = $value;
            } else {
                if (! isset($current[$k]) || ! is_array($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
        }

        // Save to file using secure JSON encoding then converting to PHP array syntax
        // This prevents code injection from translation values
        $jsonContent = json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $phpArray = $this->jsonToPhpArray($jsonContent);
        $content = "<?php\n\nreturn ".$phpArray.";\n";

        // Ensure directory exists
        $directory = dirname($filePath);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($filePath, $content);

        // Clear translation-specific cache
        Cache::forget("translations.{$locale}");
    }

    /**
     * Convert JSON string to PHP array syntax safely
     */
    protected function jsonToPhpArray($json)
    {
        if (! is_string($json)) {
            return var_export([], true);
        }

        $array = json_decode($json, true);

        return var_export($array ?? [], true);
    }

    public function clearCache()
    {
        // Only clear translation-related caches, not the entire application cache
        Cache::forget('translations.en');
        Cache::forget('translations.ar');

        // Clear config cache which affects translations
        Artisan::call('config:clear');

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Translation cache cleared!'),
        ]);
    }

    public function getMissingTranslations()
    {
        $missing = [];

        // Scan blade files for hardcoded strings
        $viewsPath = resource_path('views');
        $files = File::allFiles($viewsPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = File::get($file->getPathname());

            // Find strings that might need translation (simple heuristic)
            // Look for >'text'< or >"text"< patterns (text between tags)
            preg_match_all('/>\s*([A-Z][a-zA-Z\s]{3,30})\s*</', $content, $matches);

            foreach ($matches[1] ?? [] as $text) {
                $text = trim($text);
                if ($text && ! preg_match('/^[A-Z_]+$/', $text)) {
                    $missing[] = $text;
                }
            }
        }

        return array_unique($missing);
    }

    public function render()
    {
        return view('livewire.admin.translation-manager', [
            'translations' => $this->getTranslations(),
            'groups' => $this->getTranslationGroups(),
            'missingCount' => count($this->getMissingTranslations()),
        ])->layout('layouts.app');
    }
}
