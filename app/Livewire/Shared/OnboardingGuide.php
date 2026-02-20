<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use App\Livewire\BaseComponent as Component;
/**
 * Onboarding Guide Component
 *
 * Provides an interactive guide for new users to learn the ERP system.
 * Shows contextual help based on which page the user is on.
 * Tracks completed onboarding steps in user preferences.
 */
class OnboardingGuide extends Component
{
    public bool $showGuide = false;

    public int $currentStep = 0;

    public array $completedSteps = [];

    public string $context = 'dashboard';

    public function mount(string $context = 'dashboard'): void
    {
        $this->context = $context;

        $user = Auth::user();
        if (! $user) {
            return;
        }

        // Get completed steps from user preferences
        $preferences = $user->preferences ?? [];
        $this->completedSteps = $preferences['onboarding_completed'] ?? [];

        // Check if we should show the guide automatically for new users
        $hasSeenOnboarding = in_array('welcome', $this->completedSteps);

        if (! $hasSeenOnboarding && $this->isNewUser()) {
            $this->showGuide = true;
        }
    }

    /**
     * Check if user is new (registered in last 7 days)
     */
    protected function isNewUser(): bool
    {
        $user = Auth::user();

        if (! $user || ! $user->created_at) {
            return false;
        }

        return $user->created_at->gt(now()->subDays(7));
    }

