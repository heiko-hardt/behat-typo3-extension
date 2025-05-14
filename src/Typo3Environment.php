<?php

namespace HeikoHardt\Behat\TYPO3Extension;

use HeikoHardt\Behat\TYPO3Extension\Typo3\V06\Environment as Typo3v06Environment;
use HeikoHardt\Behat\TYPO3Extension\Typo3\V07\Environment as Typo3v07Environment;
use HeikoHardt\Behat\TYPO3Extension\Typo3\V08\Environment as Typo3v08Environment;
use HeikoHardt\Behat\TYPO3Extension\Typo3\V09\Environment as Typo3v09Environment;
use HeikoHardt\Behat\TYPO3Extension\Typo3\V10\Environment as Typo3v10Environment;
use HeikoHardt\Behat\TYPO3Extension\Typo3\V11\Environment as Typo3v11Environment;
use HeikoHardt\Behat\TYPO3Extension\Typo3\V12\Environment as Typo3v12Environment;
use HeikoHardt\Behat\TYPO3Extension\Typo3\V13\Environment as Typo3v13Environment;

class Typo3Environment
{
    public function boot(array $configuration)
    {
        if ($typo3environment = $this->getTypo3Environment()) {
            return $typo3environment->setConfiguration($configuration)->boot();
        }
        return false;
    }

    private function getTypo3Environment()
    {
        switch ($this->getVersion()) {
            case '6.2':
                return new Typo3v06Environment();
                break;
            case '7.6':
                return new Typo3v07Environment();
                break;
            case '8.7':
                return new Typo3v08Environment();
                break;
            case '9.5':
                return new Typo3v09Environment();
                break;
            case '10.4':
                return new Typo3v10Environment();
                break;
            case '11.5':
                return new Typo3v11Environment();
                break;
            case '12.4':
                return new Typo3v12Environment();
                break;
            case '13.4':
                return new Typo3v13Environment();
                break;
        }
        return false;
    }

    protected function getVersion()
    {
        if (!class_exists('\\Composer\\InstalledVersions')) {
            return getenv('TYPO3_BRANCH');
        }
        if ($version = $this->getVersionByPackageName('typo3/cms-core')) {
            return $version;
        } elseif ($version = $this->getVersionByPackageName('typo3/cms')) {
            return $version;
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
