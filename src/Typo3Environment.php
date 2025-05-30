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
        if (!class_exists('\\Composer\\InstalledVersions')) {
            return getenv('TYPO3_BRANCH');
        }

        foreach (self::$SUPPORTED_PACKAGES as $packageName) {
            $coreVersion = $this->getVersionByPackageName($packageName);
            if ($coreVersion) {
                return $coreVersion;
            }
        }

        // Return environment variable as fallback
        return getenv('TYPO3_BRANCH');
    }

    protected function getVersionByPackageName($packageName)
    {
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
}
