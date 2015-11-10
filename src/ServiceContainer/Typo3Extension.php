<?php
namespace HeikoHardt\Behat\TYPO3Extension\ServiceContainer;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class Typo3Extension implements Extension {

	const APPLICATION_ID = 'typo3_extension.application';

	public function getConfigKey() {
		return 'typo3';
	}

	public function initialize(ExtensionManager $extensionManager) {
	}

	public function process(ContainerBuilder $container) {
	}

	public function configure(ArrayNodeDefinition $builder) {
		$builder->
			children()->
				scalarNode('t3_parameter')->defaultValue('none')->end()->
			end()->
		end();
	}

	public function load(ContainerBuilder $container, array $config) {
		$this->loadTypo3($container);
		$this->loadContextInitializer($container);
		$container->setParameter('typo3.parameters', $config);
		$container->setParameter('typo3.t3_parameter', $config['t3_parameter']);
	}

	private function loadTypo3(ContainerBuilder $container) {
		$container->setDefinition(self::APPLICATION_ID, new Definition('\HeikoHardt\Behat\TYPO3Extension\Typo3'));
	}

	private function loadContextInitializer(ContainerBuilder $container) {
		$definition = new Definition('HeikoHardt\Behat\TYPO3Extension\Context\Initializer\Typo3AwareInitializer', array(
			new Reference(self::APPLICATION_ID),
			'%typo3.parameters%',
		));
		// $definition->addTag(ContextExtension::INITIALIZER_TAG, array('priority' => 0));
		$container->setDefinition('typo3.context_initializer', $definition);
	}

}