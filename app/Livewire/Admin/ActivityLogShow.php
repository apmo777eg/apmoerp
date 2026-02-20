<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use App\Livewire\BaseComponent as Component;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts.app')]
class ActivityLogShow extends Component
{
    public Activity $activity;

    public function mount(int $id): void
    {
        $this->activity = Activity::with(['causer', 'subject'])->findOrFail($id);
    }

    /**
     * Format properties for human-readable display
     */
    public function getFormattedPropertiesProperty(): array
    {
        $properties = $this->activity->properties?->toArray() ?? [];
        $formatted = [];

        // Handle 'old' and 'attributes' for update events
        if (isset($properties['old']) && isset($properties['attributes'])) {
            $formatted['changes'] = [];
            foreach ($properties['attributes'] as $key => $newValue) {
                $oldValue = $properties['old'][$key] ?? null;
                $formatted['changes'][] = [
                    'field' => $this->formatFieldName($key),
                    'old' => $this->formatValue($oldValue),
                    'new' => $this->formatValue($newValue),
                ];
            }
        } elseif (isset($properties['attributes'])) {
            // Created event - just show new values
            $formatted['attributes'] = [];
            foreach ($properties['attributes'] as $key => $value) {
                $formatted['attributes'][] = [
                    'field' => $this->formatFieldName($key),
                    'value' => $this->formatValue($value),
                ];
            }
        } else {
            // Generic properties
            foreach ($properties as $key => $value) {
                if (! in_array($key, ['old', 'attributes'])) {
                    $formatted['other'][] = [
                        'field' => $this->formatFieldName($key),
                        'value' => $this->formatValue($value),
                    ];
                }
            }
        }

        return $formatted;
    }

    /**
     * Convert field names to human-readable format
     */
    protected function formatFieldName(string $field): string
    {
        // Replace underscores and hyphens with spaces
        $field = str_replace(['_', '-'], ' ', $field);

        // Handle common abbreviations
        $replacements = [
            'id' => 'ID',
            'sku' => 'SKU',
            'url' => 'URL',
            'ip' => 'IP',
            'api' => 'API',
        ];

        foreach ($replacements as $search => $replace) {
            $field = preg_replace('/\b'.$search.'\b/i', $replace, $field);
        }

        return ucwords($field);
    }

    /**
     * Format values for display
     *
     * @param  int  $depth  Current recursion depth to prevent stack overflow
     */
    protected function formatValue($value, int $depth = 0): string
    {
        // Prevent infinite recursion
        if ($depth > 5) {
            return is_scalar($value) ? (string) $value : '[nested data]';
        }

        if (is_null($value)) {
            return __('(empty)');
        }

        if (is_bool($value)) {
            return $value ? __('Yes') : __('No');
        }

        if (is_array($value)) {
            // Limit array processing to prevent performance issues
            $items = array_slice($value, 0, 10);
            $formatted = array_map(fn ($v) => $this->formatValue($v, $depth + 1), $items);
            $result = implode(', ', $formatted);
            if (count($value) > 10) {
                $result .= ' ... '.__('and :count more', ['count' => count($value) - 10]);
            }

            return $result;
        }

        if (is_object($value)) {
            return json_encode($value);
        }

        // Check if it looks like a date/datetime
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $value)) {
            try {
                $date = new \DateTime($value);

                return $date->format('M d, Y H:i:s');
            } catch (\Exception $e) {
                // Not a valid date, return as is
            }
        }

        return (string) $value;
    }

    public function render(): View
    {
        return view('livewire.admin.activity-log-show', [
            'formattedProperties' => $this->formattedProperties,
        ]);
    }
}
