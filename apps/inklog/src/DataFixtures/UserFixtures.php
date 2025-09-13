<?php declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements FixtureGroupInterface
{
    public const string USER_ALICE = 'user-alice';
    public const string USER_ALBERT = 'user-albert';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly Generator $faker,
    ) {
    }

    public function load(ObjectManager $manager): void
    {

        $manager->persist($this->createUser('alice@test.fr', 'Miss Alice', [], self::USER_ALICE));
        $manager->persist($this->createUser('albert@test.fr', 'Mister Albert', [], self::USER_ALBERT));
        $manager->persist($this->createUser('admin@test.fr', 'Admin', ['ROLE_ADMIN']));
        $manager->persist($this->createUser('superadmin@test.fr', 'THE super admin', ['ROLE_SUPER_ADMIN']));

        $this->faker->seed(1337);
        for ($i = 0; $i < 4; $i++) {
            $manager->persist(
                $this->createUser($this->faker->safeEmail(), $this->faker->unique()->userName(), [])
            );
        }
        $manager->flush();
    }

    private function createUser(
        string $email,
        string $username,
        array $roles,
        ?string $reference = null
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setRoles($roles);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, 'password')
        );
        if (isset($reference)) {
            $this->addReference($reference, $user);
        }

        return $user;
    }

    public static function getGroups(): array
    {
        return ['dev', 'test'];
    }
}
