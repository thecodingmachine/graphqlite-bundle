<?php


namespace TheCodingMachine\Graphqlite\Bundle\Tests;


use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NullSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use TheCodingMachine\Graphqlite\Bundle\GraphqliteBundle;
use Symfony\Component\Security\Core\User\User;

class GraphqliteTestingKernel extends Kernel
{
    use MicroKernelTrait;

    const CONFIG_EXTS = '.{php,xml,yaml,yml}';
    /**
     * @var bool
     */
    private $enableSession;
    /**
     * @var string
     */
    private $enableLogin;
    /**
     * @var bool
     */
    private $enableSecurity;
    /**
     * @var string|null
     */
    private $enableMe;

    public function __construct(bool $enableSession = true, ?string $enableLogin = null, bool $enableSecurity = true, ?string $enableMe = null)
    {
        parent::__construct('test', true);
        $this->enableSession = $enableSession;
        $this->enableLogin = $enableLogin;
        $this->enableSecurity = $enableSecurity;
        $this->enableMe = $enableMe;
    }

    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new GraphqliteBundle(),
        ];
    }

    public function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $loader->load(function(ContainerBuilder $container) {
            $frameworkConf = array(
                'secret' => 'S0ME_SECRET'
            );

            if ($this->enableSession) {
                $frameworkConf['session'] =[
                    'enabled' => true,
                    'handler_id' => NullSessionHandler::class
                ];
            }

            $container->loadFromExtension('framework', $frameworkConf);
            if ($this->enableSecurity) {
                $container->loadFromExtension('security', array(
                    'providers' => [
                        'in_memory' => [
                            'memory' => [
                                'users' => [
                                    'foo' => [
                                        'password' => 'bar',
                                        'roles' => 'ROLE_USER',
                                    ],
                               ],
                            ],
                        ],
                        'in_memory_other' => [
                            'memory' => [
                                'users' => [
                                    'foo' => [
                                        'password' => 'bar',
                                        'roles' => 'ROLE_USER',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'firewalls' => [
                        'main' => [
                            'anonymous' => true,
                            'provider' => 'in_memory'
                        ]
                    ],
                    'encoders' => [
                        User::class => 'plaintext',
                    ],
                ));
            }

            $graphqliteConf = array(
                'namespace' => [
                    'controllers' => ['TheCodingMachine\\Graphqlite\\Bundle\\Tests\\Fixtures\\Controller\\'],
                    'types' => ['TheCodingMachine\\Graphqlite\\Bundle\\Tests\\Fixtures\\Types\\', 'TheCodingMachine\\Graphqlite\\Bundle\\Tests\\Fixtures\\Entities\\']
                ],
            );

            if ($this->enableLogin) {
                $graphqliteConf['security']['enable_login'] = $this->enableLogin;
            }

            if ($this->enableMe) {
                $graphqliteConf['security']['enable_me'] = $this->enableMe;
            }

            $container->loadFromExtension('graphqlite', $graphqliteConf);
        });
        $confDir = $this->getProjectDir().'/Tests/Fixtures/config';

        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->import(__DIR__.'/../Resources/config/routes.xml');
    }

    public function getCacheDir()
    {
        return __DIR__.'/../cache/'.spl_object_hash($this);
    }
}