    /**
     * Get onboarding steps based on context
     */
    public function getStepsProperty(): array
    {
        $allSteps = [
            'dashboard' => [
                [
                    'id' => 'welcome',
                    'title' => __('Welcome to Your ERP System!'),
                    'description' => __('This system helps you manage your business. Let us show you around.'),
                    'icon' => '👋',
                    'target' => null,
                ],
                [
                    'id' => 'sidebar',
                    'title' => __('Navigation Menu'),
                    'description' => __('Use the sidebar to navigate between modules. Click on any menu item to explore.'),
                    'icon' => '📍',
                    'target' => '#sidebar',
                ],
                [
                    'id' => 'quick_actions',
                    'title' => __('Quick Actions'),
                    'description' => __('Use quick action buttons to perform common tasks like creating sales, adding products, etc.'),
                    'icon' => '⚡',
                    'target' => '[data-quick-actions]',
                ],
                [
                    'id' => 'dashboard_customize',
                    'title' => __('Customize Dashboard'),
                    'description' => __('Click "Customize" to show/hide widgets, drag to reorder, and choose your preferred layout.'),
                    'icon' => '🎨',
                    'target' => '[wire\\:click="toggleEditMode"]',
                ],
                [
                    'id' => 'notifications',
                    'title' => __('Notifications'),
                    'description' => __('Check the notification bell for important alerts about stock, invoices, and system updates.'),
                    'icon' => '🔔',
                    'target' => '[data-notifications]',
                ],
                [
                    'id' => 'user_menu',
                    'title' => __('Your Profile'),
                    'description' => __('Click your name to access profile settings, change password, or logout.'),
                    'icon' => '👤',
                    'target' => '[data-user-menu]',
                ],
                [
                    'id' => 'offline_mode',
                    'title' => __('Works Offline!'),
                    'description' => __('This app works offline. When connection is lost, you can still view cached data and create drafts.'),
                    'icon' => '📱',
                    'target' => null,
                ],
            ],
            'sales' => [
                [
                    'id' => 'sales_intro',
                    'title' => __('Sales Module'),
                    'description' => __('Create invoices, manage orders, and track customer payments here.'),
                    'icon' => '💰',
                    'target' => null,
                ],
                [
                    'id' => 'create_sale',
                    'title' => __('Create a Sale'),
                    'description' => __('Click "New Sale" to create a sales invoice. Select customer, add products, and save.'),
                    'icon' => '➕',
                    'target' => '[data-create-button]',
                ],
                [
                    'id' => 'sales_filters',
                    'title' => __('Filter & Search'),
                    'description' => __('Use filters to find sales by date, status, customer, or search by invoice number.'),
                    'icon' => '🔍',
                    'target' => '[data-filters]',
                ],
                [
                    'id' => 'sales_export',
                    'title' => __('Export Data'),
                    'description' => __('Export sales data to Excel or PDF using the export button.'),
                    'icon' => '📊',
                    'target' => '[data-export]',
                ],
            ],
            'inventory' => [
                [
                    'id' => 'inventory_intro',
                    'title' => __('Inventory Module'),
                    'description' => __('Manage your products, track stock levels, and receive alerts for low stock.'),
                    'icon' => '📦',
                    'target' => null,
                ],
                [
                    'id' => 'add_product',
                    'title' => __('Add Products'),
                    'description' => __('Add your products with prices, descriptions, and stock quantities.'),
                    'icon' => '➕',
                    'target' => '[data-create-button]',
                ],
                [
                    'id' => 'stock_alerts',
                    'title' => __('Stock Alerts'),
                    'description' => __('Set minimum stock levels to receive alerts when products run low.'),
                    'icon' => '⚠️',
                    'target' => null,
                ],
                [
                    'id' => 'barcode_scan',
                    'title' => __('Barcode Scanning'),
                    'description' => __('Use barcode scanner or camera to quickly find and add products.'),
                    'icon' => '📸',
                    'target' => '[data-barcode]',
                ],
            ],
            'pos' => [
                [
                    'id' => 'pos_intro',
                    'title' => __('Point of Sale'),
                    'description' => __('Fast checkout system for retail sales. Works great on touch screens!'),
                    'icon' => '🛒',
                    'target' => null,
                ],
                [
                    'id' => 'pos_products',
                    'title' => __('Add Products'),
                    'description' => __('Click products or scan barcodes to add them to the cart.'),
                    'icon' => '🛍️',
                    'target' => '[data-products-grid]',
                ],
                [
                    'id' => 'pos_cart',
                    'title' => __('Shopping Cart'),
                    'description' => __('View items, adjust quantities, and apply discounts in the cart.'),
                    'icon' => '🛒',
                    'target' => '[data-cart]',
                ],
                [
                    'id' => 'pos_payment',
                    'title' => __('Complete Payment'),
                    'description' => __('Choose payment method, enter amount, and complete the sale.'),
                    'icon' => '💳',
                    'target' => '[data-pay-button]',
                ],
                [
                    'id' => 'pos_offline',
                    'title' => __('Offline Mode'),
                    'description' => __('POS works offline! Sales are saved and synced when connection returns.'),
                    'icon' => '📱',
                    'target' => null,
                ],
            ],
            'settings' => [
                [
                    'id' => 'settings_intro',
                    'title' => __('System Settings'),
                    'description' => __('Configure your company information, preferences, and system behavior here.'),
                    'icon' => '⚙️',
                    'target' => null,
                ],
                [
                    'id' => 'settings_tabs',
                    'title' => __('Settings Categories'),
                    'description' => __('Use the tabs to navigate between different settings categories.'),
                    'icon' => '📑',
                    'target' => '[data-settings-tabs]',
                ],
                [
                    'id' => 'settings_company',
                    'title' => __('Company Info'),
                    'description' => __('Set your company name, logo, and contact details for invoices.'),
                    'icon' => '🏢',
                    'target' => null,
                ],
                [
                    'id' => 'settings_notifications',
                    'title' => __('Notification Preferences'),
                    'description' => __('Choose which alerts you want to receive and how.'),
                    'icon' => '🔔',
                    'target' => null,
                ],
            ],
            'reports' => [
                [
                    'id' => 'reports_intro',
                    'title' => __('Reports & Analytics'),
                    'description' => __('Generate detailed reports to understand your business performance.'),
                    'icon' => '📈',
                    'target' => null,
                ],
                [
                    'id' => 'reports_types',
                    'title' => __('Report Types'),
                    'description' => __('Choose from sales, inventory, financial, and custom reports.'),
                    'icon' => '📊',
                    'target' => '[data-report-types]',
                ],
                [
                    'id' => 'reports_filters',
                    'title' => __('Date & Filters'),
                    'description' => __('Filter reports by date range, branch, category, and more.'),
                    'icon' => '📅',
                    'target' => '[data-report-filters]',
                ],
                [
                    'id' => 'reports_export',
                    'title' => __('Export Reports'),
                    'description' => __('Download reports as PDF, Excel, or schedule automatic email delivery.'),
                    'icon' => '💾',
                    'target' => '[data-export]',
                ],
            ],
        ];

        return $allSteps[$this->context] ?? $allSteps['dashboard'];
    }

