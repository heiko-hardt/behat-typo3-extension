<?php

declare(strict_types=1);

namespace HeikoHardt\Behat\TYPO3Extension\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\MySqlSchemaManager;

class Database
{
    public static function cleanup(
        string $host,
        int $port,
        string $database,
        string $user,
        string $password
    ) {
        /** @var Connection $connection */
        $connection = self::getConnection('mysqli', $host, $port, $database, $user, $password);
        if (version_compare(\Composer\InstalledVersions::getPrettyVersion('doctrine/dbal'), '3.0.0', '<')) {
            /** @var MySqlSchemaManager $schemaManager */
            $schemaManager = $connection->getSchemaManager();
            // 10:
        } else {
            /** @var MySqlSchemaManager $schemaManager */
            $schemaManager = $connection->createSchemaManager();
        }
        // Fetch tables in database
        $tableNames = $schemaManager->listTableNames();
        // Drop all tables in database
        $connection->prepare('SET FOREIGN_KEY_CHECKS = 0;')->executeQuery();
        foreach ($tableNames as $tableName) {
            $connection->prepare('DROP TABLE ' . $tableName)->executeQuery();
        }
        $connection->prepare('SET FOREIGN_KEY_CHECKS = 1;')->executeQuery();
    }

    public static function getConnection(
        string $driver,
        string $host,
        int $port,
        string $database,
        string $user,
        string $password
    ): Connection {

        /** @var Connection $connection */
        $connection = DriverManager::getConnection([
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'dbname' => $database,
            'user' => $user,
            'password' => $password,
        ]);

        // Disable logger
        $configuration = $connection->getConfiguration();
        if (method_exists($configuration, 'setSQLLogger')) {
            $configuration->setSQLLogger(null);
        }
        return $connection;
    }

    public static function getLocalConfiguration(
        string $driver,
        string $host,
        int $port,
        string $database,
        string $user,
        string $password
    ): array {
        $originalConfigurationArray = [
            'DB' => [
                'Connections' => [
                    'Default' => [],
                ],
            ],
        ];
        if ($driver) {
            $originalConfigurationArray['DB']['Connections']['Default']['driver'] = $driver;
        }
        if ($database) {
            $originalConfigurationArray['DB']['Connections']['Default']['dbname'] = $database;
        }
        if ($host) {
            $originalConfigurationArray['DB']['Connections']['Default']['host'] = $host;
        }
        if ($user) {
            $originalConfigurationArray['DB']['Connections']['Default']['user'] = $user;
        }
        if ($password !== false) {
            $originalConfigurationArray['DB']['Connections']['Default']['password'] = $password;
        }
        if ($port) {
            $originalConfigurationArray['DB']['Connections']['Default']['port'] = $port;
        }

        return $originalConfigurationArray['DB'];
    }
}
