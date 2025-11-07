<?php

namespace IceTea\IceCube\Cache;

use IceTea\IceCube\Compiler\CompiledComponent;

class CachedComponent
{
  public function __construct(
    public string $className,
    public string $componentName,
    public string $compiledPhpPath,
    public string $compiledAssetsPath,
    public ?string $compiledStyles,
    public ?string $scriptUrl,
    public string $digest,
  ) {}

  public static function createFromCompiledComponent(
    CompiledComponent $compiledComponent,
    bool $includeStyles = true,
  ): self
  {
    return new self(
      $compiledComponent->className,
      $compiledComponent->componentName,
      $compiledComponent->compiledPhpPath,
      $compiledComponent->compiledAssetsPath,
      $includeStyles ? $compiledComponent->compiledStyles : null,
      $compiledComponent->scriptUrl,
      $compiledComponent->digest,
    );
  }

  public static function __set_state($properties)
  {
    return new self(
      $properties['className'],
      $properties['componentName'],
      $properties['compiledPhpPath'],
      $properties['compiledAssetsPath'],
      $properties['compiledStyles'],
      $properties['scriptUrl'],
      $properties['digest'],
    );
  }
}
