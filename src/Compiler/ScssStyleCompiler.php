<?php

namespace IceTea\IceCube\Compiler;

use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;

class ScssStyleCompiler implements StyleCompiler
{
  public function __construct(
    private ?Compiler $compiler = null,
  ) {
    if ($compiler === null) {
      $this->compiler = new Compiler();
      $this->compiler->setOutputStyle(OutputStyle::COMPRESSED);
      $this->compiler->setSourceMap(Compiler::SOURCE_MAP_NONE);
    }
  }

  public function getCompiler()
  {
    return $this->compiler;
  }

  public function compile(string $componentName, string $styleCode, bool $global): string
  {
    if (empty($styleCode)) {
      return '';
    }

    $compiler = $this->getCompiler();

    return $compiler->compileString($global ? $styleCode : "[data-icecube=\"{$componentName}\"] { {$styleCode} }")->getCss();
  }
}
