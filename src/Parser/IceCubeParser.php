<?php

namespace IceTea\IceCube\Parser;

class IceCubeParser
{
  public function parse(string $className, string $componentSource, ?string $colocateJsSource = null)
  {
    $componentName = str_replace('\\', '_', $className);

    $phpCode = $this->extractPhp($componentSource);
    $styleTags = $this->extractStyleTags($componentSource);
    $jsCode = $colocateJsSource ?: $this->extractTag($componentSource, 'script');
    $digest = $colocateJsSource ? hash('xxh128', $componentSource . $colocateJsSource) : hash('xxh128', $componentSource);

    return new ParsedComponent(
      className: $className,
      componentName: $componentName,
      php: $phpCode,
      js: $jsCode,
      styles: $styleTags,
      digest: substr($digest, 0, 8),
    );
  }

  protected function extractPhp(string $content): ?string
  {
    $lastClosingTag = strrpos($content, '?>');

    if ($lastClosingTag === false) {
      return $content;
    }

    return trim(substr($content, 0, $lastClosingTag + 2));
  }

  protected function extractTag(string $content, string $tagName): ?string
  {
    $pattern = "/<{$tagName}[^>]*>(.*?)<\/{$tagName}>/is";

    if (preg_match($pattern, $content, $matches)) {
      return trim($matches[1]);
    }

    return null;
  }

  protected function extractStyleTags(string $content): array
  {
    $pattern = "/<style([^>]*)>(.*?)<\/style>/is";
    $styles = [];

    if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $attributes = trim($match[1]);
        $styleContent = trim($match[2]);

        $isGlobal = preg_match('/\bglobal\b/', $attributes) === 1;

        $styles[] = [
          'content' => $styleContent,
          'global' => $isGlobal,
        ];
      }
    }

    return $styles;
  }
}
