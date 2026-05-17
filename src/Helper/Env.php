<?php

declare(strict_types=1);

namespace HeikoHardt\Behat\TYPO3Extension\Helper;

use RuntimeException;

class Env
{
    /**
     * Resolves environment variable placeholders with optional default values.
     *
     * Syntax:
     * - Basic: %env(VARIABLE_NAME)%
     * - With default: %env(VARIABLE_NAME):-default_value%
     * - With suffix: %env(VARIABLE_NAME)%/path/suffix
     * - Combined: %env(VARIABLE_NAME):-https://localhost%/api
     *
     * @param string $string The string containing placeholders
     * @param bool $throwOnMissing Throw exception if env var not found and no default
     * @return string The resolved string
     * @throws RuntimeException If env var not found, no default, and $throwOnMissing is true
     */
    public static function resolve(
        string $string,
        bool   $throwOnMissing = false
    ): string {
        return preg_replace_callback(
            '/%env\(([A-Z0-9_]+)\)(?::-([^%]*))?%(\/[^%\s]*)?/i',
            fn(array $matches) => self::replacePlaceholder($matches, $throwOnMissing), $string);
    }

    /**
     * Replaces a single placeholder with its resolved value.
     *
     * @param array $matches Regex matches: [0] = full match, [1] = var name, [2] = default, [3] = suffix
     * @param bool $throwOnMissing Whether to throw exception if variable not found
     * @return string The resolved value
     * @throws RuntimeException If variable not found and throwOnMissing is true
     */
    private static function replacePlaceholder(array $matches, bool $throwOnMissing): string
    {
        $varName = $matches[1];
        $defaultValue = $matches[2] ?? null;
        $suffix = $matches[3] ?? '';

        $value = self::getEnvironmentValue($varName);

        if ($value === null || $value === '') {
            $value = self::handleMissingValue($varName, $defaultValue, $matches[0], $throwOnMissing);
        }

        return self::applySuffix($value, $suffix);
    }

    /**
     * Retrieves the environment variable value from different sources.
     *
     * @param string $varName The variable name
     * @return string|null The value or null if not found
     */
    private static function getEnvironmentValue(string $varName): ?string
    {
        // Try getenv first
        $envValue = getenv($varName);
        if ($envValue !== false) {
            return $envValue;
        }

        // Fall back to $_ENV
        if (isset($_ENV[$varName])) {
            return $_ENV[$varName];
        }

        // Fall back to $_SERVER
        if (isset($_SERVER[$varName])) {
            return $_SERVER[$varName];
        }

        return null;
    }

    /**
     * Handles the case when an environment variable is not set or empty.
     *
     * @param string $varName The variable name
     * @param string|null $defaultValue The default value if provided
     * @param string $originalMatch The original placeholder string
     * @param bool $throwOnMissing Whether to throw exception
     * @return string The default value or original placeholder
     * @throws RuntimeException If throwOnMissing is true and no default provided
     */
    private static function handleMissingValue(
        string $varName,
        ?string $defaultValue,
        string $originalMatch,
        bool $throwOnMissing
    ): string {
        if ($defaultValue !== null) {
            return $defaultValue;
        }

        if ($throwOnMissing) {
            throw new RuntimeException(
                sprintf('Environment variable "%s" is not set and no default provided.', $varName)
            );
        }

        // Return original placeholder unchanged
        return $originalMatch;
    }

    /**
     * Appends a suffix to the value, removing trailing slashes if needed.
     *
     * @param string $value The base value
     * @param string $suffix The suffix to append
     * @return string The value with suffix applied
     */
    private static function applySuffix(string $value, string $suffix): string
    {
        if ($suffix === '') {
            return $value;
        }

        return rtrim($value, '/') . $suffix;
    }
}
