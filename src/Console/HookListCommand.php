<?php

namespace RealZone22\LaraHooks\Console;

use Illuminate\Console\Command;
use RealZone22\LaraHooks\Facades\LaraHooks;

class HookListCommand extends Command
{
    protected $signature = 'hook:list';

    protected $description = 'List all registered hooks';

    public function handle(): void
    {
        $this->displayRegisteredHooks();
        $this->displayTemplateHooks();
        $this->displayClassHooks();
    }

    /**
     * Display all registered hook listeners in a table.
     */
    private function displayRegisteredHooks(): void
    {
        $list = LaraHooks::getListeners();
        $array = [];

        foreach ($list as $hook => $lister) {
            foreach ($lister as $key => $element) {
                $array[] = [
                    $key,
                    $hook,
                    $element['caller']['class'],
                ];
            }
        }

        $this->table(['Sort', 'Name', 'Listener class'], $array);
    }

    /**
     * Display all hooks found in Blade templates.
     */
    private function displayTemplateHooks(): void
    {
        $this->info('');
        $this->info('Available Hooks in Templates:');

        $availableHooks = $this->findHooksInTemplates();

        if (empty($availableHooks)) {
            $this->warn('No hooks found in templates');

            return;
        }

        $hookArray = [];
        foreach ($availableHooks as $hook => $files) {
            foreach ($files as $file) {
                $hookArray[] = [$hook, $file];
            }
        }

        $this->table(['Hook Name', 'Template File'], $hookArray);
    }

    /**
     * Scan all view paths for @hook and @shook directives.
     */
    private function findHooksInTemplates(): array
    {
        $hooks = [];
        $viewPaths = config('view.paths', [resource_path('views')]);

        foreach ($viewPaths as $path) {
            $this->scanDirectory($path, $hooks);
        }

        return $hooks;
    }

    /**
     * Recursively scan directory for Blade files and extract hook names.
     */
    private function scanDirectory(string $directory, array &$hooks): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $files = glob($directory.'/*.blade.php');
        $directories = glob($directory.'/*', GLOB_ONLYDIR);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $relativePath = str_replace(resource_path('views/'), '', $file);

            preg_match_all('/@(?:hook|shook)\([\'"]([^\'"]+)[\'"]\)/', $content, $matches);

            if (! empty($matches[1])) {
                foreach ($matches[1] as $hookName) {
                    if (! isset($hooks[$hookName])) {
                        $hooks[$hookName] = [];
                    }
                    if (! in_array($relativePath, $hooks[$hookName])) {
                        $hooks[$hookName][] = $relativePath;
                    }
                }
            }
        }

        foreach ($directories as $subDirectory) {
            $this->scanDirectory($subDirectory, $hooks);
        }
    }

    /**
     * Display all hooks found in PHP classes.
     */
    private function displayClassHooks(): void
    {
        $this->info('');
        $this->info('Available Hooks in Classes:');

        $classHooks = $this->findHooksInClasses();

        if (empty($classHooks)) {
            $this->warn('No hooks found in classes');

            return;
        }

        $classHookArray = [];
        foreach ($classHooks as $hook => $locations) {
            foreach ($locations as $location) {
                $classHookArray[] = [
                    $hook,
                    $location['class'],
                    $location['method'],
                    $location['line'],
                ];
            }
        }

        $this->table(['Hook Name', 'Class', 'Method', 'Line'], $classHookArray);
    }

    /**
     * Scan all PHP files in app directory for LaraHooks::get() calls.
     */
    private function findHooksInClasses(): array
    {
        $hooks = [];
        $appPath = app_path();

        $this->scanPhpDirectory($appPath, $hooks);

        return $hooks;
    }

    /**
     * Recursively scan directory for PHP files and extract LaraHooks::get() calls.
     */
    private function scanPhpDirectory(string $directory, array &$hooks): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $files = glob($directory.'/*.php');
        $directories = glob($directory.'/*', GLOB_ONLYDIR);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $relativePath = str_replace(app_path().'/', '', $file);

            preg_match_all('/LaraHooks::get\([\'"]([^\'"]+)[\'"]/', $content, $matches, PREG_OFFSET_CAPTURE);

            if (! empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    $hookName = $match[0];
                    $offset = $match[1];
                    $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;
                    $classInfo = $this->findClassAndMethod($content, $offset);

                    if (! isset($hooks[$hookName])) {
                        $hooks[$hookName] = [];
                    }

                    $hooks[$hookName][] = [
                        'class' => $classInfo['class'] ?: $relativePath,
                        'method' => $classInfo['method'] ?: 'unknown',
                        'line' => $lineNumber,
                    ];
                }
            }
        }

        foreach ($directories as $subDirectory) {
            $this->scanPhpDirectory($subDirectory, $hooks);
        }
    }

    /**
     * Extract class name and method name from content at given offset.
     */
    private function findClassAndMethod(string $content, int $offset): array
    {
        $beforeOffset = substr($content, 0, $offset);

        preg_match('/class\s+(\w+)/', $content, $classMatch);
        $className = $classMatch[1] ?? null;

        preg_match_all('/(?:public|private|protected)?\s*function\s+(\w+)\s*\(/', $beforeOffset, $methodMatches, PREG_OFFSET_CAPTURE);

        $methodName = null;
        if (! empty($methodMatches[1])) {
            $lastMethod = end($methodMatches[1]);
            $methodName = $lastMethod[0];
        }

        return [
            'class' => $className,
            'method' => $methodName,
        ];
    }
}
