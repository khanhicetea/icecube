<?php

namespace IceTea\IceCube;

use IceTea\IceDOM\HtmlNode;
use IceTea\IceDOM\SafeStringable;
use ReflectionClass;
use ReflectionProperty;

abstract class SingleFileComponent extends Component implements SafeStringable
{
  abstract public function render(): HtmlNode;

  public function __toString(): string
  {
    $root = $this->render();

    $className = get_class($this);
    $componentName = str_replace('\\', '_', $className);

    $reflection = new ReflectionClass($this);
    $publicProps = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
    $dataProps = [];
    foreach ($publicProps as $prop) {
      $dataProps[$prop->getName()] = $prop->getValue($this);
    }

    $root->id($root->getAttribute('id') ?? $this->getId());
    $root->setAttribute('data-icecube', $componentName);
    $root->setAttribute('data-props', json_encode($dataProps));

    return (string) $root;
  }
}
