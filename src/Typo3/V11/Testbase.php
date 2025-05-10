<?php

declare(strict_types=1);

namespace HeikoHardt\Behat\TYPO3Extension\Typo3\V11;

use HeikoHardt\Behat\TYPO3Extension\Typo3\AbstractTestbase;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;

class Testbase extends AbstractTestbase
{
    public function createSiteConfiguration(
        ContainerInterface $container,
        ?array $siteConfiguration = null,
        ?array $siteConfigurationAdditional = null
    ): void {
        /** @var SiteConfiguration $configurationService */
        $configurationService = $container->get(SiteConfiguration::class);
        if ($siteConfiguration) {
            $configurationService->write('website-local', $siteConfiguration);
        } else {
            $configurationService->createNewBasicSite('website-local', 1, getenv('TYPO3_URL') ?: 'http://localhost');
        }
        if ($siteConfigurationAdditional) {
            $site = $configurationService->load('website-local');
            $site = array_merge_recursive($site, $siteConfigurationAdditional);
            $configurationService->write('website-local', $site);
        }
    }
}
