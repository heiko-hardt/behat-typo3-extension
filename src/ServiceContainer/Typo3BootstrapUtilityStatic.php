<?php
namespace HeikoHardt\Behat\TYPO3Extension\ServiceContainer;

use TYPO3\CMS\Core\Tests\FunctionalTestCaseBootstrapUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Typo3BootstrapUtilityStatic extends FunctionalTestCaseBootstrapUtility {

	/**
	 * @var array These folder are always created
	 */
	protected $defaultFoldersToCreate = array(
		'',
		'/fileadmin',
		'/typo3temp',
		'/typo3temp/Cache',
		'/typo3temp/Cache/Code',
		'/typo3temp/Cache/Code/cache_core',
		'/typo3temp/Cache/Code/cache_phpcode',
		'/typo3temp/Cache/Data',
		'/typo3temp/Cache/Data/cache_classes',
		'/typo3conf',
		'/typo3conf/ext',
		'/uploads'
	);

	/**
	 * Calculates path to TYPO3 CMS test installation for this test case.
	 *
	 * @return void
	 */
	protected function setUpInstancePath() {
		$this->instancePath = BEHAT_ROOT;
	}

	/**
	 * This method will only used in flexible context
	 *
	 * @param null $context
	 */
	public function rewriteMinkParameter(&$context = NULL) {
	}

	/**
	 * Teardown database and filesystem
	 */
	public function destroy() {
		$this->removeInstance();
		$this->tearDownTestDatabase();
	}

	/**
	 * Remove all directory and files within the test instance folder.
	 *
	 * @return void
	 */
	protected function removeOldInstanceIfExists() {

		$dir = scandir($this->instancePath);

		foreach ($dir AS $entry) {
			if (is_dir($this->instancePath . '/' . $entry) && $entry != '..' && $entry != '.') {
				GeneralUtility::rmdir($this->instancePath . '/' . $entry, TRUE);
			} else if (is_file($this->instancePath . '/' . $entry)) {
				unlink($this->instancePath . '/' . $entry);
			}
		}

	}

	/**
	 * Create folder structure of test instance.
	 *
	 * @param array $additionalFoldersToCreate Array of additional folders to be created
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function setUpInstanceDirectories(array $additionalFoldersToCreate = array()) {

		$foldersToCreate = array_merge($this->defaultFoldersToCreate, $additionalFoldersToCreate);

		foreach ($foldersToCreate as $folder) {
			if (trim($folder) !== '') {
				$success = mkdir($this->instancePath . $folder, 0777, TRUE);
				if (!$success) {
					throw new \Exception(
						'Creating directory failed: ' . $this->instancePath . $folder,
						1376657189
					);
				}
			}
		}

	}

	/**
	 * Create LocalConfiguration.php file in the test instance
	 *
	 * @param array $configurationToMerge
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function setUpLocalConfiguration(array $configurationToMerge) {

		$databaseName = trim(getenv('BEHAT_TYPO3_DB_NAME'));
		$databaseHost = trim(getenv('BEHAT_TYPO3_DB_HOST'));
		$databaseUsername = trim(getenv('BEHAT_TYPO3_DB_USERNAME'));
		$databasePassword = trim(getenv('BEHAT_TYPO3_DB_PASSWORD'));
		$databasePort = trim(getenv('BEHAT_TYPO3_DB_PORT'));
		$databaseSocket = trim(getenv('BEHAT_TYPO3_DB_SOCKET'));

		if ($databaseName || $databaseHost || $databaseUsername || $databasePassword || $databasePort || $databaseSocket) {
			// Try to get database credentials from environment variables first
			$originalConfigurationArray = array(
				'DB' => array(),
			);
			if ($databaseName) {
				$originalConfigurationArray['DB']['database'] = $databaseName;
			}
			if ($databaseHost) {
				$originalConfigurationArray['DB']['host'] = $databaseHost;
			}
			if ($databaseUsername) {
				$originalConfigurationArray['DB']['username'] = $databaseUsername;
			}
			if ($databasePassword) {
				$originalConfigurationArray['DB']['password'] = $databasePassword;
			}
			if ($databasePort) {
				$originalConfigurationArray['DB']['port'] = $databasePort;
			}
			if ($databaseSocket) {
				$originalConfigurationArray['DB']['socket'] = $databaseSocket;
			}

		} else {
			throw new Exception(
				'Database credentials for functional tests are neither set through environment'
				. ' variables, and can not be found in an existing LocalConfiguration file',
				1397406356
			);
		}

		// Base of final LocalConfiguration is core factory configuration
		$finalConfigurationArray = require ORIGINAL_ROOT . 'typo3/sysext/core/Configuration/FactoryConfiguration.php';

		$this->mergeRecursiveWithOverrule($finalConfigurationArray, require ORIGINAL_ROOT . 'typo3/sysext/core/Build/Configuration/FunctionalTestsConfiguration.php');
		$this->mergeRecursiveWithOverrule($finalConfigurationArray, $configurationToMerge);
		$finalConfigurationArray['DB'] = $originalConfigurationArray['DB'];

		// Calculate and set new database name
		$this->originalDatabaseName = $originalConfigurationArray['DB']['database'];
		$this->databaseName = $this->originalDatabaseName;

		// Maximum database name length for mysql is 64 characters
		if (strlen($this->databaseName) > 64) {
			$maximumOriginalDatabaseName = 64 - strlen('_ft' . $this->identifier);
			throw new Exception(
				'The name of the database that is used for the functional test (' . $this->databaseName . ')' .
				' exceeds the maximum length of 64 character allowed by MySQL. You have to shorten your' .
				' original database name to ' . $maximumOriginalDatabaseName . ' characters',
				1377600104
			);
		}

		$finalConfigurationArray['DB']['database'] = $this->databaseName;

		$result = $this->writeFile(
			$this->instancePath . '/typo3conf/LocalConfiguration.php',
			'<?php' . chr(10) .
			'return ' .
			$this->arrayExport(
				$finalConfigurationArray
			) .
			';' . chr(10) .
			'?>'
		);
		if (!$result) {
			throw new Exception('Can not write local configuration', 1376657277);
		}
	}


	/**
	 * Set up creates a test instance and database.
	 *
	 * @param string $testCaseClassName         Name of test case class
	 * @param array  $coreExtensionsToLoad      Array of core extensions to load
	 * @param array  $testExtensionsToLoad      Array of test extensions to load
	 * @param array  $pathsToLinkInTestInstance Array of source => destination path pairs to be linked
	 * @param array  $configurationToUse        Array of TYPO3_CONF_VARS that need to be overridden
	 * @param array  $additionalFoldersToCreate Array of folder paths to be created
	 *
	 * @return string Path to TYPO3 CMS test installation for this test case
	 */
	public function setUp(
		$testCaseClassName,
		array $coreExtensionsToLoad,
		array $testExtensionsToLoad,
		array $pathsToLinkInTestInstance,
		array $configurationToUse,
		array $additionalFoldersToCreate
	) {

		$this->setUpIdentifier($testCaseClassName);
		$this->setUpInstancePath();

		// cleanup
		$this->removeOldInstanceIfExists();
		$this->cleanupGLOBALS();

		// setup
		$this->setUpInstanceDirectories($additionalFoldersToCreate);
		$this->setUpInstanceCoreLinks();
		$this->linkTestExtensionsToInstance($testExtensionsToLoad);
		$this->linkPathsInTestInstance($pathsToLinkInTestInstance);
		$this->setUpLocalConfiguration($configurationToUse);
		$this->setUpPackageStates($coreExtensionsToLoad, $testExtensionsToLoad);
		$this->setUpBasicTypo3Bootstrap();
		$this->setUpTestDatabase();
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadExtensionTables(TRUE);
		$this->createDatabaseStructure();

		return $this->instancePath;

	}

	/**
	 * Bootstrap basic TYPO3
	 *
	 * @return void
	 */
	protected function setUpBasicTypo3Bootstrap() {

		$_SERVER['PWD'] = $this->instancePath;
		$_SERVER['argv'][0] = 'index.php';

		define('TYPO3_MODE', 'BE');
		define('TYPO3_cliMode', TRUE);

		// already loaded
		require_once $this->instancePath . '/typo3/sysext/core/Classes/Core/CliBootstrap.php';
		\TYPO3\CMS\Core\Core\CliBootstrap::checkEnvironmentOrDie();

		// already loaded
		require_once $this->instancePath . '/typo3/sysext/core/Classes/Core/Bootstrap.php';

		/** @var \TYPO3\CMS\Core\Core\Bootstrap $bootstrap */
		$bootstrap = \TYPO3\CMS\Core\Core\Bootstrap::getInstance();

		/**
		 * Overwriting baseSetup
		 *
		 * original: $bootstrap->baseSetup('');
		 */
		$composerClassLoader = $this->initializeComposerClassLoader();
		$bootstrap->setEarlyInstance('Composer\\Autoload\\ClassLoader', $composerClassLoader);
		\TYPO3\CMS\Core\Core\SystemEnvironmentBuilder::run('');

		$bootstrap->loadConfigurationAndInitialize(TRUE);
		$bootstrap->loadTypo3LoadedExtAndExtLocalconf(TRUE);
		$bootstrap->applyAdditionalConfigurationSettings();

	}

	/**
	 * @return \Composer\Autoload\ClassLoader
	 */
	private function initializeComposerClassLoader() {
		$respectComposerPackagesForClassLoading = getenv('TYPO3_COMPOSER_AUTOLOAD') ?: (getenv('REDIRECT_TYPO3_COMPOSER_AUTOLOAD') ?: NULL);
		$possiblePaths = array();
		if (!empty($respectComposerPackagesForClassLoading)) {
			$possiblePaths['distribution'] = __DIR__ . '/../../../../../../Packages/Libraries/autoload.php';
		}
		$possiblePaths['fallback'] = __DIR__ . '/../../../../contrib/vendor/autoload.php';
		foreach ($possiblePaths as $possiblePath) {
			if (file_exists($possiblePath)) {
				return include $possiblePath;
			}
		}
		throw new \LogicException('No class loading information found for TYPO3 CMS. Please make sure you installed TYPO3 with composer or the typo3/contrib/vendor folder is present.', 1425153762);
	}

	/**
	 * Cleanup global variables
	 */
	private function cleanupGLOBALS() {
		unset($GLOBALS['typo3CacheManager']);
		unset($GLOBALS['typo3CacheFactory']);
		unset($GLOBALS['TYPO3_LOADED_EXT']);
		unset($GLOBALS['TYPO3_CONF_VARS']);
		unset($GLOBALS['TCA']);
		unset($GLOBALS['TYPO3_MISC']);
		unset($GLOBALS['T3_VAR']);
		unset($GLOBALS['T3_SERVICES']);
		unset($GLOBALS['TBE_MODULES']);
		unset($GLOBALS['TBE_MODULES_EXT']);
		unset($GLOBALS['TYPO3_CONF_VARS']);
		unset($GLOBALS['TCA_DESCR']);
	}
}