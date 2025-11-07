<?php

namespace IceTea\IceCube\Compiler;

class EmbedStyleScriptCompiler implements ScriptCompiler
{
  public function compile(string $componentName, string $scriptCode, string $styleCode): string
  {
    return <<<JS
{$scriptCode}

// Embedded CSS
(() => {
    const styleId = `icecube-style-{$componentName}`;
    if (document.getElementById(styleId)) return;
    const style = document.createElement('style');
    style.id = styleId;
    style.textContent = `{$styleCode}`;
    (document.head || document.body).appendChild(style);
})();
JS;
  }
}
