<?php

namespace IceTea\IceCube\Compiler;

use IceTea\IceCube\IceCubeRegistry;
use IceTea\IceCube\Parser\IceCubeParser;
use IceTea\IceCube\Parser\ParsedComponent;
use IceTea\IceCube\Compiler\StyleCompiler;
use IceTea\IceCube\Compiler\NestingStyleCompiler;
use IceTea\IceCube\Compiler\ScriptCompiler;
use IceTea\IceCube\Compiler\EmbedStyleScriptCompiler;

class IceCubeCompiler
{
  public function __construct(
    private string $prefixClass,
    private string $sourceDir,
    private string $compiledPhpDir,
    private string $compiledAssetsDir,
    private string $publicUrl,
    private IceCubeParser $parser = new IceCubeParser,
    private StyleCompiler $styleCompiler = new NestingStyleCompiler,
    private ScriptCompiler $scriptCompiler = new EmbedStyleScriptCompiler,
  ) {
    $this->registerAutoload();
  }

  protected function sureCompiledDir()
  {
    if (!is_dir($this->compiledPhpDir)) {
      mkdir($this->compiledPhpDir, 0750, true);
    }
    if (!is_dir($this->compiledAssetsDir)) {
      mkdir($this->compiledAssetsDir, 0750, true);
    }
  }

  public function scanAndCompile()
  {
    $this->sureCompiledDir();

    $files = glob($this->sourceDir . DIRECTORY_SEPARATOR . '*.ice.php');
    foreach ($files as $file) {
      $className = $this->prefixClass . '\\' . basename($file, '.ice.php');
      $compiledComponent = $this->compile($className, $file);
      IceCubeRegistry::register($className, $compiledComponent);
    }
  }

  protected function registerAutoload()
  {
    $this->sureCompiledDir();

    spl_autoload_register(function ($class) {
      if (str_starts_with($class, $this->prefixClass)) {
        $path = str_replace('\\', DIRECTORY_SEPARATOR, str_replace($this->prefixClass, '', $class));
        $realPath = $this->sourceDir . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) . '.ice.php';

        $compiledComponent = $this->compile($class, $realPath);
        require_once $compiledComponent->compiledPhpPath;
      }
    }, true, true);
  }

  public function compile($className, $componentPath): CompiledComponent
  {
    $componentSource = file_get_contents($componentPath);
    $colocateJs = preg_replace('/\.ice\.php$/', '.js', $componentPath);
    $colocateJsSource = is_readable($colocateJs) ? file_get_contents($colocateJs) : null;

    $parsed = $this->parser->parse($className, $componentSource, $colocateJsSource);
    $compiledComponent = $this->compileComponent($parsed);

    IceCubeRegistry::register($className, $compiledComponent);

    return $compiledComponent;
  }

  private function writeIfChanged($path, $content)
  {
    if (!file_exists($path) || file_get_contents($path) !== $content) {
      file_put_contents($path, $content);
    }
  }

  protected function compileComponent(ParsedComponent $parsed)
  {
    $phpPath = $this->compiledPhpDir . DIRECTORY_SEPARATOR . $parsed->componentName . '.php';
    $compiledAssetsPath = $this->compiledAssetsDir . DIRECTORY_SEPARATOR . $parsed->componentName;
    $jsPath = $compiledAssetsPath . '.js';
    $cssPath = $compiledAssetsPath . '.css';

    $compiledPhp = $parsed->php;
    $compiledStyles = implode("\n", array_map(function ($style) use ($parsed) {
      return $this->styleCompiler->compile($parsed->componentName, $style['content'], $style['global']);
    }, $parsed->styles));

    $compiledJs = $this->scriptCompiler->compile($parsed->componentName, $parsed->js ?: '', $compiledStyles);

    $this->writeIfChanged($phpPath, $compiledPhp);
    $this->writeIfChanged($jsPath, $compiledJs);
    $this->writeIfChanged($cssPath, $compiledStyles);

    return new CompiledComponent(
      className: $parsed->className,
      componentName: $parsed->componentName,
      compiledPhpPath: $phpPath,
      compiledAssetsPath: $compiledAssetsPath,
      compiledPhp: $compiledPhp,
      compiledJs: $compiledJs,
      compiledStyles: $compiledStyles,
      scriptUrl: $this->publicUrl . '/' . $parsed->componentName . '.js?digest=' . $parsed->digest,
      digest: $parsed->digest,
    );
  }
}
