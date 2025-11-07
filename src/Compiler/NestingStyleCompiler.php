<?php

namespace IceTea\IceCube\Compiler;

class NestingStyleCompiler implements StyleCompiler
{
  public function compile(string $componentName, string $styleCode, bool $global): string
  {
    if ($global) return $styleCode;

    // $rootReplace = preg_replace('/^\s*\:root\s*\{\s*/', '', $styleCode);

    return "[data-icecube={$componentName}] { {$styleCode} }";
  }
}
