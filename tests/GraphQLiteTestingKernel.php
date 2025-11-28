<?php


namespace TheCodingMachine\GraphQLite\Bundle\Tests;


use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use TheCodingMachine\GraphQLite\Bundle\GraphQLiteBundle;
use Symfony\Component\Security\Core\User\InMemoryUser;
use function class_exists;
use function serialize;

class GraphQLiteTestingKernel extends Kernel implements CompilerPassInterface
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
                                array $controllersNamespace = ['TheCodingMachine\\GraphQLite\\Bundle\\Tests\\Fixtures\\Controller\\'],
                                array $typesNamespace = ['TheCodingMachine\\GraphQLite\\Bundle\\Tests\\Fixtures\\Types\\', 'TheCodingMachine\\GraphQLite\\Bundle\\Tests\\Fixtures\\Entities\\'])
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

    public function registerBundles(): iterable
    {
        $bundles = [ new FrameworkBundle() ];
        if ($this->enableSecurity && class_exists(SecurityBundle::class)) {
            $bundles[] = new SecurityBundle();
        }
        $bundles[] = new GraphQLiteBundle();
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

            $frameworkConf['router'] =[
                'utf8' => true,
            ];

            if ($this->enableSession) {
                $frameworkConf['session'] =[
                    'enabled' => true,
                    'storage_factory_id' => 'session.storage.factory.mock_file',
                ];
            }

            $container->loadFromExtension('framework', $frameworkConf);
            if ($this->enableSecurity) {
                $extraConfig = [];
                if (InstalledVersions::satisfies(new VersionParser(), 'symfony/security-bundle', '< 7.0.0')) {
                    $extraConfig['enable_authenticator_manager'] = true;
                }

                $container->loadFromExtension('security', array_merge(array(
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
                            'provider' => 'in_memory'
                        ]
                    ],
                    'password_hashers' => [
                        InMemoryUser::class => 'plaintext',
                    ],
                ), $extraConfig));
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
        $confDir = $this->getProjectDir().'/tests/Fixtures/config';

        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__.'/../src/Resources/config/routes.php');
    }

    public function getCacheDir(): string
    {
        $prefix = ($this->enableSession?'withSession':'withoutSession')
            .$this->enableLogin
            .($this->enableSecurity?'withSecurity':'withoutSecurity')
            .$this->enableMe
            .'_'
            .($this->introspection?'withIntrospection':'withoutIntrospection');

        return __DIR__.'/../cache/'.$prefix.'_'.$this->maximumQueryComplexity.'_'.$this->maximumQueryDepth.'_'.md5(serialize($this->controllersNamespace).'_'.md5(serialize($this->typesNamespace)));
    }

    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('security.untracked_token_storage')) {
            $container->getDefinition('security.untracked_token_storage')->setPublic(true);
        }
    }
}
