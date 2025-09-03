<?php declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
       $manager->persist($this->createUser('user1@test.fr','user1','Mister User One', []));
       $manager->persist($this->createUser('user2@test.fr','user2','Miss User Two', []));
       $manager->persist($this->createUser('admin@test.fr','admin','Admin', ['ROLE_ADMIN']));
       $manager->persist($this->createUser('superadmin@test.fr','superadmin','THE super admin', ['ROLE_SUPER_ADMIN']));

        $manager->flush();
    }

    private function createUser(string $email, string $password, string $username, array $roles) : User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setRoles($roles);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $password)
        );

        return $user;
    }

    public static function getGroups(): array
    {
        return ['dev', 'test'];
    }
}
