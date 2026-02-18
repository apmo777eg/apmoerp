<?php

declare(strict_types=1);

namespace App\Services\UX;

/**
 * KeyboardShortcutsService - Global keyboard shortcuts for power users
 *
 * Provides keyboard shortcut definitions and handlers for common actions
 * across the ERP system. Shortcuts are locale-aware and customizable.
 */
class KeyboardShortcutsService
{
    /**
     * Get all available keyboard shortcuts grouped by category
     */
    public function getAllShortcuts(): array
    {
        return [
            'navigation' => [
                [
                    'key' => 'g h',
                    'description' => __('Go to Dashboard'),
                    'action' => 'navigate',
                    'target' => 'dashboard',
                ],
                [
                    'key' => 'g s',
                    'description' => __('Go to Sales'),
                    'action' => 'navigate',
                    'target' => 'sales.index',
                ],
                [
                    'key' => 'g p',
                    'description' => __('Go to Products'),
                    'action' => 'navigate',
                    'target' => 'products.index',
                ],
                [
                    'key' => 'g c',
                    'description' => __('Go to Customers'),
                    'action' => 'navigate',
                    'target' => 'customers.index',
                ],
                [
                    'key' => 'g i',
                    'description' => __('Go to Inventory'),
                    'action' => 'navigate',
                    'target' => 'inventory.index',
                ],
                [
                    'key' => 'g r',
                    'description' => __('Go to Reports'),
                    'action' => 'navigate',
                    'target' => 'admin.reports.index',
                ],
                [
                    'key' => 'g t',
                    'description' => __('Go to Settings'),
                    'action' => 'navigate',
                    'target' => 'admin.settings.index',
                ],
            ],
            'actions' => [
                [
                    'key' => 'ctrl+s',
                    'description' => __('Save current form'),
                    'action' => 'save',
                    'target' => null,
                ],
                [
                    'key' => 'ctrl+n',
                    'description' => __('Create new record'),
                    'action' => 'create',
                    'target' => null,
                ],
                [
                    'key' => 'ctrl+e',
                    'description' => __('Edit current record'),
                    'action' => 'edit',
                    'target' => null,
                ],
                [
                    'key' => 'ctrl+d',
                    'description' => __('Delete current record'),
                    'action' => 'delete',
                    'target' => null,
                ],
                [
                    'key' => 'ctrl+p',
                    'description' => __('Print current page'),
                    'action' => 'print',
                    'target' => null,
                ],
                [
                    'key' => 'ctrl+shift+e',
                    'description' => __('Export data'),
                    'action' => 'export',
                    'target' => null,
                ],
            ],
            'search' => [
                [
                    'key' => '/',
                    'description' => __('Focus search box'),
                    'action' => 'focus',
                    'target' => '#global-search',
                ],
                [
                    'key' => 'ctrl+k',
                    'description' => __('Open command palette'),
                    'action' => 'command-palette',
                    'target' => null,
                ],
                [
                    'key' => 'escape',
                    'description' => __('Close modal/dropdown'),
                    'action' => 'close',
                    'target' => null,
                ],
            ],
            'view' => [
                [
                    'key' => 'ctrl+/',
                    'description' => __('Toggle sidebar'),
                    'action' => 'toggle',
                    'target' => '#sidebar',
                ],
                [
                    'key' => '?',
                    'description' => __('Show keyboard shortcuts'),
                    'action' => 'show-shortcuts',
                    'target' => null,
                ],
                [
                    'key' => 'r',
                    'description' => __('Refresh current page'),
                    'action' => 'refresh',
                    'target' => null,
                ],
            ],
            'pos' => [
                [
                    'key' => 'F2',
                    'description' => __('Focus quantity field'),
                    'action' => 'focus',
                    'target' => '#pos-quantity',
                ],
                [
                    'key' => 'F4',
                    'description' => __('Apply discount'),
                    'action' => 'discount',
                    'target' => null,
                ],
                [
                    'key' => 'F8',
                    'description' => __('Clear cart'),
                    'action' => 'clear-cart',
                    'target' => null,
                ],
                [
                    'key' => 'F12',
                    'description' => __('Complete sale'),
                    'action' => 'complete-sale',
                    'target' => null,
                ],
            ],
        ];
    }

    /**
     * Get shortcuts as flat array for JavaScript
     */
    public function getShortcutsForJs(): array
    {
        $shortcuts = [];
        foreach ($this->getAllShortcuts() as $category => $items) {
            foreach ($items as $item) {
                $shortcuts[] = [
                    'key' => $item['key'],
                    'category' => $category,
                    'description' => $item['description'],
                    'action' => $item['action'],
                    'target' => $item['target'],
                ];
            }
        }

        return $shortcuts;
    }

    /**
     * Get user's custom shortcuts
     */
    public function getUserShortcuts(int $userId): array
    {
        // DB-first: user_preferences is one row per user and stores shortcuts in `pos_shortcuts` JSON.
        $prefs = \App\Models\UserPreference::getForUser($userId);

        return $prefs->pos_shortcuts ?? [];
    }

    /**
     * Save user's custom shortcuts
     */
    public function saveUserShortcuts(int $userId, array $shortcuts): void
    {
        // DB-first: store shortcuts in `pos_shortcuts` JSON column.
        $prefs = \App\Models\UserPreference::getForUser($userId);
        $prefs->update(['pos_shortcuts' => $shortcuts]);
    }
}
