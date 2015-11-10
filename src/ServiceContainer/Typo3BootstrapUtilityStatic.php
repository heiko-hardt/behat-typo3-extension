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
	 * Bootstrap basic TYPO3
	 *
	 * @return void
	 */
	protected function setUpBasicTypo3Bootstrap() {

		$_SERVER['PWD'] = $this->instancePath;
		$_SERVER['argv'][0] = 'index.php';

		define('TYPO3_MODE', 'BE');
		define('TYPO3_cliMode', TRUE);

		require_once $this->instancePath . '/typo3/sysext/core/Classes/Core/CliBootstrap.php';
		\TYPO3\CMS\Core\Core\CliBootstrap::checkEnvironmentOrDie();

		require_once $this->instancePath . '/typo3/sysext/core/Classes/Core/Bootstrap.php';
		$bootstrap = \TYPO3\CMS\Core\Core\Bootstrap::getInstance();

		/**
		 * Ignoring exception
		 *
		 * message: 'Trying to override applicationContext which has already been defined!'
		 * code: 1376084316
		 * from: Utility\GeneralUtility::presetApplicationContext($this->applicationContext);
		 */
		try {
			$bootstrap->baseSetup('');
		} catch (\Exception $e) {
			if ($e->getCode() !== 1376084316) {
				throw $e;
			}
		}

		$bootstrap->loadConfigurationAndInitialize(TRUE);
		$bootstrap->loadTypo3LoadedExtAndExtLocalconf(TRUE);
		$bootstrap->applyAdditionalConfigurationSettings();

	}

}