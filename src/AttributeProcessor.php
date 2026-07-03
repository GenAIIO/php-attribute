<?php

namespace GenAI\Attribute;

/**
 * A processor handles one attribute type. To add an attribute-driven feature a
 * developer writes two classes — the attribute, and a processor that declares
 * it via getAttributeClass() — and the Scanner runs the processor automatically
 * for every matching attribute it finds. No manual on()/registration of logic.
 *
 *   class RouteProcessor implements AttributeProcessor {
 *       public function getAttributeClass(): string { return Route::class; }
 *       public function process(object $attribute, \Reflector $target): void {
 *           // ...collect into a register...
 *       }
 *       public function compile(Context $context): void {
 *           // ...dump the register to $context->output('routes.php')...
 *       }
 *   }
 *
 * Keep the constructor parameterless so the Scanner can auto-discover and
 * instantiate it; take what you need (paths) from the Context at compile().
 *
 * Build-time only (PHP 8).
 */
interface AttributeProcessor
{
    /**
     * The attribute class this processor handles (subclasses match too).
     *
     * @return string
     */
    public function getAttributeClass(): string;

    /**
     * Called once per matching attribute occurrence.
     *
     * @param object     $attribute The instantiated attribute.
     * @param \Reflector $target    Where it was found (ReflectionClass/Method/
     *                              Property/Parameter).
     * @return void
     */
    public function process(object $attribute, \Reflector $target): void;

    /**
     * Called once after scanning, to finalize — typically dump a compiled file.
     *
     * @param Context $context Build paths (config dir, output dir).
     * @return void
     */
    public function compile(Context $context): void;
}
