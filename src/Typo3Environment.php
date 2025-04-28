<?php

declare(strict_types=1);

namespace HeikoHardt\Behat\TYPO3Extension;

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
        if (getenv('TYPO3_BRANCH') === '10.4') {
            return new Typo3v10Environment();
        } elseif (getenv('TYPO3_BRANCH') === '11.5') {
            return new Typo3v11Environment();
        } elseif (getenv('TYPO3_BRANCH') === '12.4') {
            return new Typo3v12Environment();
        } elseif (getenv('TYPO3_BRANCH') === '13.4') {
            return new Typo3v13Environment();
        }
        return false;
    }
}
