<?php declare(strict_types=1);

namespace App\Tests\Functional;

final class AdminAccessTest extends AbstractWebTestCase
{
    public function testAnonymousIsRedirectedToLogin(): void
    {
        $client = self::createClient();

        $client->request('GET', '/admin/dashboard');

        self::assertResponseRedirects('/login');
    }

    public function testUserGets403OnAdmin(): void
    {
        $client = self::createClient();

        $user = $this->createUser('user@test-access.fr'); // implicit ROLE_USER
        $client->loginUser($user); // fully authenticated (not remember-me)

        $client->request('GET', '/admin/dashboard');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminGets200OnAdmin(): void
    {
        $client = self::createClient();

        $admin = $this->createUser('admin@test-access.fr', ['ROLE_ADMIN']);
        $client->loginUser($admin);

        $client->request('GET', '/admin/dashboard');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists("[data-testid=dashboard]");
    }

    public function testRedirectAdminToAdminDashboard(): void
    {
        $client = self::createClient();
        $admin = $this->createUser('adminRedirectToDashboard@test-access.fr', ['ROLE_ADMIN']);
        $client->loginUser($admin);


        $client->request('GET', '/admin');
        self::assertResponseRedirects('/admin/dashboard');
    }
}
