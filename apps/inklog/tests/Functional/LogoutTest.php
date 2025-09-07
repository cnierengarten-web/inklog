<?php declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class LogoutTest extends WebTestCase
{
    private function createUser(string $email, array $roles = [], string $plain = 'pass'): User
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $u = (new User())
            ->setEmail($email)
            ->setUsername(preg_replace('/@.*/','',$email) ?: $email)
            ->setRoles($roles);
        $u->setPassword($hasher->hashPassword($u, $plain));

        $em->persist($u);
        $em->flush();

        return $u;
    }

    private function csrf(string $id): string
    {
        $tm = static::getContainer()->get(CsrfTokenManagerInterface::class);

        return (string) $tm->getToken($id);
    }

    public function testGetLogoutDoesNotLogout(): void
    {
        $client = static::createClient();
        $user = $this->createUser('userlogout@test.fr');

        $client->loginUser($user);

        $client->request('GET', '/logout');
        self::assertTrue(
            in_array($client->getResponse()->getStatusCode(), [404, 405], true),
            'GET /logout should not be allowed'
        );

        $client->request('GET', '/profile');
        self::assertResponseIsSuccessful();
    }

    public function testPostLogoutWithCsrfLogsOut(): void
    {
        $client = static::createClient();
        $user = $this->createUser('userlogout2@test.fr');

        $client->loginUser($user);

        $client->request('POST', '/logout', [
            '_csrf_token' => $this->csrf('logout'),
        ]);

        self::assertResponseRedirects('/');

        $client->followRedirect();
        $client->request('GET', '/profile');
        self::assertResponseRedirects('/login');
    }
}
