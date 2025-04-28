<?php

declare(strict_types=1);

namespace HeikoHardt\Behat\TYPO3Extension\Helper;

use Symfony\Component\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Site
{
    public static function createSite(
        string $docroot = '/var/www/html/public/',
        string $identifier = 'website-local',
        int    $rootPageId = 1,
        string $basePath = 'http://website.local',
        array  $langConfiguration = []
    ) {
        $siteConfiguration = self::buildSiteConfiguration($rootPageId, $basePath);

        self::writeSiteConfiguration(
            $docroot,
            $identifier,
            $siteConfiguration,
            $langConfiguration
        );
    }

    protected static function writeSiteConfiguration(
        string $docroot,
        string $identifier,
        array  $site = [],
        array  $languages = [],
        array  $errorHandling = []
    ): void {
        $configuration = $site;
        if (!empty($languages)) {
            $configuration['languages'] = $languages;
        }
        if (!empty($errorHandling)) {
            $configuration['errorHandling'] = $errorHandling;
        }

        /* Original: ******************************************
        $siteConfiguration = new SiteConfiguration(
            $this->instancePath . '/typo3conf/sites/',
            $this->getContainer()->get('cache.core')
        );
        // ************************************************* */

        if (getenv('TYPO3_BRANCH') === '10.4') {
            $siteConfiguration = new SiteConfiguration(
                $docroot . '/typo3conf/sites/'
            );
        } elseif (getenv('TYPO3_BRANCH') === '11.5') {
            $siteConfiguration = new SiteConfiguration(
                $docroot . '/typo3conf/sites/'
            );
        } elseif (getenv('TYPO3_BRANCH') === '12.4') {
            $eventDispatcher = new EventDispatcher();
            $siteConfiguration = new SiteConfiguration(
                $docroot . '/typo3conf/sites/',
                $eventDispatcher
            );
        }

        // ensure no previous site configuration influences the test
        GeneralUtility::rmdir($docroot . '/typo3conf/sites/' . $identifier, true);
        $siteConfiguration->write($identifier, $configuration);
    }

    protected static function buildSiteConfiguration(
        int    $rootPageId,
        string $base = ''
    ): array {
        return [
            'rootPageId' => $rootPageId,
            'base' => $base,
        ];
    }
}
