<?php

declare(strict_types=1);

namespace HeikoHardt\Behat\TYPO3Extension\Typo3\V10;

use HeikoHardt\Behat\TYPO3Extension\Helper\Language;
use HeikoHardt\Behat\TYPO3Extension\Helper\Site;
use HeikoHardt\Behat\TYPO3Extension\Typo3\AbstractEnvironment;
use HeikoHardt\Behat\TYPO3Extension\Typo3\V10\Testbase;

class Environment extends AbstractEnvironment
{
    public function boot(array $configuration = [])
    {
        $origInstanceDirectory = $this->getOriginRootPath();
        $testInstanceDirectory = $this->getTestingRootPath();
        $testDatabaseConfiguration = $this->getTestingDatabaseConfiguration();

        if (!defined('ORIGINAL_ROOT')) {
            define('ORIGINAL_ROOT', $origInstanceDirectory . '/public/');
        }

        putenv('TYPO3_PATH_ROOT=' . $testInstanceDirectory);
        putenv('TYPO3_PATH_APP=' . $testInstanceDirectory);
        putenv('TYPO3_PATH_WEB=' . $testInstanceDirectory);

        $testbase = new Testbase();
        $testbase->defineOriginalRootPath();
        $testbase->defineTypo3ModeBe();
        $testbase->setTypo3TestingContext();

        if (isset($this->configuration['setup'])) {
            $this->cleanupFilesystem($testInstanceDirectory);
            $this->cleanupDatabase($testDatabaseConfiguration);
            $this->prepareFilesystem(
                $origInstanceDirectory,
                $testInstanceDirectory,
                $this->configuration['setup']['testExtensionsToLoad'] ?? null
            );

            $testbase->setUpLocalConfiguration(
                $testInstanceDirectory,
                $this->getLocalConfiguration($testDatabaseConfiguration),
                $this->configuration['setup']['configurationToUseInTestInstance'] ?? []
            );

            $testbase->setUpPackageStates(
                $testInstanceDirectory,
                $this->configuration['setup']['coreExtensionsToLoad'] ?? [],
                [],
                $this->configuration['setup']['testExtensionsToLoad'] ?? [],
                []
            );
        }

        $container = $testbase->setUpBasicTypo3Bootstrap($testInstanceDirectory);
        $testbase->loadExtensionTables();

        if (isset($this->configuration['setup'])) {
            $testbase->createDatabaseStructure();
            if (isset($this->configuration['fixtures'])) {
                foreach ($this->configuration['fixtures']['xmlDatabaseFixtures'] as $fixture) {
                    $testbase->importXmlDatabaseFixture($fixture);
                }
            }

            $languages = [
                Language::buildDefaultLanguageConfiguration('EN', '/en/'),
                Language::buildLanguageConfiguration('DE', '/de/'),
            ];

            Site::createSite(
                $testInstanceDirectory,
                'website-local',
                1,
                getenv('TYPO3_URL') ?: 'http://localhost',
                $languages
            );
        }
        return $container;
    }
}
