<?php

namespace IceTea\IceCube;

use IceTea\IceCube\Cache\CachedComponent;
use IceTea\IceCube\Compiler\CompiledComponent;
use IceTea\IceDOM\Node;

class IceCubeRegistry
{
  public static $registry = [];

  public static function register($className, CompiledComponent $component)
  {
    self::$registry[$className] = $component;
  }

  public static function loadCache(string $cacheFile)
  {
    if (!is_readable($cacheFile)) return false;

    $caches = include $cacheFile;
    foreach ($caches as $component) {
      self::$registry[$component->className] = $component;
    }

    spl_autoload_register(function ($class) {
      if ($component = self::$registry[$class] ?? null) {
        require_once $component->compiledPhpPath;
      }
    }, true, true);

    return true;
  }

  public static function storeCache(string $cacheFile, bool $includeStyles = true)
  {
    $caches = [];
    foreach (self::$registry as $component) {
      $cached = CachedComponent::createFromCompiledComponent($component, $includeStyles);
      $caches[$component->componentName] = $cached;
    }
    file_put_contents($cacheFile, '<?php return ' . var_export($caches, true) . ';');
  }

  public static function allStyles()
  {
    $styles = array_map(function ($component) {
      return _style(['id' => "icecube-style-{$component->componentName}"])(
        _safe($component->compiledStyles)
      );
    }, self::$registry);

    return (new Node())->appendChildren($styles);
  }

  public static function iceCubeScript()
  {
    $scripts = [];
    foreach (self::$registry as $component) {
      if ($component->scriptUrl) {
        $scripts[$component->componentName] = $component->scriptUrl;
      }
    }
    $componentScripts = json_encode($scripts);

    return _script([
      _safe(
        <<<JS
const componentScripts = JSON.parse('{$componentScripts}');
        
const initComponent = async (node) => {
  const name = node.dataset.icecube;
  if (!name || !componentScripts[name]) return;
  const mod = await import(componentScripts[name]);
  if (!mod) return;
  const refs = new Proxy({}, { get: (_, r) => node.querySelector(`[data-ref="\${r}"]`) });
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

JS
      ),
    ]);
  }
}
