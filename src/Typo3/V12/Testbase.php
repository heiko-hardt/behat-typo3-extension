<?php

declare(strict_types=1);

namespace HeikoHardt\Behat\TYPO3Extension\Typo3\V12;

use Doctrine\DBAL\Platforms\SQLServerPlatform;
use HeikoHardt\Behat\TYPO3Extension\Typo3\AbstractTestbase;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Testbase extends AbstractTestbase
{
    /**
     * Imports a data set represented as XML into the test database,
     *
     * @param string $path Absolute path to the XML file containing the data set to load
     * @param non-empty-string $path Absolute path to the XML file containing the data set to load
     * @throws \Doctrine\DBAL\DBALException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @deprecated Will be removed with core v12 compatible testing-framework.
     *             Importing database fixtures based on XML format is discouraged. Switch to CSV format
     *             instead. See core functional tests or styleguide for many examples how these look like.
     */
    public function importXmlDatabaseFixture($path): void
    {
        $path = strpos($path, 'EXT:') === 0
            ? GeneralUtility::getFileAbsFileName($path)
            : $path;
        if (!is_file($path)) {
            throw new \RuntimeException(
                'Fixture file ' . $path . ' not found',
                1376746261
            );
        }

        $fileContent = file_get_contents($path);
        $previousValueOfEntityLoader = false;
        if (PHP_MAJOR_VERSION < 8) {
            // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
            $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        }
        $xml = simplexml_load_string($fileContent);
        if (PHP_MAJOR_VERSION < 8) {
            libxml_disable_entity_loader($previousValueOfEntityLoader);
        }
        $foreignKeys = [];

        /** @var \SimpleXMLElement $table */
        foreach ($xml->children() as $table) {
            $insertArray = [];

            /** @var \SimpleXMLElement $column */
            foreach ($table->children() as $column) {
                $columnName = $column->getName();
                $columnValue = null;

                if (isset($column['ref'])) {
                    [$tableName, $elementId] = explode('#', $column['ref']);
                    $columnValue = $foreignKeys[$tableName][$elementId];
                } elseif (isset($column['is-NULL']) && ($column['is-NULL'] === 'yes')) {
                    $columnValue = null;
                } else {
                    $columnValue = (string)$table->$columnName;
                }

                $insertArray[$columnName] = $columnValue;
            }

            $tableName = $table->getName();
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);

            // With mssql, hard setting uid auto-increment primary keys is only allowed if
            // the table is prepared for such an operation beforehand
            $platform = $connection->getDatabasePlatform();
            $sqlServerIdentityDisabled = false;
            if ($platform instanceof SQLServerPlatform) {
                try {
                    $connection->exec('SET IDENTITY_INSERT ' . $tableName . ' ON');
                    $sqlServerIdentityDisabled = true;
                } catch (\Doctrine\DBAL\DBALException $e) {
                    // Some tables like sys_refindex don't have an auto-increment uid field and thus no
                    // IDENTITY column. Instead of testing existance, we just try to set IDENTITY ON
                    // and catch the possible error that occurs.
                }
            }

            // Some DBMS like mssql are picky about inserting blob types with correct cast, setting
            // types correctly (like Connection::PARAM_LOB) allows doctrine to create valid SQL
            $types = [];
            $tableDetails = $connection->getSchemaManager()->listTableDetails($tableName);
            foreach ($insertArray as $columnName => $columnValue) {
                $types[] = $tableDetails->getColumn($columnName)->getType()->getBindingType();
            }

            // Insert the row
            $connection->insert($tableName, $insertArray, $types);

            if ($sqlServerIdentityDisabled) {
                // Reset identity if it has been changed
                $connection->exec('SET IDENTITY_INSERT ' . $tableName . ' OFF');
            }

            static::resetTableSequences($connection, $tableName);

            if (isset($table['id'])) {
                $elementId = (string)$table['id'];
                $foreignKeys[$tableName][$elementId] = $connection->lastInsertId($tableName);
            }
        }
    }

    public function createSiteConfiguration(
        ContainerInterface $container,
        ?array $siteConfiguration = null,
        ?array $siteConfigurationOverwrite = null
    ): void {

        /** @var SiteConfiguration $configurationService */
        $configurationService = $container->get(SiteConfiguration::class);
        if ($siteConfiguration) {
            $configurationService->write('website-local', $siteConfiguration);
        } else {
            $configurationService->createNewBasicSite('website-local', 1, getenv('TYPO3_URL') ?: 'http://localhost');
        }
        if ($siteConfigurationOverwrite) {
            $site = $configurationService->load('website-local');
            $site = array_merge_recursive($site, $siteConfigurationOverwrite);
            $configurationService->write('website-local', $site);
        }
    }
}
