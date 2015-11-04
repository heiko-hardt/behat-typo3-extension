<?php
namespace HeikoHardt\Behat\TYPO3Extension\Context;

use HeikoHardt\Behat\TYPO3Extension\Typo3;

class Typo3Context
	extends Typo3
	implements Typo3AwareContext {

	/** @var \HeikoHardt\Behat\TYPO3Extension\Typo3 */
	private $typo3 = NULL;

	/** @var array */
	private $typo3Parameters = array();

	/**
	 * Set the typo3 instance
	 * This will be done automaticaly by the Typo3AwareInitializer
	 *
	 * @param Typo3 $typo3
	 */
	public function setTypo3(Typo3 $typo3) {
		$this->typo3 = $typo3;
	}

	/**
	 * Return the Typo3 instance
	 * This needs to be used if FeatureContext do not extends the Typo3Context
	 *
	 * @return Typo3
	 */
	public function getTypo3() {
		if (NULL === $this->typo3) {
			throw new \RuntimeException(
				'Typo3 instance has not been set on Typo3 context class. ' .
				'Have you enabled the Typo3 Extension?'
			);
		}
		return $this->typo3;
	}

	public function getTypo3Parameters() {
		return $this->typo3Parameters;
	}

	public function setTypo3Parameters(array $parameters) {
		$this->typo3Parameters = $parameters;
	}

	public function getTypo3Parameter($name) {
		return isset($this->typo3Parameters[$name]) ? $this->typo3Parameters[$name] : NULL;
	}

	public function setTypo3Parameter($name, $value) {
		$this->typo3Parameters[$name] = $value;
	}

}