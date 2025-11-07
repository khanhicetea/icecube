<?php

namespace IceTea\IceCube\Compiler;

interface ScriptCompiler
{
  public function compile(string $componentName, string $scriptCode, string $styleCode): string;
}
