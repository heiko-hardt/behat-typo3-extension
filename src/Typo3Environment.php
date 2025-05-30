<?php

namespace HeikoHardt\Behat\TYPO3Extension;

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
        foreach (self::$SUPPORTED_PACKAGES as $packageName) {
            $coreVersion = $this->getVersionByPackageName($packageName);
            if ($coreVersion) {
                return $coreVersion;
            }
        }

        // Return environment variable as fallback
        return $this->getVersionFromEnvironment();
    }

    protected function getVersionByPackageName($packageName)
    {
        if (!class_exists('\\Composer\\InstalledVersions')) {
            return false;
        }

        if (\Composer\InstalledVersions::isInstalled($packageName)) {
            if ($packageVersion = \Composer\InstalledVersions::getVersion($packageName)) {
                if (version_compare($packageVersion, '13.4.0', '>=') && version_compare($packageVersion, '13.4.99', '<=')) {
                    return '13.4';
                } elseif (version_compare($packageVersion, '12.4.0', '>=') && version_compare($packageVersion, '12.4.99', '<=')) {
                    return '12.4';
                } elseif (version_compare($packageVersion, '11.5.0', '>=') && version_compare($packageVersion, '11.5.99', '<=')) {
                    return '11.5';
                } elseif (version_compare($packageVersion, '10.4.0', '>=') && version_compare($packageVersion, '10.4.99', '<=')) {
                    return '10.4';
                } elseif (version_compare($packageVersion, '9.5.0', '>=') && version_compare($packageVersion, '9.5.99', '<=')) {
                    return '9.5';
                } elseif (version_compare($packageVersion, '8.7.0', '>=') && version_compare($packageVersion, '8.7.99', '<=')) {
                    return '8.7';
                } elseif (version_compare($packageVersion, '7.6.0', '>=') && version_compare($packageVersion, '7.6.99', '<=')) {
                    return '7.6';
                }
            }
        }
        return false;
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
    private function getVersionFromEnvironment()
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
