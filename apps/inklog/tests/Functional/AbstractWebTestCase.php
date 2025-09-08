<?php declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractWebTestCase extends WebTestCase
{
    protected const string FIREWALL = 'main';

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    protected function t(string $message, string $domain = 'messages'): string
    {
        $container = static::getContainer();

        if ($container->has(TranslatorInterface::class)) {
            return $container->get(TranslatorInterface::class)->trans($message, [], $domain);
        }
        if ($container->has('translator')) {
            /** @var TranslatorInterface $tr */
            $tr = $container->get('translator');

            return $tr->trans($message, [], $domain);
        }

        return $message;
    }

    protected function createUser(string $email, array $roles = [], string $plain = 'pass'): User
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = (new User())
            ->setEmail($email)
            ->setUsername(preg_replace('/@/', ' ', $email) ?: $email)
            ->setRoles($roles);
        $user->setPassword($hasher->hashPassword($user, $plain));

        $em->persist($user);
        $em->flush();

        return $user;
    }
    protected function login(
        KernelBrowser $client,
        string $email,
        string $password = 'pass',
        ?string $csrf = null,
        bool $remember = false,
    ): void {
        $client->request('GET', '/login');
        $params = [
            'email' => $email,
            'password' => $password,
            '_csrf_token' => $csrf ?? $this->csrf(),
        ];
        if ($remember) {
            $params['_remember_me'] = 'on';
        }
        $client->request('POST', '/login', $params);
    }

    protected function csrf(string $token = 'authenticate'): string
    {
        return static::getContainer()
            ->get(CsrfTokenManagerInterface::class)
            ->getToken($token)
            ->getValue();
    }
}
