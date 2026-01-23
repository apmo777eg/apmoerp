<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class DynamicForm extends Component
{
    use WithFileUploads;

    /**
     * Buffer size for security scanning of HTML/SVG files (in bytes).
     * 8KB is sufficient to detect malicious content in file headers.
     */
    private const SECURITY_SCAN_BUFFER_SIZE = 8192;

    /**
     * Comprehensive XSS/malicious content detection pattern.
     * Includes: scripts, iframes, JS URIs, event handlers, data URIs, vbscript, object/embed tags
     */
    private const MALICIOUS_CONTENT_PATTERN = '/<script|<iframe|<object|<embed|javascript:|vbscript:|data:\s*[^,]*;base64|on[a-z]+\s*=/i';

    #[Locked]
    public array $schema = [];

    public array $data = [];

    public string $submitLabel = '';

    public string $cancelLabel = '';

    public ?string $cancelRoute = null;

    public bool $showCancel = true;

    public string $layout = 'vertical';

    public int $columns = 1;

    public bool $loading = false;

    private array $defaultFileMimes = [
        'pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt',
    ];

    // HIGH-004 FIX: Whitelist of allowed storage disks for security
    // Removed 'private' as it's not defined in config/filesystems.php
    // Available disks: 'local' (private storage), 'public', 's3'
    private array $allowedDisks = ['local'];

    public function mount(
        array $schema = [],
        array $data = [],
        string $submitLabel = '',
        string $cancelLabel = '',
        ?string $cancelRoute = null,
        bool $showCancel = true,
        string $layout = 'vertical',
        int $columns = 1
    ): void {
        $this->schema = $schema;
        $this->submitLabel = $submitLabel ?: __('Save');
        $this->cancelLabel = $cancelLabel ?: __('Cancel');
        $this->cancelRoute = $cancelRoute;
        $this->showCancel = $showCancel;
        $this->layout = $layout;
        $this->columns = $columns;

        foreach ($this->schema as $field) {
            $name = $field['name'] ?? '';
            if ($name && ! isset($data[$name])) {
                $data[$name] = $field['default'] ?? '';
            }
        }
        $this->data = $data;
    }

    public function updated($propertyName): void
    {
        $this->dispatch('dynamic-form-updated', data: $this->data);
    }

    public function submit(): void
    {
        $this->loading = true;

        try {
            $rules = $this->buildValidationRules();
            if (! empty($rules)) {
                $this->validate($rules);
            }

            $this->processFileUploads();

            $this->dispatch('formSubmitted', data: $this->data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('formError', errors: $e->errors());
            throw $e;
        } finally {
            $this->loading = false;
        }
    }

    protected function processFileUploads(): void
    {
        foreach ($this->schema as $field) {
            $name = $field['name'] ?? '';
            $type = $field['type'] ?? 'text';

            if ($type === 'file' && $name && isset($this->data[$name])) {
                $file = $this->data[$name];
                if ($file && method_exists($file, 'store')) {
                    // Security: Only allow whitelisted disks, default to 'local' (private)
                    $requestedDisk = $field['disk'] ?? 'local';
                    $disk = in_array($requestedDisk, $this->allowedDisks, true) ? $requestedDisk : 'local';

                    // Security: Validate file against server-side rules
                    $this->validateFileUpload($file, $field);

                    $path = $file->store('dynamic-uploads', $disk);
                    $this->data[$name] = $path;
                }
            }
        }
    }

    #[On('resetForm')]
    public function resetFormData(): void
    {
        foreach ($this->schema as $field) {
            $name = $field['name'] ?? '';
            if ($name) {
                $this->data[$name] = $field['default'] ?? '';
            }
        }
    }

    protected function buildValidationRules(): array
    {
        $rules = [];
        foreach ($this->schema as $field) {
            $name = $field['name'] ?? '';
            if (! $name) {
                continue;
            }

            $fieldRules = $this->normalizeRules($field['rules'] ?? []);

            if (($field['type'] ?? 'text') === 'file') {
                $fieldRules = $this->augmentFileRules($fieldRules, $field);
            }

            if (! empty($fieldRules)) {
                $rules["data.{$name}"] = $fieldRules;
            }
        }

        return $rules;
    }

    private function normalizeRules(string|array $rules): array
    {
        if (is_string($rules)) {
            return array_filter(explode('|', $rules));
        }

        return $rules;
    }

    private function augmentFileRules(array $rules, array $field): array
    {
        $rules[] = 'file';
        $maxSize = $field['max'] ?? 10240;
        $rules[] = "max:{$maxSize}";

        $mimes = $field['mimes'] ?? $this->defaultFileMimes;
        if (! empty($mimes)) {
            $rules[] = 'mimes:'.implode(',', $mimes);
        }

        return array_values(array_unique($rules));
    }

    /**
     * Validate file upload against server-side security rules (BUG-003 fix)
     * MED-001 FIX: Use server-detected extension/MIME instead of client-provided values
     * MED-002 FIX: Check file size before reading content to prevent DoS
     */
    private function validateFileUpload($file, array $field): void
    {
        // MED-002 FIX: Check file size FIRST to prevent memory exhaustion on large files
        $maxSizeKB = $field['max'] ?? 10240;
        $maxSize = $maxSizeKB * 1024; // Convert KB to bytes
        if ($file->getSize() > $maxSize) {
            $validator = validator([], []);
            $validator->errors()->add('file', __('File size exceeds maximum allowed size of :max KB.', ['max' => $maxSizeKB]));
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // Get allowed extensions from field or use default
        $allowedExtensions = $field['mimes'] ?? $this->defaultFileMimes;

        // MED-001 FIX: Use server-detected extension instead of client-provided
        // $file->extension() uses the Symfony guesser based on file content
        $serverExtension = strtolower($file->extension() ?: '');
        $clientExtension = strtolower($file->getClientOriginalExtension());

        // Security: Block potentially dangerous file types (check both server and client extensions)
        $blockedExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'exe', 'sh', 'bat', 'cmd', 'com'];
        if (in_array($serverExtension, $blockedExtensions, true) || in_array($clientExtension, $blockedExtensions, true)) {
            $validator = validator([], []);
            $validator->errors()->add('file', __('This file type is not allowed for security reasons.'));
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // MED-001 FIX: Validate using server-detected MIME type as primary check
        $serverMimeType = $file->getMimeType();

        // Build a map of allowed MIME types from extensions for validation
        $extensionToMimeMap = [
            'pdf' => ['application/pdf'],
            'png' => ['image/png'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'csv' => ['text/csv', 'text/plain', 'application/csv'],
            'txt' => ['text/plain'],
            'html' => ['text/html'],
            'htm' => ['text/html'],
            'svg' => ['image/svg+xml'],
        ];

        // Collect all allowed MIME types based on allowed extensions
        $allowedMimeTypes = [];
        foreach ($allowedExtensions as $ext) {
            if (isset($extensionToMimeMap[$ext])) {
                $allowedMimeTypes = array_merge($allowedMimeTypes, $extensionToMimeMap[$ext]);
            }
        }
        $allowedMimeTypes = array_unique($allowedMimeTypes);

        // Validate server-detected MIME type if we have a mapping
        if (! empty($allowedMimeTypes) && $serverMimeType && ! in_array($serverMimeType, $allowedMimeTypes, true)) {
            $validator = validator([], []);
            $validator->errors()->add('file', __('Only the following file types are allowed: :types', ['types' => implode(', ', $allowedExtensions)]));
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // Fallback: also validate extension is in allowed list
        if (! in_array($serverExtension, $allowedExtensions, true) && ! in_array($clientExtension, $allowedExtensions, true)) {
            $validator = validator([], []);
            $validator->errors()->add('file', __('Only the following file types are allowed: :types', ['types' => implode(', ', $allowedExtensions)]));
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // MED-003 FIX: Only scan HTML/SVG content if these types are actually allowed
        // Check if HTML/SVG types are in allowed extensions before scanning
        $htmlSvgExtensions = ['html', 'htm', 'svg'];
        $htmlSvgAllowed = ! empty(array_intersect($htmlSvgExtensions, $allowedExtensions));

        if ($htmlSvgAllowed && (in_array($serverExtension, $htmlSvgExtensions, true) || in_array($clientExtension, $htmlSvgExtensions, true))) {
            // Read only first 8KB to check for malicious content (sufficient for header scanning)
            $filePath = $file->getRealPath();
            if (! $filePath || ! is_readable($filePath)) {
                // If we can't read the file, reject it for security
                $validator = validator([], []);
                $validator->errors()->add('file', __('Unable to verify file content for security scanning.'));
                throw new \Illuminate\Validation\ValidationException($validator);
            }

            $handle = @fopen($filePath, 'rb');
            if ($handle === false) {
                // If we can't open the file, reject it for security
                $validator = validator([], []);
                $validator->errors()->add('file', __('Unable to verify file content for security scanning.'));
                throw new \Illuminate\Validation\ValidationException($validator);
            }

            $content = @fread($handle, self::SECURITY_SCAN_BUFFER_SIZE);
            fclose($handle);

            if ($content === false) {
                // If we can't read the content, reject it for security
                $validator = validator([], []);
                $validator->errors()->add('file', __('Unable to verify file content for security scanning.'));
                throw new \Illuminate\Validation\ValidationException($validator);
            }

            // Use class constant for malicious content pattern
            if ($content && preg_match(self::MALICIOUS_CONTENT_PATTERN, $content)) {
                $validator = validator([], []);
                $validator->errors()->add('file', __('File contains potentially malicious content.'));
                throw new \Illuminate\Validation\ValidationException($validator);
            }
        }
    }

    protected function validationAttributes(): array
    {
        $attributes = [];
        foreach ($this->schema as $field) {
            $name = $field['name'] ?? '';
            if ($name) {
                $attributes["data.{$name}"] = $field['label'] ?? $name;
            }
        }

        return $attributes;
    }

    public function render()
    {
        return view('livewire.shared.dynamic-form');
    }
}
