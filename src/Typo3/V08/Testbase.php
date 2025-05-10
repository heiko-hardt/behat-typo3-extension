<?php

namespace HeikoHardt\Behat\TYPO3Extension\Typo3\V08;

use HeikoHardt\Behat\TYPO3Extension\Typo3\AbstractTestbase;
use TYPO3\CMS\Core\Core\Bootstrap;

class Testbase extends AbstractTestbase
{
    public function setUpInstanceCoreLinks($instancePath)
    {
        /* original ######################################################
        $linksToSet = [
            '../../../../' => $instancePath . '/typo3_src',
            'typo3_src/typo3' => $instancePath . '/typo3',
            'typo3_src/index.php' => $instancePath . '/index.php',
        ];
        // ############################################################ */
        $linksToSet = [
            getenv('TYPO3_PATH_WEB') => $instancePath . '/typo3_src',
            'typo3_src/typo3' => $instancePath . '/typo3',
            'typo3_src/index.php' => $instancePath . '/index.php',
        ];
        foreach ($linksToSet as $from => $to) {
            $success = symlink($from, $to);
            if (!$success) {
                throw new \Exception(
                    'Creating link failed: from ' . $from . ' to: ' . $to,
                    1376657199
                );
            }
        }
    }

    public function linkTestExtensionsToInstance($instancePath, array $extensionPaths): void
    {
        foreach ($extensionPaths as $key => $extensionPath) {
            $extensionPaths[$key] = 'typo3conf/ext/' . $extensionPath;
        }
        parent::linkTestExtensionsToInstance($instancePath, $extensionPaths);
    }

    public function setUpBasicTypo3Bootstrap($instancePath)
    {
        $_SERVER['PWD'] = $instancePath;
        $_SERVER['argv'][0] = 'index.php';

        /* original ######################################################
        $classLoader = require rtrim(realpath($instancePath . '/typo3'), '\\/') . '/../vendor/autoload.php';
        // ############################################################ */
        $autoload = isset($GLOBALS['_composer_autoload_path'])
            ? $GLOBALS['_composer_autoload_path']
            : rtrim(realpath($instancePath . '/typo3'), '\\/') . '/../vendor/autoload.php';
        $classLoader = require $autoload;

        $instance = Bootstrap::getInstance();
        $instance = $instance->initializeClassLoader($classLoader);
        if (!defined('TYPO3_REQUESTTYPE')) {
            $instance = $instance->setRequestType(TYPO3_REQUESTTYPE_BE | TYPO3_REQUESTTYPE_CLI);
        }
        try {
            $instance = $instance->baseSetup();
        } catch (\Exception $e) {
            if ($e->getCode() !== 1376084316) {
                throw $e;
            }
        }
        $instance = $instance->loadConfigurationAndInitialize(true);

        $this->dumpClassLoadingInformation();

        Bootstrap::getInstance()
            ->loadTypo3LoadedExtAndExtLocalconf(true)
            ->setFinalCachingFrameworkCacheConfiguration()
            ->unsetReservedGlobalVariables();
    }
}
