<?php

namespace IceTea\IceCube\Compiler;

class CompiledComponent
{
  public function __construct(
    public string $className,
    public string $componentName,
    public string $compiledPhpPath,
    public string $compiledAssetsPath,
    public string $compiledPhp,
    public ?string $compiledJs,
    public ?string $compiledStyles,
    public ?string $scriptUrl,
    public string $digest,
  ) {}
}
