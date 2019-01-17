<?php


namespace TheCodingMachine\Graphql\Controllers\Bundle\Tests;


use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use TheCodingMachine\Graphql\Controllers\Bundle\GraphqlControllersBundle;

class GraphqlControllersTestingKernel extends Kernel
{

    const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function registerBundles()
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new GraphqlControllersBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function(ContainerBuilder $container) {
            $container->loadFromExtension('framework', array(
                'secret' => 'S0ME_SECRET',
            ));
            $container->loadFromExtension('graphql_controllers', array(
                'namespace' => [
                    'controllers' => ['TheCodingMachine\\Graphql\\Controllers\\Bundle\\Tests\\Fixtures\\Controller\\'],
                    'types' => ['TheCodingMachine\\Graphql\\Controllers\\Bundle\\Tests\\Fixtures\\Types\\', 'TheCodingMachine\\Graphql\\Controllers\\Bundle\\Tests\\Fixtures\\Entities\\']
                ]
            ));
        });
        $confDir = $this->getProjectDir().'/Tests/Fixtures/config';

        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');
    }
}
