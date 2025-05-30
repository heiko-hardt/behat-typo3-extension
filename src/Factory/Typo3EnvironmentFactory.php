<?php

namespace HeikoHardt\Behat\TYPO3Extension\Factory;

use HeikoHardt\Behat\TYPO3Extension\Typo3 as T3;

/**
 * Factory class for creating TYPO3 environment instances
 *
 * Supports TYPO3 versions from 6.2 to 13.4 and creates the appropriate environment
 * instance based on the specified version.
 */
class Typo3EnvironmentFactory
{
    /**
     * Mapping of TYPO3 versions to their environment classes
     *
     * @var array
     */
    private static $TYPO3_VERSION_ENVIRONMENTS = [
        '6.2' => T3\V06\Environment::class,
        '7.6' => T3\V07\Environment::class,
        '8.7' => T3\V08\Environment::class,
        '9.5' => T3\V09\Environment::class,
        '10.4' => T3\V10\Environment::class,
        '11.5' => T3\V11\Environment::class,
        '12.4' => T3\V12\Environment::class,
        '13.4' => T3\V13\Environment::class
    ];

    /**
     * List of supported TYPO3 versions
     *
     * @var array
     */
    private $supportedVersions;

    public function __construct()
    {
        $this->supportedVersions = array_keys(self::$TYPO3_VERSION_ENVIRONMENTS);
    }

    /**
     * Creates a TYPO3 environment instance for the specified version
     *
     * Available versions are: 6.2, 7.6, 8.7, 9.5, 10.4, 11.5, 12.4, 13.4
     *
     * @param string $version TYPO3 version string (e.g. '6.2', '7.6')
     * @return T3\AbstractEnvironment|TYPO3\CMS\Core\Tests\FunctionalTestCase Returns environment instance for the specified TYPO3 version
     * @throws \RuntimeException When the version is not supported or environment class not found
     * @see self::$TYPO3_VERSION_ENVIRONMENTS For all supported version mappings
     * @see self::getSupportedVersions() To get a list of all supported versions
     */
    public function createEnvironment($version)
    {
        if (!$this->isVersionSupported($version)) {
            throw new \RuntimeException(sprintf(
                'TYPO3 version "%s" is not supported. Supported versions are: %s',
                $version,
                implode(', ', $this->supportedVersions)
            ));
        }

        $className = self::$TYPO3_VERSION_ENVIRONMENTS[$version];

        if (!class_exists($className)) {
            throw new \RuntimeException(sprintf(
                'TYPO3 environment class "%s" not found',
                $className
            ));
        }

        return new $className;
    }

    /**
     * Checks if a specific TYPO3 version is supported
     *
     * @param string $version TYPO3 version string (e.g. '6.2', '7.6')
     * @return bool True if the version is supported, false otherwise
     */
    public function isVersionSupported($version)
    {
        return array_key_exists($version, self::$TYPO3_VERSION_ENVIRONMENTS);
    }

    /**
     * Returns array of supported TYPO3 versions
     *
     * @return array Array of supported version strings
     */
    public function getSupportedVersions()
    {
        return $this->supportedVersions;
    }
}
