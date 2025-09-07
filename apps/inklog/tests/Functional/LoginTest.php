<?php declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LoginTest extends AbstractWebTestCase
{
    private function setTargetPath(string $path, KernelBrowser $client): void
    {
        $session = static::getContainer()->get('session.factory')->createSession();

        $key = sprintf('_security.%s.target_path', parent::FIREWALL);
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
        ];

        yield 'unknown-user' => [
            'email' => 'unknownUser@testlogin.fr',
            'storedPassword' => 'pass',
            'usedPassword' => 'pass',
            'createUser' => false,
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
            'expectedRedirect' => '/profile',
            'targetPath' => '/profile',
        ];

        yield 'role-user_redirect-to-profile' => [
            'storedEmail' => 'user@testlogin.fr',
            'loginEmail' => 'user@testlogin.fr',
            'roles' => [],
            'password' => 'pass',
            'expectedRedirect' => '/profile',
            'targetPath' => null,
        ];

        yield 'role-admin_insensitive-case' => [
            'storedEmail' => 'userTestCase@testlogin.fr',
            'loginEmail' => 'userTESTCASE@testlogin.fr',
            'roles' => [],
            'password' => 'pass',
            'expectedRedirect' => '/profile',
            'targetPath' => null,
        ];
    }

    /**
     * @dataProvider provideFailedLogins
     */
    public function testFailedLogin(
        string $email,
        string $storedPassword,
        string $usedPassword,
        bool $createUser,
    ): void
    {
        $client = static::createClient();
        if ($createUser) {
            $this->createUser($email, [], $storedPassword);
        }


        $client->request('GET', '/login');
        $client->request('POST', '/login', [
            'email' => $email,
            'password' => $usedPassword,
            '_csrf_token' => $this->csrf(),
        ]);

        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertSelectorTextContains('.alert.alert-danger', $this->t('Invalid credentials.', 'security'));
    }

    /**
     * @dataProvider provideSuccessfulLogins
     */
    public function testSuccessLoginRedirect(
        string $storedEmail,
        string $loginEmail,
        array $roles,
        string $password,
        string $expectedRedirect,
        ?string $targetPath ,
    ): void {
        $client = static::createClient();
        $this->createUser($storedEmail, $roles, $password);

        if ($targetPath) {
            $this->setTargetPath($targetPath, $client);
        }

        $client->request('GET', '/login');
        $client->request('POST', '/login', [
            'email' => $loginEmail,
            'password' => $password,
            '_csrf_token' => $this->csrf(),
        ]);

        self::assertResponseRedirects($expectedRedirect);
    }
}
