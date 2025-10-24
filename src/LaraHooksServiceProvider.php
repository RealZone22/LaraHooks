<?php

namespace RealZone22\LaraHooks;

use Illuminate\Support\Facades\Blade;
use RealZone22\LaraHooks\Console\HookListCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaraHooksServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('larahooks')
            ->hasCommand(HookListCommand::class)
            ->hasConfigFile();

        $this->bootDirectives();
    }

    protected function bootDirectives()
    {
        Blade::directive('hook', function ($parameter) {
            $parameter = trim($parameter, '()');
            $parameters = explode(',', $parameter);
            $name = trim($parameters[0], "'");

            return ' <'.'?php
                $__hook_name="'.$name.'";
                $__definedVars = get_defined_vars();

                ob_start();
                $__hook_has_endhook = true;
            ?'.'>';
        });

        Blade::directive('endhook', function ($parameter) {
            return ' <'.'?php
                $__definedVars = get_defined_vars();
                unset($__definedVars["__hook_name"]);
                unset($__definedVars["__hook_has_endhook"]);
                $__hook_content = ob_get_clean();
                $output = \RealZone22\LaraHooks\Facades\LaraHooks::get("'.config('larahooks.blade_prefix').'$__hook_name",["data"=>$__definedVars],function($data) { return null; },$__hook_content);
                unset($__hook_name);
                unset($__hook_content);
                if ($output) echo $output;
            ?'.'>';
        });

        Blade::directive('shook', function ($parameter) {
            $parameter = trim($parameter, '()');
            $parameters = explode(',', $parameter);
            $name = trim($parameters[0], "'");

            return ' <'.'?php
                $__definedVars = get_defined_vars();
                $output = \RealZone22\LaraHooks\Facades\LaraHooks::get("'.config('larahooks.blade_prefix').''.$name.'",["data"=>$__definedVars],function($data) { return null; });
                if ($output) echo $output;
            ?'.'>';
        });
    }
}
