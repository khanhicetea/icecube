<?php

namespace IceTea\IceCube\Parser;

class ParsedComponent
{
  public function __construct(
    public string $className,
    public string $componentName,
    public string $php,
    public ?string $js,
    public array $styles,
    public string $digest,
  ) {}
}
