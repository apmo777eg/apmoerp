<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Module;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckModuleCompleteness extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'module:check-completeness
                            {module? : Specific module key to check}
                            {--all : Check all modules}
                            {--detailed : Show detailed analysis}';

    /**
     * The console command description.
     */
    protected $description = 'Check completeness of modules (CRUD, navigation, permissions, etc.)';

    protected array $completenessScores = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('═══════════════════════════════════════');
        $this->info('   Module Completeness Checker');
        $this->info('═══════════════════════════════════════');
        $this->line('');

        if ($this->option('all')) {
            $modules = Module::active()->get();
        } elseif ($moduleKey = $this->argument('module')) {
            $modules = Module::where('module_key', $moduleKey)->get();
            if ($modules->isEmpty()) {
                $this->error("Module '{$moduleKey}' not found");

                return self::FAILURE;
            }
        } else {
            $this->error('Please specify a module or use --all flag');

            return self::FAILURE;
        }

        foreach ($modules as $module) {
            $this->checkModule($module);
        }

        $this->displaySummary();

        return self::SUCCESS;
    }

    /**
     * Check individual module
     */
    protected function checkModule(Module $module): void
    {
        $this->info("Checking module: {$module->name} ({$module->module_key})");

        $scores = [
            'navigation' => $this->checkNavigation($module),
            'livewire' => $this->checkLivewireComponents($module),
            'routes' => $this->checkRoutes($module),
            'permissions' => $this->checkPermissions($module),
            'models' => $this->checkModels($module),
            'views' => $this->checkViews($module),
            'documentation' => $this->checkDocumentation($module),
        ];

        $totalScore = array_sum($scores) / count($scores);
        $this->completenessScores[$module->module_key] = [
            'module' => $module,
            'scores' => $scores,
            'total' => $totalScore,
        ];

        // Display scores
        $this->line('');
        $this->displayModuleScores($module, $scores, $totalScore);
        $this->line('');
    }

    /**
     * Check navigation completeness
     */
    protected function checkNavigation(Module $module): float
    {
        $score = 0;
        $maxScore = 100;

        // Has navigation items?
        $navItems = $module->navigation()->count();
        if ($navItems > 0) {
            $score += 40;
        }

        // Navigation has proper labels?
        $navWithLabels = $module->navigation()
            ->whereNotNull('nav_label')
            ->whereNotNull('nav_label_ar')
            ->count();

        if ($navItems > 0) {
            $score += ($navWithLabels / $navItems) * 30;
        }

        // Navigation has icons?
        $navWithIcons = $module->navigation()
            ->whereNotNull('icon')
            ->count();

        if ($navItems > 0) {
            $score += ($navWithIcons / $navItems) * 30;
        }

        return $score;
    }

    /**
     * Check Livewire components
     */
    protected function checkLivewireComponents(Module $module): float
    {
        $score = 0;
        $requiredComponents = ['Index', 'Form'];
        $foundComponents = 0;

        $moduleName = str($module->module_key)->studly();
        $componentPath = app_path("Livewire/{$moduleName}");

        if (File::isDirectory($componentPath)) {
            $score += 20; // Module directory exists

            foreach ($requiredComponents as $component) {
                if (File::exists("{$componentPath}/{$component}.php")) {
                    $foundComponents++;
                }
            }

            $score += ($foundComponents / count($requiredComponents)) * 60;

            // Bonus for additional features
            $files = File::files($componentPath);
            if (count($files) > count($requiredComponents)) {
                $score += 20; // Has additional components
            }
        }

        return min($score, 100);
    }

    /**
     * Check routes
     */
    protected function checkRoutes(Module $module): float
    {
        $routeFile = base_path("routes/web.php");
        $content = File::get($routeFile);

        $routePatterns = [
            "app.{$module->module_key}.index",
            "app.{$module->module_key}.create",
            "app.{$module->module_key}.edit",
        ];

        $foundRoutes = 0;
        foreach ($routePatterns as $pattern) {
            if (str_contains($content, $pattern)) {
                $foundRoutes++;
            }
        }

        return ($foundRoutes / count($routePatterns)) * 100;
    }

    /**
     * Check permissions
     */
    protected function checkPermissions(Module $module): float
    {
        $permissionPatterns = [
            "{$module->module_key}.view",
            "{$module->module_key}.create",
            "{$module->module_key}.update",
            "{$module->module_key}.delete",
        ];

        $foundPermissions = \Spatie\Permission\Models\Permission::whereIn('name', $permissionPatterns)->count();

        return ($foundPermissions / count($permissionPatterns)) * 100;
    }

    /**
     * Check models
     */
    protected function checkModels(Module $module): float
    {
        $moduleName = str($module->module_key)->studly()->singular();
        $modelPath = app_path("Models/{$moduleName}.php");

        if (! File::exists($modelPath)) {
            return 0;
        }

        $score = 50; // Model exists

        $content = File::get($modelPath);

        // Check for traits
        $traits = [
            'ValidatesAndSanitizes' => 10,
            'EnhancedAuditLogging' => 10,
            'HasFactory' => 5,
            'SoftDeletes' => 5,
        ];

        foreach ($traits as $trait => $points) {
            if (str_contains($content, $trait)) {
                $score += $points;
            }
        }

        // Check for fillable
        if (str_contains($content, '$fillable')) {
            $score += 10;
        }

        // Check for relationships
        if (preg_match('/public function \w+\(\).*(belongsTo|hasMany|hasOne|belongsToMany)/', $content)) {
            $score += 10;
        }

        return min($score, 100);
    }

    /**
     * Check views
     */
    protected function checkViews(Module $module): float
    {
        $moduleName = str($module->module_key)->kebab();
        $viewPath = resource_path("views/livewire/{$moduleName}");

        if (! File::isDirectory($viewPath)) {
            return 0;
        }

        $score = 30; // Directory exists

        $requiredViews = ['index.blade.php', 'form.blade.php'];
        $foundViews = 0;

        foreach ($requiredViews as $view) {
            if (File::exists("{$viewPath}/{$view}")) {
                $foundViews++;
            }
        }

        $score += ($foundViews / count($requiredViews)) * 70;

        return $score;
    }

    /**
     * Check documentation
     */
    protected function checkDocumentation(Module $module): float
    {
        $score = 0;

        // Check if module has description
        if ($module->description) {
            $score += 30;
        }

        if ($module->description_ar) {
            $score += 20;
        }

        // Check for README
        $readmePath = base_path("docs/modules/{$module->module_key}.md");
        if (File::exists($readmePath)) {
            $score += 50;
        }

        return $score;
    }

    /**
     * Display module scores
     */
    protected function displayModuleScores(Module $module, array $scores, float $total): void
    {
        $detailed = $this->option('detailed');

        // Overall score with color
        $color = match (true) {
            $total >= 80 => 'green',
            $total >= 60 => 'yellow',
            default => 'red',
        };

        $this->line("  Overall Completeness: <fg={$color}>".round($total, 1).'%</>');

        if ($detailed) {
            $this->line('  ─────────────────────────────────');
            foreach ($scores as $aspect => $score) {
                $aspectColor = $score >= 70 ? 'green' : ($score >= 40 ? 'yellow' : 'red');
                $bar = $this->getProgressBar($score);
                $this->line(sprintf(
                    '  %-15s <fg=%s>%s</> %5.1f%%',
                    ucfirst($aspect),
                    $aspectColor,
                    $bar,
                    $score
                ));
            }
        }
    }

    /**
     * Get progress bar
     */
    protected function getProgressBar(float $score): string
    {
        $filled = (int) ($score / 10);
        $empty = 10 - $filled;

        return str_repeat('█', $filled).str_repeat('░', $empty);
    }

    /**
     * Display summary
     */
    protected function displaySummary(): void
    {
        $this->line('');
        $this->info('═══════════════════════════════════════');
        $this->info('   Summary');
        $this->info('═══════════════════════════════════════');
        $this->line('');

        // Sort by completeness
        uasort($this->completenessScores, fn ($a, $b) => $b['total'] <=> $a['total']);

        $rows = [];
        foreach ($this->completenessScores as $key => $data) {
            $module = $data['module'];
            $total = $data['total'];

            $status = match (true) {
                $total >= 80 => '✅ Complete',
                $total >= 60 => '⚠️  Needs Work',
                default => '❌ Incomplete',
            };

            $rows[] = [
                $module->name,
                $key,
                round($total, 1).'%',
                $status,
            ];
        }

        $this->table(
            ['Module', 'Key', 'Completeness', 'Status'],
            $rows
        );

        // Recommendations
        $this->line('');
        $this->info('Recommendations:');
        foreach ($this->completenessScores as $key => $data) {
            if ($data['total'] < 80) {
                $lowestAspect = array_keys($data['scores'], min($data['scores']))[0];
                $this->warn("  • {$data['module']->name}: Focus on {$lowestAspect}");
            }
        }
    }
}
