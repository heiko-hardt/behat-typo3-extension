<?php

declare(strict_types=1);

namespace HeikoHardt\Behat\TYPO3Extension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use HeikoHardt\Behat\TYPO3Extension\Context\Initializer\Typo3AwareInitializer;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class Typo3Extension implements Extension
{

    const APPLICATION_ID = 'typo3_extension.application';

    public function getConfigKey()
    {
        return 'typo3';
    }

    public function configure(ArrayNodeDefinition $builder)
    {
        $builder->
            children()->
                scalarNode('t3_parameter')->defaultValue('none')->end()->
            end()->
        end();
    }

    public function load(ContainerBuilder $container, array $config)
    {
        $this->loadContextInitializer($container);
        $container->setParameter('typo3.parameters', $config);
        $container->setParameter('typo3.t3_parameter', $config['t3_parameter']);
    }

    private function loadContextInitializer(ContainerBuilder $container)
    {
        $definition = new Definition(
            Typo3AwareInitializer::class,
            array(
            new Reference(self::APPLICATION_ID),
            '%typo3.parameters%',
        )
        );
        $container->setDefinition('typo3.context_initializer', $definition);
    }
}
