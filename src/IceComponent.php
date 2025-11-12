<?php

namespace IceTea\IceCube;

use IceTea\IceDOM\HtmlNode;

abstract class IceComponent extends SingleFileComponent
{
    public function onRender($root): HtmlNode
    {
        $root = parent::onRender($root);

        $jsonProps = $root->getAttribute("data-props");
        $iceSignature = hash("xxh128", env("APP_KEY") . $jsonProps);
        $root->setAttribute("x-ice", $iceSignature);
        $root->setAttribute("x-data", "IceComponent(\$el)");

        return $root;
    }
}
