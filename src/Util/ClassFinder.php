<?php

namespace GenAI\Attribute\Util;

/**
 * Finds the fully-qualified class names under a PSR-4 prefix + base directory by
 * walking the filesystem. Build-time only (PHP 8).
 */
class ClassFinder
{
    /**
     * @param string $prefix  PSR-4 namespace prefix, ending in a backslash.
     * @param string $baseDir Directory that maps to that prefix.
     * @return string[] Candidate class names (one per .php file).
     */
    public static function find(string $prefix, string $baseDir): array
    {
        $baseDir = rtrim($baseDir, '/\\');
        if (!is_dir($baseDir)) {
            return [];
        }

        $classes = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($baseDir) + 1);
            $relative = preg_replace('/\.php$/i', '', $relative);
            $classes[] = $prefix . str_replace(['/', '\\'], '\\', $relative);
        }

        return $classes;
    }
}
