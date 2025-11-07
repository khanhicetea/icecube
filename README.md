# IceCube

A PHP Single-File Component (SFC) framework for building reactive, component-based web applications. IceCube allows you to write PHP components with colocated styles and JavaScript in a single file, similar to Vue.js Single File Components.

## Goals

IceCube aims to provide:

1. **Single-File Component Architecture**: Write PHP components with their styles and JavaScript in one cohesive file (`.ice.php`)
2. **Component Encapsulation**: Scoped styles that don't leak to other components
3. **Reactive Client-Side Behavior**: Easy-to-use client-side interactivity with a refs-based system
4. **Flexible Compilation**: Support for different style preprocessors (CSS, SCSS) and bundling strategies (embedded, Vite)
5. **Performance Optimization**: Automatic compilation, caching, and on-demand loading of components
6. **Developer Experience**: Intuitive API for creating interactive components without complex build processes
7. **Progressive Enhancement**: Components work server-side first, with optional client-side enhancements

## Architecture

### Core Components

#### 1. Component System

- **[`Component`](src/Component.php)**: Abstract base class for all components
  - Provides unique ID generation for each component instance
  - Implements `SafeStringable` interface for seamless HTML rendering

- **[`SingleFileComponent`](src/SingleFileComponent.php)**: Extended base class for SFC pattern
  - Automatically extracts public properties as component props
  - Injects component metadata (`data-icecube`, `data-props`) into rendered HTML
  - Manages component lifecycle and client-side hydration

#### 2. Parser Layer

- **[`IceCubeParser`](src/Parser/IceCubeParser.php)**: Extracts content from `.ice.php` files
  - Separates PHP code from style and script tags
  - Supports multiple `<style>` tags with optional `global` attribute
  - Can parse colocated `.js` files separately
  - Generates content digest for cache invalidation

- **[`ParsedComponent`](src/Parser/ParsedComponent.php)**: Data structure holding parsed component parts

#### 3. Compiler Layer

- **[`IceCubeCompiler`](src/Compiler/IceCubeCompiler.php)**: Main compilation orchestrator
  - Scans directories for `.ice.php` files
  - Manages autoloading of components
  - Compiles components on-demand or in batch
  - Writes compiled files only when changed (optimization)

- **[`CompiledComponent`](src/Compiler/CompiledComponent.php)**: Container for compiled component artifacts

##### Style Compilers

Implement the [`StyleCompiler`](src/Compiler/StyleCompiler.php) interface:

- **[`NestingStyleCompiler`](src/Compiler/NestingStyleCompiler.php)**: Wraps styles with component selector `[data-icecube={name}]`
- **[`ScssStyleCompiler`](src/Compiler/ScssStyleCompiler.php)**: Compiles SCSS to CSS with automatic scoping

##### Script Compilers

Implement the [`ScriptCompiler`](src/Compiler/ScriptCompiler.php) interface:

- **[`EmbedStyleScriptCompiler`](src/Compiler/EmbedStyleScriptCompiler.php)**: Embeds styles directly in JavaScript
- **[`ViteScriptCompiler`](src/Compiler/ViteScriptCompiler.php)**: Imports CSS separately for Vite bundling

#### 4. Registry System

- **[`IceCubeRegistry`](src/IceCubeRegistry.php)**: Global component registry
  - Stores compiled component metadata
  - Provides cache loading/storing functionality
  - Generates collection of all component styles
  - Injects client-side initialization script

#### 5. Cache System

- **[`CachedComponent`](src/Cache/CachedComponent.php)**: Lightweight cached component data
  - Serializable component metadata for production
  - Supports PHP's `var_export` for efficient caching

### Compilation Flow

```
.ice.php file
    ↓
IceCubeParser
    ↓
ParsedComponent (PHP + Styles + JS)
    ↓
IceCubeCompiler
    ├→ StyleCompiler → compiled.css
    ├→ ScriptCompiler → compiled.js (with styles)
    └→ PHP class → compiled.php
    ↓
CompiledComponent
    ↓
IceCubeRegistry
```

### Client-Side Runtime

