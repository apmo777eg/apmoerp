<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AuditViewButtons extends Command
{
    protected $signature = 'audit:buttons {--fix : Attempt to fix issues}';

    protected $description = 'Audit Blade views for buttons without actions or broken wire:click directives';

    protected array $issues = [];

    public function handle(): int
    {
        $this->info('Starting button audit...');

        $viewPath = resource_path('views');
        $this->auditDirectory($viewPath);

        if (empty($this->issues)) {
            $this->info('✓ No issues found!');

            return Command::SUCCESS;
        }

        $this->warn("\nFound ".count($this->issues).' potential issues:');

        foreach ($this->issues as $issue) {
            $this->line("  - {$issue['file']}:{$issue['line']}");
            $this->line("    Issue: {$issue['issue']}");
            $this->line('    Code: '.trim($issue['code']));
            $this->newLine();
        }

        if ($this->option('fix')) {
            $this->info('Fix option not yet implemented. Please fix manually.');
        }

        return Command::SUCCESS;
    }

    protected function auditDirectory(string $path): void
    {
        $files = File::allFiles($path);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $this->auditFile($file->getPathname());
            }
        }
    }

    protected function auditFile(string $filePath): void
    {
        if (! is_readable($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);

        // Track when we're inside a <form> to detect accidental submits.
        $isInForm = false;

        // Use an indexed loop so we can consume multi-line button tags.
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $lineNum = $i + 1;

            if (stripos($line, '<form') !== false) {
                $isInForm = true;
            }
            if (stripos($line, '</form') !== false) {
                $isInForm = false;
            }

            // Check for buttons (including multi-line attributes)
            if (stripos($line, '<button') !== false) {
                $buttonStartLine = $lineNum;
                $buttonHtml = $line;
                $safety = 0;

                // If the tag spans multiple lines, concatenate up to 10 lines.
                while (stripos($buttonHtml, '>') === false && ($i + 1) < count($lines) && $safety < 10) {
                    $i++;
                    $safety++;
                    $buttonHtml .= ' '.trim($lines[$i]);
                }

                $hasAnyTypeAttr = (bool) preg_match('/\btype\s*=\s*["\'][^"\']+["\']/i', $buttonHtml);
                $isExplicitSubmit = (bool) preg_match('/\btype\s*=\s*["\']submit["\']/i', $buttonHtml);
                $hasClickHandler = (bool) preg_match('/(wire:click|@click|x-on:click|onclick)/i', $buttonHtml);
                $hasAnyAction = (bool) preg_match('/(wire:click|@click|x-on:click|onclick|type\s*=\s*["\']submit["\']|form=)/i', $buttonHtml);
                $isDisabled = (bool) preg_match('/\bdisabled\b/i', $buttonHtml);

                // Forms bug detector: a <button> inside a <form> defaults to submit.
                // We only warn when the dev clearly intended a click handler, but forgot type="button".
                if ($isInForm && $hasClickHandler && ! $hasAnyTypeAttr) {
                    $this->issues[] = [
                        'file' => str_replace(resource_path().DIRECTORY_SEPARATOR, '', $filePath),
                        'line' => $buttonStartLine,
                        'issue' => 'Button inside <form> has a click handler but is missing type="button" (may submit the form)',
                        'code' => $buttonHtml,
                    ];
                }

                // Generic: a button that is not a submit button and has no action is suspicious.
                // Avoid flagging disabled buttons.
                if (! $hasAnyAction && ! $isDisabled && ! $isExplicitSubmit) {
                    $this->issues[] = [
                        'file' => str_replace(resource_path().DIRECTORY_SEPARATOR, '', $filePath),
                        'line' => $buttonStartLine,
                        'issue' => 'Button without action',
                        'code' => $buttonHtml,
                    ];
                }

                // Check for wire:click without method
                if (preg_match('/wire:click\s*=\s*["\']([^"\']*)["\']/', $buttonHtml, $matches)) {
                    $method = trim($matches[1]);
                    if (empty($method) || $method === '$refresh') {
                        $this->issues[] = [
                            'file' => str_replace(resource_path().DIRECTORY_SEPARATOR, '', $filePath),
                            'line' => $buttonStartLine,
                            'issue' => 'wire:click with empty or $refresh method',
                            'code' => $buttonHtml,
                        ];
                    }
                }
            }

            // Check for anchor tags that look like buttons but have no href or action
            if (preg_match('/<a[^>]*class=["\'][^"\']*btn[^"\']*["\'][^>]*>/', $line)) {
                if (! preg_match('/(href=|wire:click|@click|onclick)/', $line)) {
                    $this->issues[] = [
                        'file' => str_replace(resource_path().DIRECTORY_SEPARATOR, '', $filePath),
                        'line' => $lineNum,
                        'issue' => 'Button-styled anchor without action',
                        'code' => $line,
                    ];
                }
            }

            // Check for disabled buttons that might be permanently disabled
            if (preg_match('/<button[^>]*disabled[^>]*>/', $line)) {
                if (! preg_match('/(wire:loading|x-bind:disabled|\$wire)/', $line)) {
                    $this->issues[] = [
                        'file' => str_replace(resource_path().DIRECTORY_SEPARATOR, '', $filePath),
                        'line' => $lineNum,
                        'issue' => 'Button permanently disabled (not conditionally)',
                        'code' => $line,
                    ];
                }
            }
        }
    }
}
