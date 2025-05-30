<?php

namespace HeikoHardt\Behat\TYPO3Extension;

use Composer\InstalledVersions;
use HeikoHardt\Behat\TYPO3Extension\Factory\Typo3EnvironmentFactory;

class Typo3Environment
{
    /**
     * @var array
     */
    private static $SUPPORTED_PACKAGES = [
        'typo3/cms-core',
        'typo3/cms'
    ];

    /**
     * @var Typo3EnvironmentFactory
     */
    private $Typo3EnvironmentFactory;

    public function __construct()
    {
        $this->Typo3EnvironmentFactory = new Typo3EnvironmentFactory();
    }

    public function boot(array $configuration)
    {
        if ($typo3environment = $this->getTypo3Environment()) {
            return $typo3environment->setConfiguration($configuration)->boot();
        }
        return false;
    }

    private function getTypo3Environment()
    {
        $version = $this->getVersion();
        return $this->Typo3EnvironmentFactory->createEnvironment($version);
    }

    protected function getVersion()
    {
        // Loop through all supported packages and try to determine the TYPO3 version
        foreach (self::$SUPPORTED_PACKAGES as $packageName) {
            $coreVersion = $this->getVersionByPackageName($packageName);
            if ($coreVersion) {
                return $coreVersion;
            }
        }

        // Return environment variable as fallback
        return $this->getVersionByEnvironmentVariable();
    }

    protected function getVersionByPackageName($packageName)
    {
        // Check if composer::installed-versions is available
        if (!class_exists(InstalledVersions::class)) {
            return null;
        }

        // Check if package is installed
        if (!InstalledVersions::isInstalled($packageName)) {
            return null;
        }

        $fullPackageVersion = InstalledVersions::getVersion($packageName);
        // Check if version is not empty
        if (empty($fullPackageVersion)) {
            return null;
        }

        // Fetch minor version from package version string (e.g. 11.5.0 -> 11.5)
        if (!preg_match('/^(\d+\.\d+)/', $fullPackageVersion, $matches)) {
            return null;
        }
        $minorVersion = $matches[1];

        // Check if version is supported
        return $this->Typo3EnvironmentFactory->isVersionSupported($minorVersion)
            ? $minorVersion
            : null;
    }

    /**
     * Gets the TYPO3 version from the environment
     *
     * This method is used as a fallback when the version cannot be determined through composer.
     * It expects the TYPO3 version to be defined in the TYPO3_BRANCH environment variable.
     *
     * @return string The TYPO3 version from the environment variable
     * @throws \RuntimeException if TYPO3_BRANCH environment variable is not set or empty
     */
    private function getVersionByEnvironmentVariable()
    {
        $version = getenv('TYPO3_BRANCH');

        if ($version === false || empty($version)) {
            throw new \RuntimeException(
                'Unable to determine the TYPO3 version. Please provide an environment variable "TYPO3_BRANCH" ' .
                'with the TYPO3 version (e.g. "11.5", "12.4", "13.4").'
            );
        }

        return $version;
    }
}
