<?php

use IceTea\IceCube\Compiler\ScssStyleCompiler;
use IceTea\IceCube\Compiler\ViteScriptCompiler;

return [
    /*
    |--------------------------------------------------------------------------
    | Compiler Configurations
    |--------------------------------------------------------------------------
    |
    | Define compiler configurations for different component sets.
    | Each configuration can have its own namespace, directories, and compilers.
    |
    */
    'compilers' => [
        'default' => [
            'prefix_class' => 'App\\Components',
            'source_dir' => base_path('app/Components'),
            'compiled_php_dir' => storage_path('app/private/icecube'),
            'compiled_assets_dir' => storage_path('app/public/icecube'),
            'public_url' => '/storage/icecube',
            'script_compiler' => ViteScriptCompiler::class,
            'style_compiler' => ScssStyleCompiler::class,
            'cache_enabled' => env('ICECUBE_CACHE', true),
            'cache_file' => storage_path('app/private/icecube/default.cache.php'),
        ],

        // Example: Admin components with different compilers
        // 'admin' => [
        //     'prefix_class' => 'App\\Admin\\Components',
        //     'source_dir' => base_path('app/Admin/Components'),
        //     'compiled_php_dir' => storage_path('app/private/icecube/admin'),
        //     'compiled_assets_dir' => storage_path('app/public/icecube/admin'),
        //     'public_url' => '/storage/icecube/admin',
        //     'script_compiler' => EmbedStyleScriptCompiler::class,
        //     'style_compiler' => NestingStyleCompiler::class,
        //     'cache_enabled' => true,
        //     'cache_file' => storage_path('app/private/icecube/admin.cache.php'),
        // ],
    ],
];