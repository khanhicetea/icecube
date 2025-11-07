<?php

namespace IceTea\IceCube\Laravel;

use Illuminate\Console\Command;
use IceTea\IceCube\Compiler\ViteScriptCompiler;

class ViteIceCubeCommand extends Command
{
    protected $signature = 'icecube:vite {--compiler= : Compile specific compiler configuration}';
    protected $description = 'Vite utility for all IceCube components and generate cache';

    public function handle()
    {
        $this->info('Compiling IceCube components...');

        $configs = $this->getCompilerConfigs();
        if (!$configs) {
            return Command::FAILURE;
        }

        $manifest = $this->loadManifest();
        if (!$manifest) {
            return Command::FAILURE;
        }

        foreach ($configs as $name => $config) {
            if (!$this->isViteCompiler($config)) {
                $this->warn("Skipping '{$name}' - not a ViteScriptCompiler.");
                continue;
            }

            $this->processCompiler($name, $config, $manifest);
        }

        $this->saveManifest($manifest);
        $this->info('All components packed successfully!');
        
        return Command::SUCCESS;
    }

    private function getCompilerConfigs(): ?array
    {
        $configs = config('icecube.compilers', []);
        $selected = $this->option('compiler');
        
        if (!$selected) {
            return $configs;
        }

        if (!isset($configs[$selected])) {
            $this->error("Compiler configuration '{$selected}' not found!");
            return null;
        }

        return [$selected => $configs[$selected]];
    }

    private function loadManifest(): ?array
    {
        $path = public_path('build/manifest.json');
        
        if (!file_exists($path)) {
            $this->error('Vite manifest.json not found! Please run Vite build first.');
            return null;
        }

        return json_decode(file_get_contents($path), true);
    }

    private function saveManifest(array $manifest): void
    {
        file_put_contents(
            public_path('build/manifest.json'),
            json_encode($manifest, JSON_PRETTY_PRINT)
        );
    }

    private function isViteCompiler(array $config): bool
    {
        return is_a($config['script_compiler'], ViteScriptCompiler::class, true);
    }

    private function processCompiler(string $name, array $config, array &$manifest): void
    {
        $prefix = str_replace('\\', '_', $config['prefix_class']);
        $cssFiles = $this->extractCssFiles($manifest, $prefix);
        
        if (empty($cssFiles)) {
            return;
        }

        $this->createPackedCss($name, $cssFiles);
        $this->updateManifest($manifest, $name);
    }

    private function extractCssFiles(array &$manifest, string $prefix): array
    {
        $files = [];
        
        foreach ($manifest as &$meta) {
            if (str_starts_with($meta['name'] ?? '', $prefix) && isset($meta['css'])) {
                $files = [...$files, ...$meta['css']];
                unset($meta['css']);
            }
        }
        
        return array_unique($files);
    }

    private function createPackedCss(string $name, array $files): void
    {
        $buildPath = public_path('build');
        $content = implode("\n", array_map(
            fn($file) => file_get_contents($buildPath . '/' . $file),
            $files
        ));
        
        file_put_contents(
            $buildPath . '/assets/icecube_' . $name . '_styles.css',
            $content
        );
    }

    private function updateManifest(array &$manifest, string $name): void
    {
        $key = 'icecube_' . $name . '_styles.css';
        $file = 'assets/icecube_' . $name . '_styles.css';
        
        $manifest[$key] = [
            'file' => $file,
            'name' => $key,
            'src' => $file,
            'isEntry' => false,
            'isDynamicEntry' => false,
        ];
        
        $manifest['resources/js/icecube.js']['css'] ??= [];
        $manifest['resources/js/icecube.js']['css'][] = $file;
    }
}