The registry generates JavaScript that:
1. Dynamically imports component scripts on-demand
2. Initializes components via `MutationObserver` (supports dynamic content)
3. Provides a `refs` proxy for easy DOM element access
4. Passes component props from server to client
5. Tracks component initialization states (`icing` → `iced`)

### Style Scoping

Styles are automatically scoped by wrapping them with the component's data attribute:

```css
/* Original */
.button { color: red; }

/* Compiled (Nesting) */
[data-icecube=App_Components_Counter] { .button { color: red; } }

/* Or with SCSS compiler */
[data-icecube="App_Components_Counter"] .button { color: red; }
```

Global styles (marked with `<style global>`) bypass scoping.

## Usage Guide

### Basic Setup (Without Vite)

#### 1. Create a Component

Create a file `app/Components/Counter.ice.php`:

```php
<?php

namespace App\Components;

use IceTea\IceCube\SingleFileComponent;
use IceTea\IceDOM\HtmlNode;

class Counter extends SingleFileComponent
{
  public function __construct(
    public int $initialCount = 0,
    public string $label = 'Counter',
  ) {}

  public function render(): HtmlNode
  {
    return _div(['class' => 'counter-container'], [
      _h2($this->label),
      _p([
        _span('Count: '),
        _strong(['class' => 'count-value'], [
          _span($this->initialCount)->data_ref('counter'),
        ]),
      ]),
      _div([
        _button(['class' => 'btn'], '-')->data_ref('decrementBtn'),
        _button(['class' => 'btn'], '+')->data_ref('incrementBtn'),
      ], ['class' => 'button-group']),
    ]);
  }
}

?>

<style>
  & {
    max-width: 400px;
    margin: 2rem auto;
    padding: 2rem;
    background: #f8f9fa;
  }

  .count-value {
    font-size: 2rem;
    color: #007bff;
  }

  .button-group {
    display: flex;
    gap: 0.5rem;
  }
</style>

<script>
export default function ({ root, refs, props }) {
  const { counter, incrementBtn, decrementBtn } = refs;
  
  let count = props.initialCount || 0;
  const render = () => {
    counter.textContent = count;
  };
  
  render();
  
  incrementBtn.addEventListener('click', () => {
    count++;
    render();
  });
  
  decrementBtn.addEventListener('click', () => {
    count--;
    render();
  });
}
</script>
```

Alternatively, separate the JavaScript into `Counter.js`:

```javascript
// app/Components/Counter.js
export default function ({ root, refs, props }) {
  const { counter, incrementBtn, decrementBtn } = refs;
  // ... rest of the logic
}
```

#### 2. Initialize the Compiler

In your application bootstrap (e.g., `bootstrap/app.php`):

```php
use IceTea\IceCube\Compiler\IceCubeCompiler;
use IceTea\IceCube\Compiler\EmbedStyleScriptCompiler;

$compiler = new IceCubeCompiler(
    prefixClass: 'App\\Components',
    sourceDir: __DIR__ . '/../app/Components',
    compiledPhpDir: storage_path('icecube'),
    compiledAssetsDir: public_path('icecube'),
    publicUrl: '/icecube',
    scriptCompiler: new EmbedStyleScriptCompiler()
);

// For development: compile on-demand via autoloader (automatic)
// For production: pre-compile all components
$compiler->scanAndCompile();
```

#### 3. Render Components in Views

In your Blade template:

```php
<!DOCTYPE html>
<html>
<head>
    <title>My App</title>
    <?= IceCubeRegistry::allStyles() ?>
</head>
<body>
    <?= new Counter(initialCount: 10, label: 'My Counter') ?>
    
    <?= IceCubeRegistry::iceCubeScript() ?>
</body>
</html>
```

#### 4. Production Optimization

For production, use caching:

```php
// During build/deployment
use IceTea\IceCube\IceCubeRegistry;

$compiler->scanAndCompile();
IceCubeRegistry::storeCache(storage_path('icecube/cache.php'));
```

```php
// In production bootstrap
IceCubeRegistry::loadCache(storage_path('icecube/cache.php'));
```

