<?php

namespace IceTea\IceCube;

use Closure;
use IceTea\IceDOM\HtmlNode;
use IceTea\IceDOM\SafeStringable;

abstract class Component implements SafeStringable
{
  protected ?string $id = null;
  protected array $slots = [];

  public function getId(): string
  {
    if ($this->id === null) {
      $this->setId();
    }

    return $this->id;
  }

  public function setId(?string $id = null): self
  {
    $this->id = $id ?? $this->generateUniqueId();

    return $this;
  }

  protected function generateUniqueId(): string
  {
    return uniqid('icecube-', false);
  }

  public function children($default  = null)
  {
    return $this->slot('children', $default);
  }

  public function slot(string $name, $default = null)
  {
    $slot = $this->slots[$name] ?? null;

    if ($slot !== null) {
      return $slot;
    }

    if (is_callable($default)) {
      $fn = Closure::fromCallable($default);
      return $fn($this);
    }

    return $default;
  }

  public function fillSlot($content = null, string $name = 'children'): self
  {
    $this->slots[$name] = $content;
    return $this;
  }

  public function __invoke()
  {
    $this->fillSlot(...func_get_args());

    return $this;
  }

  public function __toString(): string
  {
    return (string) $this->render();
  }

  abstract public function render(): HtmlNode | string;
}
