<?php declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Cookie;

final class LoginTest extends AbstractWebTestCase
{
    private function setTargetPath(string $path, KernelBrowser $client): void
    {
        $session = static::getContainer()->get('session.factory')->createSession();

        $key = sprintf('_security.%s.target_path', self::FIREWALL);
        $session->set($key, $path);
        $session->save();
        $client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));
    }

    public static function provideFailedLogins(): iterable
    {
        yield 'wrong-password' => [
            'email' => 'userWrongPassword@testlogin.fr',
            'storedPassword' => 'pass',
            'usedPassword' => 'wrongPassword',
            'createUser' => true,
            'csrf' => null,
            'errorMessage' => 'Invalid credentials.',
        ];

        yield 'unknown-user' => [
            'email' => 'unknownUser@testlogin.fr',
            'storedPassword' => 'pass',
            'usedPassword' => 'pass',
            'createUser' => false,
            'csrf' => null,
            'errorMessage' => 'Invalid credentials.',
        ];

        yield 'wrong-csrf' => [
            'email' => 'wrong-csrf@testlogin.fr',
            'storedPassword' => 'pass',
            'usedPassword' => 'pass',
            'createUser' => false,
            'csrf' => 'wrong-csrf',
            'errorMessage' => 'Invalid CSRF token.',
        ];
    }

    public static function provideSuccessfulLogins(): iterable
    {
        yield 'role-admin_redirect-to-dashboard' => [
            'storedEmail' => 'admin@testlogin.fr',
            'loginEmail' => 'admin@testlogin.fr',
            'roles' => ['ROLE_ADMIN'],
            'password' => 'pass',
            'expectedRedirect' => '/admin/dashboard',
            'targetPath' => null,
        ];

        yield 'role-admin_redirect-to-target-path' => [
            'storedEmail' => 'adminToProfile@testlogin.fr',
            'loginEmail' => 'adminToProfile@testlogin.fr',
            'roles' => ['ROLE_ADMIN'],
            'password' => 'pass',
            'expectedRedirect' => '/author',
            'targetPath' => '/author',
        ];

        yield 'role-user_redirect-to-profile' => [
            'storedEmail' => 'user@testlogin.fr',
            'loginEmail' => 'user@testlogin.fr',
            'roles' => [],
            'password' => 'pass',
            'expectedRedirect' => '/author',
            'targetPath' => null,
        ];

        yield 'case-insensitive-email' => [
            'storedEmail' => 'userTestCase@testlogin.fr',
            'loginEmail' => 'userTESTCASE@testlogin.fr',
            'roles' => [],
            'password' => 'pass',
            'expectedRedirect' => '/author',
            'targetPath' => null,
        ];
    }

    public static function provideAlreadyLogged(): iterable
    {
        yield 'role-admin_already-logged' => [
            'storedEmail' => 'adminAlreadyLogged@testlogin.fr',
            'roles' => ['ROLE_ADMIN'],
            'password' => 'pass',
            'expectedRedirect' => '/admin/dashboard',
        ];

        yield 'role-user_already-logged' => [
            'storedEmail' => 'userAlreadyLogged@testlogin.fr',
            'roles' => [],
            'password' => 'pass',
            'expectedRedirect' => '/author',
        ];
    }

     #[DataProvider('provideFailedLogins')]
    public function testFailedLogin(
        string $email,
        string $storedPassword,
        string $usedPassword,
        bool $createUser,
        ?string $csrf,
        string $errorMessage,
    ): void {
        $client = static::createClient();
        if ($createUser) {
            $this->createUser($email, [], $storedPassword);
        }

        $this->login($client, $email, $usedPassword, $csrf);

        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertSelectorTextContains('.alert.alert-danger', $this->t($errorMessage, 'security'));
    }

    #[DataProvider('provideSuccessfulLogins')]
    public function testSuccessLoginRedirect(
        string $storedEmail,
        string $loginEmail,
        array $roles,
        string $password,
        string $expectedRedirect,
        ?string $targetPath,
    ): void {
        $client = static::createClient();
        $this->createUser($storedEmail, $roles, $password);

        if ($targetPath) {
            $this->setTargetPath($targetPath, $client);
        }

        $this->login($client, $loginEmail, $password);

        self::assertResponseRedirects($expectedRedirect);
    }

    #[DataProvider('provideAlreadyLogged')]
    public function testAlreadyLoggedRedirection(
        string $storedEmail,
        array $roles,
        string $password,
        string $expectedRedirect,
    ): void {
        $client = static::createClient();
        $user = $this->createUser($storedEmail, $roles, $password);
        $client->loginUser($user);

        $client->request('GET', '/login');
        self::assertResponseRedirects($expectedRedirect);
    }


    public function testRememberMe(): void
    {
        $client = static::createClient();
        $this->createUser('adminRememberMe@testlogin.fr', ['ROLE_ADMIN']);

        $this->login($client, 'adminRememberMe@testlogin.fr', 'pass', null, true);

        $cookies = $client->getCookieJar()->all();

        self::ensureKernelShutdown();
        $clientRemember = static::createClient();
        $sessionName = static::getContainer()->get('session.factory')->createSession()->getName();
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $sessionName) {
                continue; // on ignore la session
            }
            $clientRemember->getCookieJar()->set($cookie);
        }

        // profile is accessible with remember me
        $clientRemember->request('GET', '/author');
        self::assertResponseIsSuccessful();

        // admin need reconnect : IS_AUTHENTICATED_FULLY required
        $clientRemember->request('GET', '/admin/dashboard');
        self::assertResponseRedirects('/login', null, 'User remembered only must be redirected to /login for /admin');
    }

    public function testAllowedToSwitchUser(): void
    {
        $client = static::createClient();
        $this->createUser('superAdmin@testlogin.fr', ['ROLE_SUPER_ADMIN']);
        $this->createUser('user-switch@testlogin.fr');
        $this->login($client, 'superAdmin@testlogin.fr');

        self::assertResponseRedirects('/admin/dashboard');
        $client->followRedirect();

        $client->request('GET', '/author?_switch_user=user-switch@testlogin.fr');
        self::assertResponseRedirects('/author');
        $client->followRedirect();
        self::assertSelectorTextContains('[data-testid="profile-username"]', 'user-switch testlogin.fr');
    }

    public function testNotAllowedToSwitchUser(): void
    {
        $client = static::createClient();
        $this->createUser('userDontSwitch@testlogin.fr');
        $this->createUser('user-switch-test@testlogin.fr');


        $this->login($client, 'userDontSwitch@testlogin.fr');
        self::assertResponseRedirects('/author');
        $client->followRedirect();

        $client->request('GET', '/author?_switch_user=user-switch-test@testlogin.fr');
        self::assertResponseStatusCodeSame(403, 'User only cannot use switch');
    }
}