### Advanced Setup (With Vite)

#### 1. Install Required Dependencies

```bash
npm install --save-dev vite glob
# Optional: For reactive state management
npm install @preact/signals
```

#### 2. Configure Vite

Update [`vite.config.js`](vite.config.js) to use Vite's glob import for dynamic component loading:

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
```

#### 3. Create IceCube Loader Script

Create [`resources/js/icecube.js`](resources/js/icecube.js):

```javascript
// Use Vite's import.meta.glob for dynamic imports
const componentScripts = import.meta.glob('./**/*.js', {
    eager: false,
    base: '../../storage/app/public/icecube'
});

const initComponent = async (node) => {
    const name = node.dataset.icecube;
    if (!name) return;
    const mod = await componentScripts[`./${name}.js`]?.();
    if (!mod) return;
    const refs = new Proxy({}, { get: (_, r) => node.querySelector(`[data-ref="${r}"]`) });
    node.dataset.cube = 'icing';
    await mod.default({ root: node, refs, props: JSON.parse(node.dataset.props || '{}') });
    node.dataset.cube = 'iced';
};

(() => {
    document.querySelectorAll('[data-icecube]').forEach(initComponent);

    const observer = new MutationObserver((mutations) => {
        mutations.flatMap(m => [...m.addedNodes]).forEach(node => {
            if (node.nodeType === Node.ELEMENT_NODE && node.dataset.icecube !== undefined) {
                initComponent(node);
                node.querySelectorAll?.('[data-icecube]').forEach(initComponent);
            }
        });
    });

    observer.observe(document.documentElement, { childList: true, subtree: true });
})();
```

#### 4. Import IceCube in Your App

Update [`resources/js/app.js`](resources/js/app.js):

```javascript
import './icecube';
```

#### 5. Configure Compiler with Vite Strategy

In your application bootstrap or controller:

```php
use IceTea\IceCube\Compiler\IceCubeCompiler;
use IceTea\IceCube\Compiler\ViteScriptCompiler;
use IceTea\IceCube\Compiler\ScssStyleCompiler;

// Optional: Configure SCSS with custom import paths
$styleCompiler = new ScssStyleCompiler();
$scss = $styleCompiler->getCompiler();
$scss->addImportPath(base_path('app/Components'));

$compiler = new IceCubeCompiler(
    prefixClass: 'App\\Components',
    sourceDir: base_path('app/Components'),
    compiledPhpDir: storage_path('app/private/icecube'),
    compiledAssetsDir: storage_path('app/public/icecube'),
    publicUrl: '/storage/icecube',
    scriptCompiler: new ViteScriptCompiler(),
    styleCompiler: $styleCompiler, // Optional: for SCSS support
);

$compiler->scanAndCompile();

// When using Vite, skip storing styles in cache (they're handled by Vite)
IceCubeRegistry::storeCache(
    storage_path('app/private/icecube/cache.php'),
    includeStyles: false
);
```

#### 6. Run Vite Dev Server

```bash
npm run dev
```

With `ViteScriptCompiler`, compiled JavaScript will import CSS separately:

```javascript
// Compiled output: storage/app/public/icecube/App_Components_Counter.js
import './App_Components_Counter.css';

export default function ({ root, refs, props }) {
  // Your component logic
}
```

#### 7. Use NPM Packages in Components

You can import any NPM package in your component JavaScript files:

```javascript
// app/Components/Counter.js
import { signal, effect } from "@preact/signals";

export default function ({ root, refs, props }) {
    const { counter, incrementBtn, decrementBtn } = refs;
    
    const count = signal(props.initialCount || 0);
    
    effect(() => {
        counter.textContent = count.value;
    });
    
    incrementBtn.addEventListener('click', () => count.value++);
    decrementBtn.addEventListener('click', () => count.value--);
}
```

## Laravel Integration Guide

### Quick Setup

#### 1. Register Service Provider

Add [`IceCubeServiceProvider`](src/Laravel/IceCubeServiceProvider.php) to `config/app.php`:

```php
'providers' => [
    // ...
    IceTea\IceCube\Laravel\IceCubeServiceProvider::class,
],
```

Or for Laravel 11+, add to `bootstrap/providers.php`:

```php
return [
    // ...
    IceTea\IceCube\Laravel\IceCubeServiceProvider::class,
];
```

#### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=icecube-config
```

