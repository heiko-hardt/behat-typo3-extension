<?php

declare(strict_types=1);

namespace HeikoHardt\Behat\TYPO3Extension\Typo3;

use HeikoHardt\Behat\TYPO3Extension\Helper\Database;
use HeikoHardt\Behat\TYPO3Extension\Helper\Filesystem;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Information\Typo3Version;

abstract class AbstractEnvironment
{
    /**
     * @var array
     */
    protected $configuration;

    public function __construct(
        array $configuration = []
    ) {
        $this->configuration = $configuration;
    }

    /**
     * @param array|null $configuration
     * @return self
     */
    public function setConfiguration(
        array $configuration = null
    ): self {
        $this->configuration = $configuration;
        return $this;
    }

    public function boot()
    {

    }

    protected function cleanupFilesystem($testInstanceDirectory)
    {
        Filesystem::clearDirectory($testInstanceDirectory);
    }

    protected function cleanupDatabase(
        array $testDatabaseConfiguration
    ) {
        Database::cleanup(
            $testDatabaseConfiguration['host'],
            $testDatabaseConfiguration['port'],
            $testDatabaseConfiguration['database'],
            $testDatabaseConfiguration['user'],
            $testDatabaseConfiguration['password']
        );
    }

    protected function prepareFilesystem(
        $origInstanceDirectory,
        $testInstanceDirectory,
        $testExtensionsToLoad
    ) {
        // Basic instance directory structure
        Filesystem::createDirectory($testInstanceDirectory . '/fileadmin');
        Filesystem::createDirectory($testInstanceDirectory . '/typo3temp/var/transient');
        Filesystem::createDirectory($testInstanceDirectory . '/typo3temp/assets');
        Filesystem::createDirectory($testInstanceDirectory . '/typo3conf/ext');
        Filesystem::setUpInstanceCoreLinks($origInstanceDirectory, $testInstanceDirectory);
        Filesystem::setUpInstanceHtaccess($origInstanceDirectory, $testInstanceDirectory);
        Filesystem::linkTestExtensionsToInstance($origInstanceDirectory, $testInstanceDirectory, $testExtensionsToLoad);
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

        $localConfiguration = array_merge_recursive(
            $localConfiguration,
            [
                'DB' => [
                    'Connections' => [
                        'Default' => [
                            //'wrapperClass' => DatabaseConnectionWrapper::class,
                            'charset' => 'utf8mb4',
                            'tableoptions' => [
                                'charset' => 'utf8mb4',
                                'collate' => 'utf8mb4_unicode_ci',
                            ],
                            'initCommands' => 'SET SESSION sql_mode = \'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_VALUE_ON_ZERO,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,NO_ZERO_IN_DATE,ONLY_FULL_GROUP_BY\';',
                        ],
                    ],
                ],
            ]
        );
        $localConfiguration['SYS']['displayErrors'] = '1';
        $localConfiguration['SYS']['debugExceptionHandler'] = '';

        if ((new Typo3Version())->getMajorVersion() >= 11
            && defined('TYPO3_TESTING_FUNCTIONAL_REMOVE_ERROR_HANDLER')
        ) {
            $localConfiguration['SYS']['errorHandler'] = '';
        }

        $localConfiguration['SYS']['trustedHostsPattern'] = '.*';
        $localConfiguration['SYS']['encryptionKey'] = 'i-am-not-a-secure-encryption-key';
        $localConfiguration['GFX']['processor'] = 'GraphicsMagick';

        $localConfiguration['SYS']['caching']['cacheConfigurations']['extbase_object']['backend'] = NullBackend::class;
        $localConfiguration['SYS']['caching']['cacheConfigurations']['hash']['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';
        $localConfiguration['SYS']['caching']['cacheConfigurations']['imagesizes']['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';
        $localConfiguration['SYS']['caching']['cacheConfigurations']['pages']['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';
        $localConfiguration['SYS']['caching']['cacheConfigurations']['rootline']['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';

        return $localConfiguration;
    }

    protected function getOriginRootPath(): string
    {
        return Filesystem::checkDirectoryPath(
            $this->getEnvironmentVariable('ORIGIN_PATH_ROOT')
        );
    }

    protected function getTestingRootPath(): string
    {
        return Filesystem::checkDirectoryPath(
            $this->getEnvironmentVariable('TESTING_PATH_ROOT')
        );
    }

    protected function getTestingDatabaseConfiguration(): array
    {
        return [
            'host' => $this->getEnvironmentVariable('TESTING_DB_HOST'),
            'port' => (int)$this->getEnvironmentVariable('TESTING_DB_PORT'),
            'database' => $this->getEnvironmentVariable('TESTING_DB_NAME'),
            'user' => $this->getEnvironmentVariable('TESTING_DB_USER'),
            'password' => $this->getEnvironmentVariable('TESTING_DB_PASSWORD'),
        ];
    }

    /**
     * @param string $variableName
     * @return string
     */
    protected function getEnvironmentVariable(
        string $variableName
    ): string {
        if (!($environmentVariable = getenv($variableName))) {
            throw new \UnexpectedValueException('Missing environment variable: ' . $variableName);
        }

        return $environmentVariable;
    }
}
