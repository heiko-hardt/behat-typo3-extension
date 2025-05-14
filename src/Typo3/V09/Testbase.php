<?php

namespace HeikoHardt\Behat\TYPO3Extension\Typo3\V09;

use HeikoHardt\Behat\TYPO3Extension\Typo3\AbstractTestbase;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Exception;

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
        chdir($instancePath);
        foreach ($linksToSet as $from => $to) {
            $success = symlink(realpath($from), $to);
            if (!$success) {
                throw new Exception(
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

        // Reset state from a possible previous run
        GeneralUtility::purgeInstances();
        GeneralUtility::resetApplicationContext();

        /* original ######################################################
        $classLoader = require __DIR__ . '/../../../../autoload.php';
        // ############################################################ */
        $autoload = isset($GLOBALS['_composer_autoload_path'])
            ? $GLOBALS['_composer_autoload_path']
            : __DIR__ . '/../../../../autoload.php';
        $classLoader = require $autoload;

        SystemEnvironmentBuilder::run(0, SystemEnvironmentBuilder::REQUESTTYPE_BE | SystemEnvironmentBuilder::REQUESTTYPE_CLI);
        Bootstrap::init($classLoader);
        // Make sure output is not buffered, so command-line output can take place and
        // phpunit does not whine about changed output bufferings in tests.
        ob_end_clean();

        $this->dumpClassLoadingInformation();
    }

    public function createSiteConfiguration(
        string $instancePath,
        ?array $siteConfiguration = null,
        ?array $siteConfigurationOverwrite = null
    ): void {
        /** @var SiteConfiguration $configurationService */
        $configurationService = new SiteConfiguration($instancePath . '/typo3conf/sites');
        if ($siteConfiguration) {
            $configurationService->write('website-local', $siteConfiguration);
        } else {
            $site = $this->createNewBasicSite(1, getenv('TYPO3_URL') ?: 'http://localhost');
            $configurationService->write('website-local', $site);
        }
        if ($siteConfigurationOverwrite) {
            $site = $configurationService->load('website-local');
            $site = array_merge_recursive($site, $siteConfigurationOverwrite);
            $configurationService->write('website-local', $site);
        }
    }

    /**
     * Creates a site configuration with one language "English" which is the de-facto default language for TYPO3 in general.
     *
     * @param int $rootPageId
     * @param string $base
     */
    public function createNewBasicSite(int $rootPageId, string $base): array
    {
        // Create a default site configuration called "main" as best practice
        return [
            'rootPageId' => $rootPageId,
            'base' => $base,
            'languages' => [
                0 => [
                    'title' => 'English',
                    'enabled' => true,
                    'languageId' => 0,
                    'base' => '/',
                    'typo3Language' => 'default',
                    'locale' => 'en_US.UTF-8',
                    'iso-639-1' => 'en',
                    'navigationTitle' => 'English',
                    'hreflang' => 'en-us',
                    'direction' => 'ltr',
                    'flag' => 'us',
                ],
            ],
            'errorHandling' => [],
            'routes' => [],
        ];
    }
}
