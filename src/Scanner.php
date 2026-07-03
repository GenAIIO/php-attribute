<?php

namespace GenAI\Attribute;

use Composer\Autoload\ClassLoader;
use GenAI\Attribute\Util\ClassFinder;

/**
 * Build-time attribute scanner driven by AttributeProcessors.
 *
 * As it scans your namespaces it recognises processors by type (any class that
 * is an AttributeProcessor) and registers them automatically — they can live in
 * any namespace, anywhere in the app or a library. Every other class is a
 * target: its attributes are dispatched to the matching processors.
 *
 *   $scanner = new Scanner($loader);
 *   $scanner->scan(['App']);                            // finds processors + targets
 *   $scanner->compile(new Context('config', 'cache'));  // processors dump
 *
 * Adding a feature = drop in an attribute class + a processor class anywhere
 * under a scanned namespace. This build script never changes.
 *
 * PHP 8 only (reflection + newInstance()); the compiled output lives entirely
 * in the registers the processors drive, which stay PHP 5.3-safe.
 */
class Scanner
{
    /**
     * Processors indexed by the attribute class they handle.
     *
     * @var array<string, AttributeProcessor[]>
     */
    private array $byAttribute = [];

    /**
     * Every registered processor, for the compile() pass.
     *
     * @var AttributeProcessor[]
     */
    private array $processors = [];

    /**
     * Class names already registered as processors, to avoid duplicates.
     *
     * @var array<string, bool>
     */
    private array $processorClasses = [];

    public function __construct(private ClassLoader $loader)
    {
    }

    /**
     * Register a processor instance explicitly. Use this only when a processor
     * needs constructor dependencies; parameterless ones are found by scan().
     *
     * @param AttributeProcessor $processor
     * @return Scanner $this, for chaining.
     */
    public function addProcessor(AttributeProcessor $processor): self
    {
        $this->byAttribute[$processor->getAttributeClass()][] = $processor;
        $this->processors[] = $processor;
        $this->processorClasses[get_class($processor)] = true;

        return $this;
    }

    /**
     * Scan the given namespaces: auto-register the processors found by type,
     * then dispatch every attribute on the remaining (target) classes — at the
     * class, property, method and method-parameter level.
     *
     * @param string[] $namespaces
     * @return void
     */
    public function scan(array $namespaces): void
    {
        $seen = [];
        foreach ($namespaces as $namespace) {
            foreach ($this->classesIn($namespace) as $class) {
                $seen[$class] = true;
            }
        }

        // Pass 1: classify every class as a processor or a target.
        $targets = [];
        foreach (array_keys($seen) as $class) {
            if (!class_exists($class) && !interface_exists($class) && !trait_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            if ($reflection->isInstantiable() && $reflection->implementsInterface(AttributeProcessor::class)) {
                $this->autoRegister($reflection);
                continue; // a processor is not itself a dispatch target
            }

            $targets[] = $reflection;
        }

        // Pass 2: dispatch attributes on the targets (all processors are known).
        foreach ($targets as $reflection) {
            $this->dispatchTarget($reflection);
        }
    }

    /**
     * Finalize: let each processor produce its compiled output.
     *
     * @param Context $context
     * @return void
     */
    public function compile(Context $context): void
    {
        foreach ($this->processors as $processor) {
            $processor->compile($context);
        }
    }

    /**
     * Auto-register a discovered processor class, unless it was already added or
     * needs constructor dependencies (then it must come via addProcessor()).
     */
    private function autoRegister(\ReflectionClass $reflection): void
    {
        if (isset($this->processorClasses[$reflection->getName()])) {
            return;
        }

        $constructor = $reflection->getConstructor();
        if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
            return;
        }

        $this->addProcessor($reflection->newInstance());
    }

    /**
     * Dispatch the attributes on a class and its members.
     */
    private function dispatchTarget(\ReflectionClass $reflection): void
    {
        $this->dispatch($reflection);

        foreach ($reflection->getProperties() as $property) {
            $this->dispatch($property);
        }

        foreach ($reflection->getMethods() as $method) {
            $this->dispatch($method);
            foreach ($method->getParameters() as $parameter) {
                $this->dispatch($parameter);
            }
        }
    }

    /**
     * Run the registered processors against one reflection target.
     */
    private function dispatch(
        \ReflectionClass|\ReflectionProperty|\ReflectionMethod|\ReflectionParameter $target
    ): void {
        foreach ($this->byAttribute as $attributeClass => $processors) {
            $attributes = $target->getAttributes($attributeClass, \ReflectionAttribute::IS_INSTANCEOF);
            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                foreach ($processors as $processor) {
                    $processor->process($instance, $target);
                }
            }
        }
    }

    /**
     * Class names under a namespace — a registered PSR-4 prefix or any
     * sub-namespace of one (e.g. "App\\Controller" under "App\\").
     *
     * @param string $namespace
     * @return string[]
     * @throws \RuntimeException If no registered PSR-4 prefix covers it.
     */
    private function classesIn(string $namespace): array
    {
        $target = rtrim($namespace, '\\') . '\\';
        $psr4 = $this->loader->getPrefixesPsr4();

        $bestPrefix = null;
        foreach ($psr4 as $prefix => $dirs) {
            if (strncmp($target, $prefix, strlen($prefix)) === 0
                && ($bestPrefix === null || strlen($prefix) > strlen($bestPrefix))) {
                $bestPrefix = $prefix;
            }
        }
        if ($bestPrefix === null) {
            throw new \RuntimeException(sprintf(
                'Namespace "%s" is not covered by any Composer PSR-4 prefix.',
                $namespace
            ));
        }

        $subPath = str_replace('\\', '/', substr($target, strlen($bestPrefix)));

        $classes = [];
        foreach ($psr4[$bestPrefix] as $baseDir) {
            $dir = rtrim($baseDir, '/\\');
            if ($subPath !== '') {
                $dir .= '/' . rtrim($subPath, '/');
            }
            foreach (ClassFinder::find($target, $dir) as $class) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
