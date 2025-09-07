<?php declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class LogoutTest extends AbstractWebTestCase
{
    public function testGetLogoutDoesNotLogout(): void
    {
        $client = static::createClient();
        $user = $this->createUser('userNotLogout@test-logout.fr');

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
        $user = $this->createUser('userWihoutCsfr@test-logout.fr');

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
