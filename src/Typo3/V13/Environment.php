<?php

declare(strict_types=1);

namespace HeikoHardt\Behat\TYPO3Extension\Typo3\V13;

use HeikoHardt\Behat\TYPO3Extension\Helper\Filesystem;
use HeikoHardt\Behat\TYPO3Extension\Helper\Language;
use HeikoHardt\Behat\TYPO3Extension\Helper\Site;
use HeikoHardt\Behat\TYPO3Extension\Typo3\AbstractEnvironment;
use HeikoHardt\Behat\TYPO3Extension\Typo3\V13\Testbase;
use TYPO3\CMS\Core\Configuration\SiteWriter;

/**
 * Based on: https://github.com/TYPO3/testing-framework/blob/9.2.0/Classes/Core/Functional/FunctionalTestCase.php
 */
class Environment extends AbstractEnvironment
{
    public function boot()
    {
        $origInstanceDirectory = $this->getOriginRootPath();
        $testInstanceDirectory = $this->getTestingRootPath();
        $testDatabaseConfiguration = $this->getTestingDatabaseConfiguration();

        if (!defined('ORIGINAL_ROOT')) {
            define('ORIGINAL_ROOT', $origInstanceDirectory . '/public/');
        }

        putenv('TYPO3_PATH_ROOT=' . $testInstanceDirectory);
        putenv('TYPO3_PATH_APP=' . $testInstanceDirectory);

        $testbase = new Testbase();
        $testbase->setTypo3TestingContext();

        if (isset($this->configuration['setup'])) {
            $this->cleanupFilesystem($testInstanceDirectory);
            $this->cleanupDatabase($testDatabaseConfiguration);

            // Basic instance directory structure
            $testbase->createDirectory($testInstanceDirectory . '/fileadmin');
            $testbase->createDirectory($testInstanceDirectory . '/typo3temp/var/transient');
            $testbase->createDirectory($testInstanceDirectory . '/typo3temp/assets');
            $testbase->createDirectory($testInstanceDirectory . '/typo3conf/ext');

            Filesystem::setUpInstanceHtaccess($origInstanceDirectory, $testInstanceDirectory);

            $testbase->setUpInstanceCoreLinks(
                $testInstanceDirectory,
                $this->configuration['setup']['coreExtensionsToLoad']
            );

            $testbase->linkTestExtensionsToInstance(
                $testInstanceDirectory,
                $this->configuration['setup']['testExtensionsToLoad']
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

            $testbase->createDatabaseStructure($container);

            if (isset($this->configuration['fixtures'])) {
                foreach ($this->configuration['fixtures']['xmlDatabaseFixtures'] as $fixture) {
                    $testbase->importXmlDatabaseFixture($fixture);
                }
            }

            // create basic site
            $siteWriter = $container->get(SiteWriter::class);
            $siteWriter->createNewBasicSite('website-local', 1, (getenv('TYPO3_URL') ?: 'http://localhost'));
        }

        return $container;
    }
}
