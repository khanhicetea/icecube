<?php

namespace IceTea\IceCube\Laravel;

use Illuminate\Console\Command;
use IceTea\IceCube\Compiler\ViteScriptCompiler;
use IceTea\IceCube\IceCubeRegistry;

class CompileIceCubeCommand extends Command
{
    protected $signature = 'icecube:compile {--compiler= : Compile specific compiler configuration}';
    
    protected $description = 'Compile all IceCube components and generate cache';

    public function handle()
    {
        $this->info('Compiling IceCube components...');

        $compilerConfigs = config('icecube.compilers', []);
        $selectedCompiler = $this->option('compiler');
        
        // Filter to specific compiler if requested
        if ($selectedCompiler) {
            if (!isset($compilerConfigs[$selectedCompiler])) {
                $this->error("Compiler configuration '{$selectedCompiler}' not found!");
                return Command::FAILURE;
            }
            $compilerConfigs = [$selectedCompiler => $compilerConfigs[$selectedCompiler]];
        }

        foreach ($compilerConfigs as $name => $config) {
            $this->compileConfiguration($name, $config);
        }
        
        $this->info('All components compiled successfully!');
        
        return Command::SUCCESS;
    }

    protected function compileConfiguration(string $name, array $config): void
    {
        $this->line("Compiling '{$name}' configuration...");
        
        // Load compiler from container
        $compiler = $this->laravel->make("icecube.compiler.{$name}");
        $compiler->scanAndCompile();
        
        $cacheFile = $config['cache_file'];
        
        // Skip styles in cache when using Vite (Vite handles CSS bundling)
        $includeStyles = !is_a($config['script_compiler'], ViteScriptCompiler::class, true);
        IceCubeRegistry::storeCache($cacheFile, $includeStyles);

        $this->comment("  ✓ Cache stored at: {$cacheFile}");
        
        if (!$includeStyles) {
            $this->comment("  ✓ Styles excluded from cache (using Vite)");
        }
    }
}