<?php


namespace TheCodingMachine\Graphqlite\Bundle\Tests;


use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use TheCodingMachine\Graphqlite\Bundle\GraphqliteBundle;

class GraphqliteTestingKernel extends Kernel
{

    const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function registerBundles()
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new GraphqliteBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function(ContainerBuilder $container) {
            $container->loadFromExtension('framework', array(
                'secret' => 'S0ME_SECRET',
            ));
            $container->loadFromExtension('graphqlite', array(
                'namespace' => [
                    'controllers' => ['TheCodingMachine\\Graphqlite\\Bundle\\Tests\\Fixtures\\Controller\\'],
                    'types' => ['TheCodingMachine\\Graphqlite\\Bundle\\Tests\\Fixtures\\Types\\', 'TheCodingMachine\\Graphqlite\\Bundle\\Tests\\Fixtures\\Entities\\']
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
