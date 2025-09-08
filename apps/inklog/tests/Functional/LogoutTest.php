<?php declare(strict_types=1);

namespace App\Tests\Functional;

final class LogoutTest extends AbstractWebTestCase
{
    public function testGetLogoutDoesNotLogout(): void
    {
        $client = static::createClient();
        $user = $this->createUser('userNotLogout@test-logout.fr');

        $client->loginUser($user);

        $client->request('GET', '/logout');
        self::assertResponseStatusCodeSame(405);

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
