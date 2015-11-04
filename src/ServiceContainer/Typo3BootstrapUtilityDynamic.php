<?php
namespace HeikoHardt\Behat\TYPO3Extension\ServiceContainer;

use TYPO3\CMS\Core\Tests\FunctionalTestCaseBootstrapUtility;

class Typo3BootstrapUtilityDynamic extends FunctionalTestCaseBootstrapUtility {

	/**
	 * Calculates path to TYPO3 CMS test installation for this test case.
	 *
	 * @return void
	 */
	protected function setUpInstancePath() {

		if (!is_dir(ORIGINAL_ROOT . 'typo3temp')) {
			if (!mkdir(ORIGINAL_ROOT . 'typo3temp', 0777, TRUE)) {
				throw new \RuntimeException('Directory "' . ORIGINAL_ROOT . 'typo3temp' . '" could not be created', 1404038665);
			}
		}

		$this->instancePath = ORIGINAL_ROOT . 'typo3temp/acceptance-' . $this->identifier;

	}

	/**
	 * This method rewrites the mink url using the new identifier
	 *
	 * @param null $context
	 */
	public function rewriteMinkParameter(&$context = NULL) {

		$context->setMinkParameter('base_url',
			$context->getMinkParameter('base_url') . '/typo3temp/acceptance-' . $this->identifier
		);

	}

	/**
	 * Teardown database and filesystem
	 *
	 * @throws \TYPO3\CMS\Core\Tests\Exception
	 */
	public function destroy() {

		$this->removeInstance();
		$this->tearDownTestDatabase();

	}

}