    /**
     * Show the onboarding guide
     */
    public function openGuide(): void
    {
        $this->showGuide = true;
        $this->currentStep = 0;
    }

    /**
     * Hide the onboarding guide
     */
    public function closeGuide(): void
    {
        $this->showGuide = false;
    }

    /**
     * Go to next step
     */
    public function nextStep(): void
    {
        $steps = $this->steps;

        if ($this->currentStep < count($steps) - 1) {
            $this->markStepComplete($steps[$this->currentStep]['id']);
            $this->currentStep++;
        } else {
            // Last step - mark as complete and close
            $this->markStepComplete($steps[$this->currentStep]['id']);
            $this->finishOnboarding();
        }
    }

    /**
     * Go to previous step
     */
    public function previousStep(): void
    {
        if ($this->currentStep > 0) {
            $this->currentStep--;
        }
    }

    /**
     * Skip to a specific step
     */
    public function goToStep(int $step): void
    {
        if ($step >= 0 && $step < count($this->steps)) {
            $this->currentStep = $step;
        }
    }

    /**
     * Mark a step as complete
     */
    protected function markStepComplete(string $stepId): void
    {
        if (! in_array($stepId, $this->completedSteps)) {
            $this->completedSteps[] = $stepId;
            $this->saveProgress();
        }
    }

    /**
     * Save onboarding progress to user preferences
     */
    protected function saveProgress(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $preferences = $user->preferences ?? [];
        $preferences['onboarding_completed'] = $this->completedSteps;
        $user->update(['preferences' => $preferences]);
    }

    /**
     * Finish onboarding
     */
    public function finishOnboarding(): void
    {
        $this->saveProgress();
        $this->showGuide = false;

        session()->flash('success', __('Onboarding complete! You can access help anytime from the menu.'));
    }

    /**
     * Skip all onboarding
     */
    public function skipOnboarding(): void
    {
        // Mark welcome as seen so it doesn't show again
        if (! in_array('welcome', $this->completedSteps)) {
            $this->completedSteps[] = 'welcome';
            $this->saveProgress();
        }

        $this->showGuide = false;
    }

    /**
     * Reset onboarding (for testing)
     */
    public function resetOnboarding(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $preferences = $user->preferences ?? [];
        $preferences['onboarding_completed'] = [];
        $user->update(['preferences' => $preferences]);

        $this->completedSteps = [];
        $this->currentStep = 0;
        $this->showGuide = true;
    }

    /**
     * Get progress percentage
     */
    public function getProgressProperty(): int
    {
        $steps = $this->steps;
        if (empty($steps)) {
            return 100;
        }

        $completed = count(array_filter($steps, fn ($step) => in_array($step['id'], $this->completedSteps)));

        return (int) round(($completed / count($steps)) * 100);
    }

    #[On('start-onboarding')]
    public function handleStartOnboarding(): void
    {
        $this->openGuide();
    }

    public function render(): View
    {
        return view('livewire.shared.onboarding-guide', [
            'steps' => $this->steps,
            'progress' => $this->progress,
        ]);
    }
}
