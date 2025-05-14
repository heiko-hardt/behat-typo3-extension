<?php

namespace HeikoHardt\Behat\TYPO3Extension\Typo3\V08;

use HeikoHardt\Behat\TYPO3Extension\Helper\Database;
use HeikoHardt\Behat\TYPO3Extension\Helper\Filesystem;
use HeikoHardt\Behat\TYPO3Extension\Typo3\AbstractEnvironment;
use HeikoHardt\Behat\TYPO3Extension\Typo3\V08\Testbase;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Core\Bootstrap;

/**
 * Based on: https://github.com/TYPO3/testing-framework/blob/1.3.2/Classes/Core/Functional/FunctionalTestCase.php
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
        $testbase->defineBaseConstants();
        $testbase->defineTypo3ModeBe();
        $testbase->definePackagesPath();
        $testbase->setTypo3TestingContext();

        if (isset($this->configuration['setup'])) {
            $this->cleanupFilesystem($testInstanceDirectory);
            $this->cleanupDatabase($testDatabaseConfiguration);

            // Basic instance directory structure
            $testbase->createDirectory($testInstanceDirectory . '/fileadmin');
            $testbase->createDirectory($testInstanceDirectory . '/typo3temp/var/transient');
            $testbase->createDirectory($testInstanceDirectory . '/typo3temp/assets');
            $testbase->createDirectory($testInstanceDirectory . '/typo3conf/ext');
            $testbase->createDirectory($testInstanceDirectory . '/uploads');

            Filesystem::setUpInstanceHtaccess($origInstanceDirectory, $testInstanceDirectory);

            $defaultCoreExtensionsToLoad = [
                'core',
                'backend',
                'frontend',
                'lang',
                'extbase',
                'install',
            ];

            $testbase->setUpInstanceCoreLinks(
                $testInstanceDirectory
            );

            $testbase->linkTestExtensionsToInstance(
                $testInstanceDirectory,
                $this->configuration['setup']['testExtensionsToLoad'] ?? []
            );

            $testbase->setUpLocalConfiguration(
                $testInstanceDirectory,
                $this->getLocalConfiguration($testDatabaseConfiguration),
                $this->configuration['setup']['localConfigurationOverwrite'] ?? []
            );

            $testbase->setUpPackageStates(
                $testInstanceDirectory,
                $defaultCoreExtensionsToLoad,
                $this->configuration['setup']['coreExtensionsToLoad'] ?? [],
                $this->configuration['setup']['testExtensionsToLoad'] ?? []
            );
        }

        $testbase->setUpBasicTypo3Bootstrap($testInstanceDirectory);
        Bootstrap::getInstance()->initializeBackendRouter();
        $testbase->loadExtensionTables();

        if (isset($this->configuration['setup'])) {
            $testbase->createDatabaseStructure();
            if (isset($this->configuration['fixtures'])) {
                foreach ($this->configuration['fixtures']['xmlDatabaseFixtures'] as $fixture) {
                    $testbase->importXmlDatabaseFixture($fixture);
                }
            }
        }
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

        $localConfiguration['SYS']['isInitialInstallationInProgress'] = false;
        $localConfiguration['SYS']['isInitialDatabaseImportDone'] = true;
        $localConfiguration['SYS']['displayErrors'] = '1';
        $localConfiguration['SYS']['debugExceptionHandler'] = '';
        $localConfiguration['SYS']['trustedHostsPattern'] = '.*';
        $localConfiguration['SYS']['encryptionKey'] = 'i-am-not-a-secure-encryption-key';
        $localConfiguration['SYS']['setDBinit'] = 'SET SESSION sql_mode = \'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_VALUE_ON_ZERO,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,NO_ZERO_IN_DATE,ONLY_FULL_GROUP_BY\';';
        $localConfiguration['SYS']['caching']['cacheConfigurations']['extbase_object']['backend'] = NullBackend::class;

        // Additionals by h.h:
        $localConfiguration['BE']['loginSecurityLevel'] = 'normal';
        $localConfiguration['FE']['loginSecurityLevel'] = 'normal';

        return $localConfiguration;
    }
}
