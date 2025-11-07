<?php

namespace IceTea\IceCube\Compiler;

class ViteScriptCompiler implements ScriptCompiler
{
  public function compile(string $componentName, string $scriptCode, string $styleCode): string
  {
    return <<<JS
// Embedded CSS
import './{$componentName}.css';

{$scriptCode}
JS;
  }
}
