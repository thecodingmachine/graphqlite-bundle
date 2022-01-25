<?php
namespace TheCodingMachine\GraphQLite\Bundle\Controller\GraphQL;


use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use TheCodingMachine\GraphQLite\Annotations\Mutation;

class LoginController
{

    /**
     * @var UserProviderInterface
     */
    private $userProvider;
    /**
     * @var UserPasswordHasherInterface
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

    public function __construct(UserProviderInterface $userProvider, UserPasswordHasherInterface $passwordEncoder, TokenStorageInterface $tokenStorage, SessionInterface $session, EventDispatcherInterface $eventDispatcher, string $firewallName)
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
    public function login(string $userName, string $password, Request $request): UserInterface
    {
        try {
            $user = $this->userProvider->loadUserByIdentifier($userName);
        } catch (UserNotFoundException $e) {
            // FIXME: should we return null instead???
            throw InvalidUserPasswordException::create($e);
        }

        if (!$user instanceof PasswordAuthenticatedUserInterface) {
            throw new RuntimeException('$user has to implements ' . PasswordAuthenticatedUserInterface::class);
        }

        if (!$this->passwordEncoder->isPasswordValid($user, $password)) {
            throw InvalidUserPasswordException::create();
        }

        // User and passwords are valid. Let's login!

        // Handle getting or creating the user entity likely with a posted form
        // The third parameter "main" can change according to the name of your firewall in security.yml
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);

        // If the firewall name is not main, then the set value would be instead:
        // $this->get('session')->set('_security_XXXFIREWALLNAMEXXX', serialize($token));
        $this->session->set('_security_'.$this->firewallName, serialize($token));

        // Fire the login event manually
        $event = new InteractiveLoginEvent($request, $token);
        // @phpstan-ignore-next-line BC for Symfony4
        $this->eventDispatcher->dispatch($event, 'security.interactive_login');

        return $user;
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
