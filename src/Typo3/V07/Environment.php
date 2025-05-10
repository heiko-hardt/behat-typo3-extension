<?php

namespace HeikoHardt\Behat\TYPO3Extension\Typo3\V07;

use TYPO3\CMS\Core\Tests\FunctionalTestCase;

class Environment extends FunctionalTestCase
{
    protected $configuration;
    protected $testbase;

    public function boot()
    {
        if (!defined('ORIGINAL_ROOT')) {
            define('ORIGINAL_ROOT', (getenv('ORIGIN_PATH_ROOT').'/'));
        }

        $this->mapDatabaseConfiguration();

        if (isset($this->configuration['setup'])) {
            $this->testbase = new Testbase();
            $this->testbase->setUp(
                get_class($this),
                (isset($this->configuration['setup']['coreExtensionsToLoad']) ? $this->configuration['setup']['coreExtensionsToLoad'] : []),
                (isset($this->configuration['setup']['testExtensionsToLoad']) ? $this->configuration['setup']['testExtensionsToLoad'] : []),
                (isset($this->configuration['setup']['pathsToLinkInTestInstance']) ? $this->configuration['setup']['pathsToLinkInTestInstance'] : []),
                (isset($this->configuration['setup']['configurationToUse']) ? $this->configuration['setup']['configurationToUse'] : []),
                (isset($this->configuration['setup']['additionalFoldersToCreate']) ? $this->configuration['setup']['additionalFoldersToCreate'] : [])
            );

            if (isset($this->configuration['fixtures']['xmlDatabaseFixtures'])) {
                if (count($this->configuration['fixtures']['xmlDatabaseFixtures']) > 0) {
                    foreach ($this->configuration['fixtures']['xmlDatabaseFixtures'] as $path) {
                        $this->importDataSet($path);
                    }
                }
            }
        }
    }

    public function setConfiguration($configuration = null)
    {
        $this->configuration = $configuration;
        return $this;
    }

    protected function mapDatabaseConfiguration()
    {
        putenv('typo3DatabaseName=' . getenv('TESTING_DB_NAME'));
        putenv('typo3DatabaseHost=' . getenv('TESTING_DB_HOST'));
        putenv('typo3DatabaseUsername=' . getenv('TESTING_DB_USER'));
        putenv('typo3DatabasePassword=' . getenv('TESTING_DB_PASSWORD'));
        putenv('typo3DatabasePort=' . getenv('TESTING_DB_PORT'));
    }

    protected function getDefaultExtensionsToLoad()
    {
        return [
            'core',
            'wizard_sortpages',
            'wizard_crpages',
            'viewpage',
            'tstemplate',
            't3skin',
            't3editor',
            'sys_note',
            'sv',
            'saltedpasswords',
            'setup',
            'rtehtmlarea',
            'rsaauth',
            'reports',
            'recordlist',
            'perm',
            'lowlevel',
            'lang',
            'extbase',
            'fluid',
            'install',
            'info_pagetsconfig',
            'info',
            'impexp',
            'func_wizards',
            'func',
            'cms',
            'frontend',
            'form',
            'filelist',
            'felogin',
            'extra_page_cm_options',
            'extensionmanager',
            'documentation',
            'css_styled_content',
            'cshmanual',
            'context_help',
            'beuser',
            'belog',
            'backend',
            'aboutmodules',
            'about',
            // 'adodb',
            // 'dbal',
            // 'feedit',
            // 'filemetadata',
            // 'indexed_search',
            // 'indexed_search_mysql',
            // 'linkvalidator',
            // 'opendocs',
            // 'openid',
            // 'recycler',
            // 'scheduler',
            // 'sys_action',
            // 'taskcenter',
            // 'version',
            // 'workspaces'
        ];
    }
}
