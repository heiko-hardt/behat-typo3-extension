<?php

namespace HeikoHardt\Behat\TYPO3Extension\Typo3\V06;

use TYPO3\CMS\Core\Tests\FunctionalTestCase;
use HeikoHardt\Behat\TYPO3Extension\ServiceContainer\Typo3BootstrapUtilityDynamic;
use HeikoHardt\Behat\TYPO3Extension\ServiceContainer\Typo3BootstrapUtilityStatic;

/**
 * Legacy class
 */
class Typo3 extends FunctionalTestCase {

	/** @var array */
	private $typo3CoreExtensionsToLoad = array();

	/** @var array */
	private $typo3TestExtensionsToLoad = array();

	/** @var array */
	private $typo3PathsToLinkInTestInstance = array();

	/** @var array */
	private $typo3ConfigurationToUseInTestInstance = array();

	/** @var array */
	private $typo3AdditionalFoldersToCreate = array();

	/** @var array */
	private $typo3DatabaseToImport = array();

	/** @var array */
	private $typo3FrontendRootPage = array();

	/** @var \TYPO3\CMS\Extbase\Object\ObjectManager */
	public $typo3ObjectManager = NULL;

	/** @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager */
	public $typo3PersistenceManager = NULL;

	/**
	 * TYPO3 boot
	 *
	 * @param \Behat\Behat\Context\Context $context
	 * @param \Behat\Behat\Hook\Scope\ScenarioScope $scope
	 */
	public function TYPO3Boot(&$context = NULL, &$scope = NULL) {

		if (!defined('ORIGINAL_ROOT'))
			define('ORIGINAL_ROOT', strtr(getenv('TYPO3_PATH_WEB') ? getenv('TYPO3_PATH_WEB') . '/' : getcwd() . '/', '\\', '/'));

		if (getenv('BEHAT_TYPO3_DOCROOT') && !defined('BEHAT_ROOT'))
			define('BEHAT_ROOT', strtr(getenv('BEHAT_TYPO3_DOCROOT'), '\\', '/'));

		$this->typo3BootstrapUtility = defined('BEHAT_ROOT')
			? new Typo3BootstrapUtilityStatic()
			: new Typo3BootstrapUtilityDynamic();

		$this->typo3InstancePath = $this->typo3BootstrapUtility->setUp(
			$scope->getFeature()->getFile(),
			$this->typo3CoreExtensionsToLoad,
			$this->typo3TestExtensionsToLoad,
			$this->typo3PathsToLinkInTestInstance,
			$this->typo3ConfigurationToUseInTestInstance,
			$this->typo3AdditionalFoldersToCreate
		);

		// import db
		if (count($this->typo3DatabaseToImport) > 0) {
			foreach ($this->typo3DatabaseToImport AS $path) {
				$this->importDataSet($path);
			}
		}

		// setup fe
		if (count($this->typo3FrontendRootPage) > 0) {
			$this->setupFrontendRootPage(
				$this->typo3FrontendRootPage['pageId'],
				$this->typo3FrontendRootPage['typoscript'],
				$this->typo3FrontendRootPage['typoscriptConstants']
			);
		}

		// rewrite mink parameter
		$this->typo3BootstrapUtility->rewriteMinkParameter($context);

		// preparing object manager
		$this->typo3ObjectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

		// preparing persistance manager
		$this->typo3PersistenceManager = $this->typo3ObjectManager
			->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager');

	}

	public function TYPO3Destroy() {
		$this->typo3BootstrapUtility->destroy();
	}

	public function setTYPO3LocalConfiguration(array $typo3LocalConfiguration = array()) {
		$this->typo3ConfigurationToUseInTestInstance = $typo3LocalConfiguration;
	}

	public function setTYPO3CreateDirectories(array $typo3CreateDirectories = array()) {
		$this->typo3AdditionalFoldersToCreate = $typo3CreateDirectories;
	}

	public function setTYPO3CoreExtensionsToLoad(array $typo3CoreExtensionsToLoad = array()) {
		$this->typo3CoreExtensionsToLoad = $typo3CoreExtensionsToLoad;
	}

	public function setTYPO3TestExtensionsToLoad(array $typo3TestExtensionsToLoad = array()) {
		$this->typo3TestExtensionsToLoad = $typo3TestExtensionsToLoad;
	}

	public function setTYPO3DatasetToImport(array $typo3DatabaseToImport = array()) {
		$this->typo3DatabaseToImport = $typo3DatabaseToImport;
	}

	public function setTYPO3FrontendRootPage($pId = 0, array $typoscriptConstants = array(), array $typoscriptSetup = array()) {
		$this->typo3FrontendRootPage = array(
			'pageId' => $pId,
			'typoscriptConstants' => $typoscriptConstants,
			'typoscript' => $typoscriptSetup
		);
	}

}
