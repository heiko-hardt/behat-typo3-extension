<?php
namespace HeikoHardt\Behat\TYPO3Extension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;

use HeikoHardt\Behat\TYPO3Extension\Context\Typo3AwareContext;

class Typo3AwareInitializer implements ContextInitializer {

	private $typo3;
	private $parameters;

	public function __construct($typo3, array $parameters) {
		$this->typo3 = $typo3;
		$this->parameters = $parameters;
	}

	public function initializeContext(Context $context) {
		if (!$context instanceof Typo3AwareContext) {
			return;
		}
		$context->setTypo3($this->typo3);
		$context->setTypo3Parameters($this->parameters);
	}

}