<?php

namespace IceTea\IceCube\Laravel;

use IceTea\IceCube\IceComponent;
use Illuminate\Http\Request;
use Stringable;
use function \is_string;

class IceCubeController
{
    public function handleComponent(Request $request)
    {
        $payload = $request->input();
        $id = $payload["id"];
        $component = $payload["component"];
        $snapshot = $payload["snapshot"];
        $method = $payload["method"];
        $args = $payload["args"];
        $data = $payload["data"];
        $signature = $request->header("X-Ice");

        $componentClass = str_replace("_", "\\", $component);
        $reflectionClass = new \ReflectionClass($componentClass);
        if (
            !$reflectionClass->isSubclassOf(IceComponent::class) ||
            hash("xxh128", env("APP_KEY") . $snapshot) !== $signature
        ) {
            return response("Bad request", 400);
        }

        // Handle the component request
        $props = json_decode($snapshot, true);
        $c = app()->make($componentClass, $props);
        $c->setId($id);

        foreach ($data as $k => $v) {
            if (array_key_exists($k, $props)) {
                $c->$k = $v;
            }
        }

        if ($method) {
            $reflectionMethod = $reflectionClass->getMethod($method);
            $methodResult = $reflectionMethod->invokeArgs($c, $args);

            if (
                is_a($methodResult, Stringable::class) ||
                is_string($methodResult)
            ) {
                return response()->json([
                    "data" => [],
                    "html" => (string) $methodResult,
                ]);
            }
        }

        $data = $c->getPublicProps();
        // $html = (string) $c;

        return response()->json([
            "html" => "",
            "data" => $data,
        ]);
    }
}