This creates [`config/icecube.php`](src/Laravel/config/icecube.php) where you can customize:
- Component namespace and directories
- Compiler strategies (Vite/Embed, SCSS/CSS)
- Cache settings

#### 3. Create Storage Link

```bash
php artisan storage:link
```

#### 4. Use Components in Blade Views

```blade
<!DOCTYPE html>
<html>
<head>
    <title>IceCube Demo</title>
    @vite(['resources/js/app.js'])
</head>
<body>
    <?= new \App\Components\Counter(initialCount: 10, label: 'My Counter') ?>
    <?= new \App\Components\Counter(initialCount: 100, label: 'Another Counter') ?>
</body>
</html>
```

### Service Provider Behavior

The [`IceCubeServiceProvider`](src/Laravel/IceCubeServiceProvider.php) automatically:

- **Development Mode**: Compiles components on-demand via autoloader
- **Production Mode**: Loads pre-compiled components from cache for maximum performance

### Production Build Command

Compile all components and generate cache before deployment:

```bash
php artisan icecube:compile
```

This [`CompileIceCubeCommand`](src/Laravel/CompileIceCubeCommand.php) scans all `.ice.php` files, compiles them, and stores the cache.

**Note**: The command automatically detects if you're using [`ViteScriptCompiler`](src/Compiler/ViteScriptCompiler.php) and will skip storing styles in cache (since Vite handles CSS bundling separately).

Add to your deployment script:

```bash
# Deploy script
composer install --optimize-autoloader --no-dev
php artisan icecube:compile
npm run build  # Vite handles CSS bundling
php artisan config:cache
php artisan route:cache
```

### Configuration

Configuration in [`config/icecube.php`](src/Laravel/config/icecube.php):

```php
return [
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
            'cache_file' => storage_path('app/private/icecube/cache.php'),
        ],
    ],
];
```

### SCSS Import Paths (Optional)

To use SCSS imports like `@import './_base.scss'`, configure in your service provider:

```php
use IceTea\IceCube\Compiler\ScssStyleCompiler;

$styleCompiler = app(ScssStyleCompiler::class);
$scss = $styleCompiler->getCompiler();
$scss->addImportPath(base_path('app/Components'));
```

### Dynamic Components (HTMX/AJAX)

Components work seamlessly with dynamic content loading:

```php
// Controller
public function loadComponent(Request $request)
{
    return (string) new Counter($request->count, 'Dynamic');
}
```

```blade
<!-- View -->
<div hx-get="/load-component?count=100" hx-swap="outerHTML">
    Load Component
</div>
```

The [`icecube.js`](resources/js/icecube.js) runtime automatically initializes dynamically added components.

### Multiple Compiler Configurations

Configure multiple compilers for different component sets in [`config/icecube.php`](src/Laravel/config/icecube.php):

```php
return [
    'compilers' => [
        'main' => [
            'prefix_class' => 'App\\Components',
            'source_dir' => base_path('app/Components'),
            'compiled_php_dir' => storage_path('app/private/icecube/main'),
            'compiled_assets_dir' => storage_path('app/public/icecube/main'),
            'public_url' => '/storage/icecube/main',
            'script_compiler' => ViteScriptCompiler::class,
            'style_compiler' => ScssStyleCompiler::class,
            'cache_enabled' => true,
            'cache_file' => storage_path('app/private/icecube/main-cache.php'),
        ],
        
        'admin' => [
            'prefix_class' => 'App\\Admin\\Components',
            'source_dir' => base_path('app/Admin/Components'),
            'compiled_php_dir' => storage_path('app/private/icecube/admin'),
            'compiled_assets_dir' => storage_path('app/public/icecube/admin'),
            'public_url' => '/storage/icecube/admin',
            'script_compiler' => EmbedStyleScriptCompiler::class,
            'style_compiler' => NestingStyleCompiler::class,
            'cache_enabled' => true,
            'cache_file' => storage_path('app/private/icecube/admin-cache.php'),
        ],
    ],
];
```

