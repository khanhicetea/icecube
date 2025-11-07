<?php

namespace IceTea\IceCube\Laravel;

use Illuminate\Support\ServiceProvider;
use IceTea\IceCube\Compiler\IceCubeCompiler;
use IceTea\IceCube\IceCubeRegistry;

class IceCubeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/icecube.php', 'icecube');
        
        // Register compilers in container
        $compilerConfigs = config('icecube.compilers', []);
        
        foreach ($compilerConfigs as $name => $config) {
            $this->app->singleton("icecube.compiler.{$name}", function ($app) use ($config) {
                return new IceCubeCompiler(
                    prefixClass: $config['prefix_class'],
                    sourceDir: $config['source_dir'],
                    compiledPhpDir: $config['compiled_php_dir'],
                    compiledAssetsDir: $config['compiled_assets_dir'],
                    publicUrl: $config['public_url'],
                    scriptCompiler: $app->make($config['script_compiler']),
                    styleCompiler: $app->make($config['style_compiler']),
                );
            });
        }
    }

    public function boot()
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/config/icecube.php' => config_path('icecube.php'),
            __DIR__ . '/resources/icecube.js' => resource_path('js/icecube2.js'),
        ], 'icecube');

        // Register command
        if ($this->app->runningInConsole()) {
            $this->commands([
                CompileIceCubeCommand::class,
                ViteIceCubeCommand::class,
            ]);
        } else {
            // Initialize compilers based on environment
            $compilerConfigs = config('icecube.compilers', []);
            
            if ($this->app->environment('production')) {
                // Production: Load from cache
                foreach ($compilerConfigs as $name => $config) {
                    if ($config['cache_enabled'] ?? false) {
                        IceCubeRegistry::loadCache($config['cache_file']);
                    } else {
                        $compiler = $this->app->make("icecube.compiler.{$name}");
                        $compiler->scanAndCompile();
                    }
                }
            } else {
                // Development: Initialize compilers for on-demand compilation
                foreach (array_keys($compilerConfigs) as $name) {
                    $compiler = $this->app->make("icecube.compiler.{$name}");
                    $compiler->scanAndCompile();
                }
            }
        }

    }
}