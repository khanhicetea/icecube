<?php

namespace IceTea\IceCube;

use IceTea\IceDOM\HtmlNode;
use IceTea\IceDOM\SafeStringable;

abstract class Component implements SafeStringable
{
  protected ?string $id = null;

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

  public function __toString(): string
  {
    return (string) $this->render();
  }

  abstract public function render(): HtmlNode | string;
}
