<?php

namespace Demo\Fixtures;

/**
 * A sample custom attribute for the demo. Placed on controller methods.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Route
{
    public function __construct(
        public string $method,
        public string $path
    ) {
    }
}
