<?php declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private UserRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = static::getContainer()->get('doctrine')->getManager();
        $this->repo = $this->em->getRepository(User::class);

        $tool = new SchemaTool($this->em);
        $classes = $this->em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($classes);
        $tool->createSchema($classes);
    }

    public function testUpgradePasswordPersistsNewHash(): void
    {
        $u = (new User())
            ->setEmail('user@test.fr')
            ->setUsername('user');
        $u->setPassword('old_hash');

        $this->em->persist($u);
        $this->em->flush();
        $this->em->clear();

        $user = $this->repo->findByEmail('user@test.fr');
        self::assertNotNull($user, 'User must exist before upgrade');

        $this->repo->upgradePassword($user, 'new_hash');
        $this->em->clear();

        $reloaded = $this->repo->findByEmail('user@test.fr');
        self::assertNotNull($reloaded);
        self::assertSame('new_hash', $reloaded->getPassword(), 'Password must be upgraded and persisted');
    }

    public function testUpgradePasswordThrowsOnUnsupportedUser(): void
    {
        $notOurUser = new class implements \Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface {
            public function getPassword(): ?string { return null; }
        };

        $this->expectException(\Symfony\Component\Security\Core\Exception\UnsupportedUserException::class);

        $this->repo->upgradePassword($notOurUser, 'irrelevant');
    }
}
