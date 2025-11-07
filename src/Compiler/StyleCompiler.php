<?php

namespace IceTea\IceCube\Compiler;

interface StyleCompiler
{
  public function compile(string $componentName, string $styleCode, bool $global): string;
}
