<?php
namespace TheCodingMachine\Graphqlite\Bundle\Controller\GraphQL;


use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

class LoginController
{

    /**
     * @var UserProviderInterface
     */
    private $userProvider;
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var string
     */
    private $firewallName;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(UserProviderInterface $userProvider, UserPasswordEncoderInterface $passwordEncoder, TokenStorageInterface $tokenStorage, SessionInterface $session, EventDispatcherInterface $eventDispatcher, string $firewallName)
    {
        $this->userProvider = $userProvider;
        $this->passwordEncoder = $passwordEncoder;
        $this->tokenStorage = $tokenStorage;
        $this->firewallName = $firewallName;
        $this->session = $session;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Mutation()
     */
    public function login(string $userName, string $password, Request $request): bool
    {
        try {
            $user = $this->userProvider->loadUserByUsername($userName);
        } catch (UsernameNotFoundException $e) {
            // FIXME: should we return false instead???
            throw InvalidUserPasswordException::create($e);
        }

        if (!$this->passwordEncoder->isPasswordValid($user, $password)) {
            throw InvalidUserPasswordException::create();
        }

        // User and passwords are valid. Let's login!

        // Handle getting or creating the user entity likely with a posted form
        // The third parameter "main" can change according to the name of your firewall in security.yml
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);

        // If the firewall name is not main, then the set value would be instead:
        // $this->get('session')->set('_security_XXXFIREWALLNAMEXXX', serialize($token));
        $this->session->set('_security_'.$this->firewallName, serialize($token));

        // Fire the login event manually
        $event = new InteractiveLoginEvent($request, $token);
        $this->eventDispatcher->dispatch($event, 'security.interactive_login');

        return true;
    }

    /**
     * @Mutation()
     */
    public function logout(): bool
    {
        $this->tokenStorage->setToken(null);

        $this->session->remove('_security_'.$this->firewallName);

        return true;
    }
}
