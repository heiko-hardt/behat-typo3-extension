<?php

namespace HeikoHardt\Behat\TYPO3Extension\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeFeatureScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use HeikoHardt\Behat\TYPO3Extension\Typo3Environment;

class Typo3Context extends RawMinkContext implements Context
{

    /** @var array */
    private $typo3Parameters = array();

    protected $typo3container = null;

    public function getTypo3Parameters()
    {
        return $this->typo3Parameters;
    }

    public function setTypo3Parameters(array $parameters)
    {
        $this->typo3Parameters = $parameters;
    }

    public function getTypo3Parameter($name)
    {
        return isset($this->typo3Parameters[$name]) ? $this->typo3Parameters[$name] : null;
    }

    public function setTypo3Parameter($name, $value)
    {
        $this->typo3Parameters[$name] = $value;
    }

    /**
     * @BeforeSuite
     */
    public static function beforeSuite(BeforeSuiteScope $scope)
    {
        $environment = $scope->getEnvironment()->getSuite()->hasSetting('environment')
            ? $scope->getEnvironment()->getSuite()->getSetting('environment')
            : [];

        if (count($environment) > 0) {
            (new Typo3Environment())->boot($environment);
        }
    }

    /**
     * @BeforeFeature
     */
    public static function beforeFeature(BeforeFeatureScope $scope)
    {
        $featureName = basename($scope->getFeature()->getFile(), ".feature");

        $featureList = $scope->getEnvironment()->getSuite()->hasSetting('features')
            ? $scope->getEnvironment()->getSuite()->getSetting('features')
            : [];

        $featureConfiguration = array_key_exists($featureName, $featureList)
            ? $featureList[$featureName]
            : [];

        $environment = array_key_exists('environment', $featureConfiguration)
            ? $featureConfiguration['environment']
            : [];

        if (count($environment) > 0) {
            (new Typo3Environment())->boot($environment);
        }
    }

    /** @BeforeScenario */
    public function beforeScenario(BeforeScenarioScope $scope)
    {
        if (is_null($this->typo3container)) {
            $this->typo3container = (new Typo3Environment())->boot([]);
        }
    }

    /**
     * @BeforeStep
     */
    public function beforeStep(BeforeStepScope $scope)
    {
        if (is_null($this->typo3container)) {
            $this->typo3container = (new Typo3Environment())->boot([]);
        }
    }
}
