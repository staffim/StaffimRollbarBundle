<?php
namespace Staffim\RollbarBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class StaffimRollbarExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if (isset($config['access_token'])) {
            $loader->load('services.xml');

            $container->setParameter('staffim_rollbar.rollbar.access_token', $config['access_token']);
            $container->setParameter('staffim_rollbar.rollbar.error_level', $config['error_level']);
        }
    }
}
