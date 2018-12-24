<?php


namespace TheCodingMachine\GraphQL\Controllers\Bundle\Tests;


use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use TheCodingMachine\GraphQL\Controllers\Bundle\GraphQLControllersBundle;

class GraphQLControllersTestingKernel extends Kernel
{

    const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function registerBundles()
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new GraphQLControllersBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function(ContainerBuilder $container) {
            $container->loadFromExtension('framework', array(
                'secret' => 'S0ME_SECRET'
            ));
        });
        $confDir = $this->getProjectDir().'/Tests/Fixtures/config';

        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');

    }
}
