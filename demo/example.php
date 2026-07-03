<?php

/**
 * Build-time: scan a namespace. The scanner finds the processor (RouteCollector)
 * by type and runs it over the #[Route] attributes automatically — no manual
 * registration, no per-attribute wiring.
 *
 *   composer install
 *   php example.php
 */

use GenAI\Attribute\Context;
use GenAI\Attribute\Scanner;

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . '/../vendor/autoload.php';
$loader->addPsr4('Demo\\Fixtures\\', __DIR__ . '/fixtures');

$scanner = new Scanner($loader);
$scanner->scan(['Demo\\Fixtures']);   // auto-detects RouteCollector + the targets
$scanner->compile(new Context(__DIR__ . '/config', __DIR__ . '/cache'));
