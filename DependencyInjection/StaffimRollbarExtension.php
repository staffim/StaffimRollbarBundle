<?php
namespace Staffim\RollbarBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * @author Vyacheslav Salakhutdinov <megazoll@gmail.com>
 */
class StaffimRollbarExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $phpLoader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $xmlLoader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if (isset($config['access_token'])) {
            $phpLoader->load('parameters.php');
            $xmlLoader->load('services.xml');

            $container->setParameter('staffim_rollbar.rollbar.access_token', $config['access_token']);
            $container->setParameter('staffim_rollbar.rollbar.error_level', $config['error_level']);
            $container->setParameter('staffim_rollbar.scrub_exceptions', $config['scrub']['exceptions']);
            $container->setParameter('staffim_rollbar.scrub_parameters', $config['scrub']['parameters']);
            $container->setParameter('staffim_rollbar.notify_http_exception', $config['notify_http_exception']);
        }
    }
}