Compilers are automatically registered in the Laravel container as `icecube.compiler.{name}` singletons.

**Compile All:**
```bash
php artisan icecube:compile
```

**Compile Specific:**
```bash
php artisan icecube:compile --compiler=admin
```

The [`IceCubeServiceProvider`](src/Laravel/IceCubeServiceProvider.php) automatically:
- Registers all compilers in the container
- In development: initializes compilers for on-demand compilation
- In production: loads all caches for maximum performance

### Component API

#### Props System

Public properties are automatically passed to client-side:

```php
class MyComponent extends SingleFileComponent
{
  public function __construct(
    public string $title,      // Passed to client
    public array $items,       // Passed to client
    private string $secret,    // NOT passed to client
  ) {}
}
```

#### Refs System

Use `data_ref()` to mark elements for easy client-side access:

```php
_button('Click me')->data_ref('myButton')
```

```javascript
export default function ({ root, refs, props }) {
  refs.myButton.addEventListener('click', () => {
    console.log('Clicked!');
  });
}
```

#### Component Parameters

- `root`: The component's root DOM element
- `refs`: Proxy object for accessing elements with `data-ref` attributes
- `props`: Public properties passed from PHP as JSON

#### Style Scoping

- Regular `<style>` tags: Scoped to component
- `<style global>`: Applied globally, not scoped

```php
<style>
  /* Scoped: only affects this component */
  .button { color: blue; }
</style>

<style global>
  /* Global: affects entire page */
  body { margin: 0; }
</style>
```

### SCSS Support

To use SCSS, configure the compiler with `ScssStyleCompiler`:

```php
use IceTea\IceCube\Compiler\ScssStyleCompiler;

$compiler = new IceCubeCompiler(
    // ... other params
    styleCompiler: new ScssStyleCompiler()
);
```

Then use SCSS syntax in your components:

```php
<style>
  $primary-color: #007bff;
  
  & {
    background: $primary-color;
    
    .nested {
      color: darken($primary-color, 10%);
    }
  }
</style>
```

## File Structure

```
icecube/
├── src/
│   ├── Component.php                       # Base component class
│   ├── SingleFileComponent.php             # SFC base class
│   ├── IceCubeRegistry.php                # Component registry
│   ├── Parser/
│   │   ├── IceCubeParser.php             # Parses .ice.php files
│   │   └── ParsedComponent.php            # Parsed data structure
│   ├── Compiler/
│   │   ├── IceCubeCompiler.php           # Main compiler
│   │   ├── CompiledComponent.php          # Compiled data structure
│   │   ├── StyleCompiler.php              # Style compiler interface
│   │   ├── NestingStyleCompiler.php       # CSS nesting strategy
│   │   ├── ScssStyleCompiler.php          # SCSS compilation strategy
│   │   ├── ScriptCompiler.php             # Script compiler interface
│   │   ├── EmbedStyleScriptCompiler.php   # Embed CSS in JS
│   │   └── ViteScriptCompiler.php         # Vite-compatible output
│   ├── Cache/
│   │   └── CachedComponent.php            # Cached component data
│   └── Laravel/                            # Laravel integration
│       ├── IceCubeServiceProvider.php     # Service provider
│       ├── CompileIceCubeCommand.php      # Artisan command
│       └── config/
│           └── icecube.php                # Configuration file
```

## Best Practices

1. **Component Organization**: Keep components in a dedicated directory (e.g., `app/Components`)
2. **Naming Convention**: Use PascalCase for component class names and files
3. **Separation of Concerns**: For complex JavaScript, use separate `.js` files
4. **Style Scoping**: Prefer scoped styles; use global styles sparingly
5. **Props Validation**: Validate and type-hint public properties
6. **Production Builds**: Always pre-compile and cache components in production
7. **Asset Strategy**: Choose between embedded styles or Vite based on your build setup

## License

MIT License