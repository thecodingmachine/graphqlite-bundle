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
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\RouteCollectionBuilder;
use TheCodingMachine\Graphqlite\Bundle\GraphqliteBundle;
use Symfony\Component\Security\Core\User\User;
use function class_exists;
use function serialize;

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
    /**
     * @var bool
     */
    private $introspection;
    /**
     * @var int|null
     */
    private $maximumQueryComplexity;
    /**
     * @var int|null
     */
    private $maximumQueryDepth;
    /**
     * @var array|string[]
     */
    private $controllersNamespace;
    /**
     * @var array|string[]
     */
    private $typesNamespace;

    /**
     * @param string[] $controllersNamespace
     * @param string[] $typesNamespace
     */
    public function __construct(bool $enableSession = true,
                                ?string $enableLogin = null,
                                bool $enableSecurity = true,
                                ?string $enableMe = null,
                                bool $introspection = true,
                                ?int $maximumQueryComplexity = null,
                                ?int $maximumQueryDepth = null,
                                array $controllersNamespace = ['TheCodingMachine\\Graphqlite\\Bundle\\Tests\\Fixtures\\Controller\\'],
                                array $typesNamespace = ['TheCodingMachine\\Graphqlite\\Bundle\\Tests\\Fixtures\\Types\\', 'TheCodingMachine\\Graphqlite\\Bundle\\Tests\\Fixtures\\Entities\\'])
    {
        parent::__construct('test', true);
        $this->enableSession = $enableSession;
        $this->enableLogin = $enableLogin;
        $this->enableSecurity = $enableSecurity;
        $this->enableMe = $enableMe;
        $this->introspection = $introspection;
        $this->maximumQueryComplexity = $maximumQueryComplexity;
        $this->maximumQueryDepth = $maximumQueryDepth;
        $this->controllersNamespace = $controllersNamespace;
        $this->typesNamespace = $typesNamespace;
    }

    public function registerBundles()
    {
        $bundles = [ new FrameworkBundle() ];
        if (class_exists(SecurityBundle::class)) {
            $bundles[] = new SecurityBundle();
        }
        $bundles[] = new GraphqliteBundle();
        return $bundles;
    }

    public function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $loader->load(function(ContainerBuilder $container) {
            $frameworkConf = array(
                'secret' => 'S0ME_SECRET'
            );

            $frameworkConf['cache'] =[
                'app' => 'cache.adapter.array',
            ];

            // @phpstan-ignore-next-line
            if (self::VERSION_ID >= 42000) {
                $frameworkConf['router'] =[
                    'utf8' => true,
                ];
            }

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
                    'controllers' => $this->controllersNamespace,
                    'types' => $this->typesNamespace
                ],
            );

            if ($this->enableLogin) {
                $graphqliteConf['security']['enable_login'] = $this->enableLogin;
            }

            if ($this->enableMe) {
                $graphqliteConf['security']['enable_me'] = $this->enableMe;
            }

            if ($this->introspection === false) {
                $graphqliteConf['security']['introspection'] = false;
            }

            if ($this->maximumQueryComplexity !== null) {
                $graphqliteConf['security']['maximum_query_complexity'] = $this->maximumQueryComplexity;
            }

            if ($this->maximumQueryDepth !== null) {
                $graphqliteConf['security']['maximum_query_depth'] = $this->maximumQueryDepth;
            }

            $container->loadFromExtension('graphqlite', $graphqliteConf);
        });
        $confDir = $this->getProjectDir().'/Tests/Fixtures/config';

        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');
    }

    // Note: typing is disabled because using different classes in Symfony 4 and 5
    protected function configureRoutes(/*RoutingConfigurator*/ $routes)
    {
        $routes->import(__DIR__.'/../Resources/config/routes.xml');
    }

    public function getCacheDir()
    {
        return __DIR__.'/../cache/'.($this->enableSession?'withSession':'withoutSession').$this->enableLogin.($this->enableSecurity?'withSecurity':'withoutSecurity').$this->enableMe.'_'.($this->introspection?'withIntrospection':'withoutIntrospection').'_'.$this->maximumQueryComplexity.'_'.$this->maximumQueryDepth.'_'.md5(serialize($this->controllersNamespace).'_'.md5(serialize($this->typesNamespace)));
    }
}
