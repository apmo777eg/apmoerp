<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ModuleRegistrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RegisterModule extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'module:register
                            {key? : Module key (e.g., inventory)}
                            {--name= : Module name}
                            {--name-ar= : Module name in Arabic}
                            {--description= : Module description}
                            {--icon= : Module icon emoji}
                            {--category= : Module category}
                            {--interactive : Interactive mode}
                            {--template : Show registration template}';

    /**
     * The console command description.
     */
    protected $description = 'Register a new module with automatic navigation setup';

    protected ModuleRegistrationService $service;

    /**
     * Execute the console command.
     */
    public function handle(ModuleRegistrationService $service): int
    {
        $this->service = $service;

        // Show template
        if ($this->option('template')) {
            $this->showTemplate();

            return self::SUCCESS;
        }

        // Interactive mode
        if ($this->option('interactive') || ! $this->argument('key')) {
            return $this->interactiveRegistration();
        }

        // Quick registration with options
        return $this->quickRegistration();
    }

    /**
     * Show registration template
     */
    protected function showTemplate(): void
    {
        $template = $this->service->getRegistrationTemplate();

        $this->info('Module Registration Template:');
        $this->line('');
        $this->line(json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->line('');
        $this->info('You can use this template to register a module programmatically or via JSON file.');
    }

    /**
     * Interactive registration
     */
    protected function interactiveRegistration(): int
    {
        $this->info('═══════════════════════════════════════');
        $this->info('   Module Registration Wizard');
        $this->info('═══════════════════════════════════════');
        $this->line('');

        // Basic Information
        $key = $this->ask('Module key (lowercase, underscores only)', 'new_module');
        $key = Str::slug($key, '_');

        $name = $this->ask('Module name (English)', Str::title(str_replace('_', ' ', $key)));
        $nameAr = $this->ask('Module name (Arabic)', $name);
        $description = $this->ask('Module description (optional)');
        $descriptionAr = $this->ask('Module description in Arabic (optional)', $description);

        // Icon and Color
        $icon = $this->ask('Module icon (emoji)', '📦');
        $color = $this->ask('Module color (hex)', '#3b82f6');

        // Category
        $categories = ['general', 'sales', 'inventory', 'financial', 'hr', 'operations', 'admin'];
        $category = $this->choice('Module category', $categories, 0);

        // Type
        $moduleType = $this->choice('Module type', ['data', 'functional'], 0);

        // Features
        $supportsReporting = $this->confirm('Supports reporting?', true);
        $supportsCustomFields = $this->confirm('Supports custom fields?', true);
        $supportsItems = $this->confirm('Supports items/products?', false);

        // Navigation
        $addNavigation = $this->confirm('Add navigation items?', true);
        $navigation = [];

        if ($addNavigation) {
            $this->info('Setting up navigation...');
            $navigation = $this->setupNavigation($key, $name, $nameAr);
        }

        // Prepare module data
        $moduleData = [
            'module_key' => $key,
            'name' => $name,
            'name_ar' => $nameAr,
            'description' => $description ?: null,
            'description_ar' => $descriptionAr ?: null,
            'icon' => $icon,
            'color' => $color,
            'is_core' => false,
            'is_active' => true,
            'category' => $category,
            'module_type' => $moduleType,
            'sort_order' => 999,
            'supports_reporting' => $supportsReporting,
            'supports_custom_fields' => $supportsCustomFields,
            'supports_items' => $supportsItems,
            'navigation' => $navigation,
        ];

        // Validate
        $errors = $this->service->validateModuleData($moduleData);
        if (! empty($errors)) {
            $this->error('Validation failed:');
            foreach ($errors as $error) {
                $this->error('  • '.$error);
            }

            return self::FAILURE;
        }

        // Confirm
        $this->line('');
        $this->info('Module Data:');
        $this->table(
            ['Field', 'Value'],
            collect($moduleData)->except('navigation')->map(fn ($value, $key) => [$key, is_bool($value) ? ($value ? 'Yes' : 'No') : $value])
        );

        if (! $this->confirm('Register this module?', true)) {
            $this->warn('Registration cancelled.');

            return self::SUCCESS;
        }

        // Register
        try {
            $module = $this->service->registerModule($moduleData);
            $this->info('✓ Module registered successfully!');
            $this->line('  ID: '.$module->id);
            $this->line('  Key: '.$module->module_key);
            $this->line('  Name: '.$module->name);

            if (! empty($navigation)) {
                $this->info('✓ Navigation items created: '.count($navigation));
            }

            $this->line('');
            $this->info('Next steps:');
            $this->line('  1. Create routes for the module');
            $this->line('  2. Create Livewire components');
            $this->line('  3. Add permissions to database');
            $this->line('  4. Enable module for branches');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Registration failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Quick registration with command options
     */
    protected function quickRegistration(): int
    {
        $key = $this->argument('key');
        $name = $this->option('name') ?: Str::title(str_replace('_', ' ', $key));
        $nameAr = $this->option('name-ar') ?: $name;

        $moduleData = [
            'module_key' => $key,
            'name' => $name,
            'name_ar' => $nameAr,
            'description' => $this->option('description'),
            'icon' => $this->option('icon') ?: '📦',
            'category' => $this->option('category') ?: 'general',
            'is_core' => false,
            'is_active' => true,
            'module_type' => 'data',
            'sort_order' => 999,
        ];

        try {
            $module = $this->service->registerModule($moduleData);
            $this->info('✓ Module "'.$module->name.'" registered successfully!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Registration failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Setup navigation items
     */
    protected function setupNavigation(string $moduleKey, string $name, string $nameAr): array
    {
        $navigation = [];
        $addMore = true;

        while ($addMore) {
            $navKey = $this->ask('Navigation key', $moduleKey.'_'.count($navigation));
            $navLabel = $this->ask('Navigation label', $name);
            $navLabelAr = $this->ask('Navigation label (Arabic)', $nameAr);
            $routeName = $this->ask('Route name', 'app.'.$moduleKey.'.index');
            $navIcon = $this->ask('Icon (emoji)', '📄');
            $permissions = $this->ask('Required permissions (comma-separated)', $moduleKey.'.view');

            $navigation[] = [
                'nav_key' => $navKey,
                'nav_label' => $navLabel,
                'nav_label_ar' => $navLabelAr,
                'route_name' => $routeName,
                'icon' => $navIcon,
                'required_permissions' => array_map('trim', explode(',', $permissions)),
                'is_active' => true,
                'sort_order' => (count($navigation) + 1) * 10,
            ];

            $addMore = $this->confirm('Add another navigation item?', false);
        }

        return $navigation;
    }
}
