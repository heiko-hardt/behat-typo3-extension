<?php

namespace HeikoHardt\Behat\TYPO3Extension\Typo3\V06;

use HeikoHardt\Behat\TYPO3Extension\Typo3\V06\VendorResource\FunctionalTestCaseBootstrapUtility;

class Testbase extends FunctionalTestCaseBootstrapUtility
{
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

	public function setUp(
		$testCaseClassName,
		array $coreExtensionsToLoad,
		array $testExtensionsToLoad,
		array $pathsToLinkInTestInstance,
		array $configurationToUse,
		array $additionalFoldersToCreate
	) {
		$this->setUpIdentifier($testCaseClassName);
		$this->setUpInstancePath($testCaseClassName);
		if ($this->recentTestInstanceExists() && false) {
			$this->setUpBasicTypo3Bootstrap();
			$this->initializeTestDatabase();
			\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadExtensionTables(TRUE);
		} else {
			$this->removeOldInstanceIfExists();
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
		}
		return $this->instancePath;
	}

	protected function setUpInstancePath($testCaseClassName)
	{
		$this->instancePath = '/var/www/html/public';
	}

	protected function setUpLocalConfiguration(array $configurationToMerge)
	{
		$databaseName = trim(getenv('typo3DatabaseName'));
		$databaseHost = trim(getenv('typo3DatabaseHost'));
		$databaseUsername = trim(getenv('typo3DatabaseUsername'));
		$databasePassword = trim(getenv('typo3DatabasePassword'));
		$databasePort = trim(getenv('typo3DatabasePort'));
		$databaseSocket = trim(getenv('typo3DatabaseSocket'));
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
		} elseif (file_exists(ORIGINAL_ROOT . 'typo3conf/LocalConfiguration.php')) {
			// See if a LocalConfiguration file exists in "parent" instance to get db credentials from
			$originalConfigurationArray = require ORIGINAL_ROOT . 'typo3conf/LocalConfiguration.php';
		} else {
			throw new \Exception(
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

		// Start of the change ######################################################### ->
		// $this->databaseName = $this->originalDatabaseName . '_ft' . $this->identifier;
		$this->databaseName = $this->originalDatabaseName;
		// ####################################################################### <-- ende

		// Maximum database name length for mysql is 64 characters
		if (strlen($this->databaseName) > 64) {
			$maximumOriginalDatabaseName = 64 - strlen('_ft' . $this->identifier);
			throw new \Exception(
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
			throw new \Exception('Can not write local configuration', 1376657277);
		}
	}

	protected function setUpBasicTypo3Bootstrap()
	{
		$_SERVER['PWD'] = $this->instancePath;
		$_SERVER['argv'][0] = 'index.php';

		define('TYPO3_MODE', 'BE');
		define('TYPO3_cliMode', TRUE);

		\TYPO3\CMS\Core\Core\SystemEnvironmentBuilder::run('');

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
