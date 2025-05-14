<?php

declare(strict_types=1);

namespace HeikoHardt\Behat\TYPO3Extension\Typo3\V10;

use HeikoHardt\Behat\TYPO3Extension\Helper\Database;
use HeikoHardt\Behat\TYPO3Extension\Helper\Filesystem;
use HeikoHardt\Behat\TYPO3Extension\Typo3\AbstractEnvironment;
use HeikoHardt\Behat\TYPO3Extension\Typo3\V10\Testbase;

/**
 * Based on: https://github.com/TYPO3/testing-framework/blob/6.16.10/Classes/Core/Functional/FunctionalTestCase.php
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
        $testbase->defineTypo3ModeBe();
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

            $defaultCoreExtensionsToLoad = [
                'core',
                'backend',
                'frontend',
                'extbase',
                'install',
                'recordlist',
                'fluid',
            ];

            $frameworkExtension = [
                // 'Resources/Core/Functional/Extensions/json_response',
                // 'Resources/Core/Functional/Extensions/private_container',
            ];

            $testbase->setUpInstanceCoreLinks(
                $testInstanceDirectory
            );

            $testbase->linkTestExtensionsToInstance(
                $testInstanceDirectory,
                $this->configuration['setup']['testExtensionsToLoad'] ?? []
            );

            $testbase->linkFrameworkExtensionsToInstance(
                $testInstanceDirectory,
                $frameworkExtension
            );

            $testbase->setUpLocalConfiguration(
                $testInstanceDirectory,
                $this->getLocalConfiguration($testDatabaseConfiguration),
                $this->configuration['setup']['localConfigurationOverride'] ?? []
            );

            $testbase->setUpPackageStates(
                $testInstanceDirectory,
                $defaultCoreExtensionsToLoad,
                $this->configuration['setup']['coreExtensionsToLoad'] ?? [],
                $this->configuration['setup']['testExtensionsToLoad'] ?? [],
                $frameworkExtension
            );
        }

        $container = $testbase->setUpBasicTypo3Bootstrap($testInstanceDirectory);
        $testbase->loadExtensionTables();

        if (isset($this->configuration['setup'])) {
            $testbase->createDatabaseStructure($container);
            $testbase->createSiteConfiguration(
                $container,
                ($this->configuration['setup']['siteConfiguration'] ?? null),
                ($this->configuration['setup']['siteConfigurationOverride'] ?? null)
            );
            if (isset($this->configuration['fixtures'])) {
                foreach ($this->configuration['fixtures']['xmlDatabaseFixtures'] as $fixture) {
                    $testbase->importXmlDatabaseFixture($fixture);
                }
            }
        }

        return $container;
    }

    protected function getLocalConfiguration(
        array $testDatabaseConfiguration
    ): array {
        $localConfiguration['DB'] = Database::getLocalConfiguration(
            'mysqli',
            $testDatabaseConfiguration['host'],
            $testDatabaseConfiguration['port'],
            $testDatabaseConfiguration['database'],
            $testDatabaseConfiguration['user'],
            $testDatabaseConfiguration['password']
        );

        $localConfiguration['DB']['Connections']['Default']['charset'] = 'utf8mb4';
        $localConfiguration['DB']['Connections']['Default']['tableoptions']['charset'] = 'utf8mb4';
        $localConfiguration['DB']['Connections']['Default']['tableoptions']['collate'] = 'utf8mb4_unicode_ci';
        $localConfiguration['DB']['Connections']['Default']['initCommands'] = 'SET SESSION sql_mode = \'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_VALUE_ON_ZERO,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,NO_ZERO_IN_DATE,ONLY_FULL_GROUP_BY\';';
        $localConfiguration['SYS']['displayErrors'] = '1';
        $localConfiguration['SYS']['debugExceptionHandler'] = '';
        $localConfiguration['SYS']['trustedHostsPattern'] = '.*';
        $localConfiguration['SYS']['encryptionKey'] = 'i-am-not-a-secure-encryption-key';
        $localConfiguration['GFX']['processor'] = 'GraphicsMagick';

        return $localConfiguration;
    }
}
