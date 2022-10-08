<?php

namespace App\Tests\functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\TestBrowserToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Session\SessionFactory;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    private SessionInterface $session;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    /**
     * This replicates static::createClient()->loginUser()
     * Inspect that method, as there are additional checks there that may be necessary for your use case.
     * The magic here is tracking an internal $session object that can be updated as needed.
     */
    protected function loginUser(UserInterface $user, string $firewallContext = 'main'): static
    {
        $token     = new TestBrowserToken($user->getRoles(), $user, $firewallContext);
        $container = static::getContainer();

        /**
         * @var TokenStorageInterface $tokenStorage
         */
        $tokenStorage = $container->get('security.untracked_token_storage');
        $tokenStorage->setToken($token);
        /**
         * @var SessionFactory $sessionFactory
         */
        $sessionFactory = $container->get('session.factory');
        $this->session  = $sessionFactory->createSession();
        $this->setLoginSessionValue('_security_'.$firewallContext, serialize($token));

        $domains = array_unique(array_map(function (Cookie $cookie) {
            return $cookie->getName() === $this->session->getName() ? $cookie->getDomain() : '';
        }, $this->client->getCookieJar()->all())) ?: [''];

        foreach ($domains as $domain) {
            $cookie = new Cookie($this->session->getName(), $this->session->getId(), null, null, $domain);
            $this->client->getCookieJar()->set($cookie);
        }

        return $this;
    }

    /**
     * @param mixed $value
     */
    protected function setLoginSessionValue(string $name, $value): self
    {
        if (isset($this->session)) {
            $this->session->set($name, $value);
            $this->session->save();

            return $this;
        }
        throw new \LogicException('loginUser() must be called to initialize session');
    }
}
