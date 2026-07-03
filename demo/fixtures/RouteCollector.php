<?php

namespace Demo\Fixtures;

use GenAI\Attribute\AttributeProcessor;
use GenAI\Attribute\Context;

/**
 * A demo processor: it handles #[Route] and just collects what it finds. A real
 * one would feed a RouterRegister and dump it in compile().
 */
class RouteCollector implements AttributeProcessor
{
    /** @var array<int, array{0:string,1:string,2:string}> */
    public array $routes = [];

    public function getAttributeClass(): string
    {
        return Route::class;
    }

    public function process(object $attribute, \Reflector $target): void
    {
        /** @var \ReflectionMethod $target */
        $this->routes[] = [
            $attribute->method,
            $attribute->path,
            $target->getDeclaringClass()->getShortName() . '@' . $target->getName(),
        ];
    }

    public function compile(Context $context): void
    {
        // A real processor would dump a file here (see example-app). This demo
        // just prints what it collected.
        foreach ($this->routes as [$method, $path, $handler]) {
            printf("%-4s %-16s -> %s\n", $method, $path, $handler);
        }
    }
}
