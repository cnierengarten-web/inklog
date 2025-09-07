<?php declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminAccessTest extends WebTestCase
{
    private function createUser(string $email, array $roles, string $plainPassword = 'pass'): User
    {
        $em     = static::getContainer()->get('doctrine')->getManager();
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $u = new User();
        $u->setEmail($email);
        $u->setUsername(preg_replace('/@.*/', '', $email) ?: $email);
        $u->setRoles($roles);
        $u->setPassword($hasher->hashPassword($u, $plainPassword));

        $em->persist($u);
        $em->flush();

        return $u;
    }

    public function testAnonymousIsRedirectedToLogin(): void
    {
        $client = static::createClient();

        $client->request('GET', '/admin/dashboard');

        self::assertResponseRedirects('/login');
    }

    public function testUserGets403OnAdmin(): void
    {
        $client = static::createClient();

        $user = $this->createUser('user@test.fr', []); // implicit ROLE_USER
        $client->loginUser($user); // fully authenticated (not remember-me)

        $client->request('GET', '/admin/dashboard');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminGets200OnAdmin(): void
    {
        $client = static::createClient();

        $admin = $this->createUser('admin@test.fr', ['ROLE_ADMIN']);
        $client->loginUser($admin);

        $client->request('GET', '/admin/dashboard');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists("[data-testid=dashboard]");
    }
}
