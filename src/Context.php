<?php

namespace GenAI\Attribute;

/**
 * Build context handed to each processor's compile(): the app's config and
 * output directories, plus a flat map of config parameters (key => value).
 * Keeping these here lets processors stay parameterless (and therefore
 * auto-discoverable) while still resolving real paths and config values — e.g.
 * a #[Value('${app.name}')] baked at build time.
 *
 * Build-time only (PHP 8).
 */
class Context
{
    /**
     * @param array<string, mixed> $parameters Flat config map for ${key} lookups.
     */
    public function __construct(
        public string $configDir,
        public string $outputDir,
        public array $parameters = []
    ) {
    }

    /**
     * Absolute path to a config file under the config dir.
     *
     * @param string $file
     * @return string
     */
    public function config(string $file): string
    {
        return rtrim($this->configDir, '/\\') . '/' . $file;
    }

    /**
     * Absolute path to an output file under the output dir.
     *
     * @param string $file
     * @return string
     */
    public function output(string $file): string
    {
        return rtrim($this->outputDir, '/\\') . '/' . $file;
    }

    /**
     * @param string $key
     * @return bool Whether a config parameter exists.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * @param string $key
     * @param mixed  $default
     * @return mixed The config parameter value, or $default if absent.
     */
    public function parameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }
}

