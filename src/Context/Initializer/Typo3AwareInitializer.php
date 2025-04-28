<?php

declare(strict_types=1);

namespace HeikoHardt\Behat\TYPO3Extension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use HeikoHardt\Behat\TYPO3Extension\Context\Typo3Context;

class Typo3AwareInitializer implements ContextInitializer
{

    private $parameters;

    public function __construct(
        array $parameters
    ) {
        $this->parameters = $parameters;
    }

    public function initializeContext(
        Context $context
    ) {
        if ($context instanceof Typo3Context) {
            $context->setTypo3Parameters($this->parameters);
        }
    }
}
