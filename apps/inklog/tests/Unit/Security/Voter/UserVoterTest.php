<?php declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\User;
use App\Security\Voter\UserVoter;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use stdClass;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserVoterTest extends TestCase
{
    private function tokenFor(?User $user = null): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    private function forceId(object $entity, int $id): void
    {
        $rp = new ReflectionProperty($entity, 'id');
        $rp->setValue($entity, $id);
    }

    private function createSecurityMock(array $rolesGranted): Security
    {
        $security = $this->createMock(Security::class);
        $security->method('isGranted')
            ->willReturnCallback(function ($role) use ($rolesGranted) {
                return in_array($role, $rolesGranted, true);
            });

        return $security;
    }

    public function testNotConnectedIsNotGranted(): void
    {
        // No user
        $security = $this->createMock(Security::class);
        $voter = new UserVoter($security);

        $notAUser = $this->createMock(UserInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($notAUser);

        // Not User cannot delete
        $userToDelete = new User();
        $userToDelete->setRoles(['ROLE_USER']);
        $this->forceId($userToDelete, 5);
        $res = $voter->vote($token, $userToDelete, [UserVoter::DELETE]);
        self::assertSame(VoterInterface::ACCESS_DENIED, $res, 'Unconnected must not be able to delete anybody');
    }

    public function testUserIsNotGranted(): void
    {
        // User is not ADMIN
        $security = $this->createSecurityMock(['ROLE_USER']);
        $voter = new UserVoter($security);

        $currentUser = new User();
        $this->forceId($currentUser, 10);
        $token = $this->tokenFor($currentUser);

        // User cannot delete user
        $userToDelete = new User();
        $userToDelete->setRoles(['ROLE_USER']);
        $this->forceId($userToDelete, 5);
        $res = $voter->vote($token, $userToDelete, [UserVoter::DELETE]);
        self::assertSame(VoterInterface::ACCESS_DENIED, $res, 'User must not be able to delete anybody');
    }

    public function testSuperAdminCannotDeleteHimself(): void
    {
        // Admin user cannot delete himself
        $security = $this->createSecurityMock(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
        $voter = new UserVoter($security);

        $currentUser = new User();
        $this->forceId($currentUser, 10);
        $currentUser->setRoles(['ROLE_SUPER_ADMIN']);
        $token = $this->tokenFor($currentUser);

        // Admin cannot delete himself
        $res = $voter->vote($token, $currentUser, [UserVoter::DELETE]);
        self::assertSame(VoterInterface::ACCESS_DENIED, $res, 'Admin must not be able to delete himself');
    }

    public function testAdminCannotDeleteHimself(): void
    {
        // Admin user cannot delete himself
        $security = $this->createSecurityMock(['ROLE_ADMIN']);
        $voter = new UserVoter($security);

        $currentUser = new User();
        $this->forceId($currentUser, 10);
        $currentUser->setRoles(['ROLE_ADMIN']);
        $token = $this->tokenFor($currentUser);

        // Admin cannot delete himself
        $res = $voter->vote($token, $currentUser, [UserVoter::DELETE]);
        self::assertSame(VoterInterface::ACCESS_DENIED, $res, 'Admin must not be able to delete himself');
    }

    public function testAdminCannotDeleteSuperAdmin(): void
    {
        // User is ADMIN
        $security = $this->createSecurityMock(['ROLE_ADMIN']);
        $voter = new UserVoter($security);

        $currentUser = new User();
        $this->forceId($currentUser, 10);
        $token = $this->tokenFor($currentUser);

        // Admin cannot delete Super admin
        $superAdminToDelete = new User();
        $superAdminToDelete->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_USER']);
        $this->forceId($superAdminToDelete, 5);
        $res = $voter->vote($token, $superAdminToDelete, [UserVoter::DELETE]);
        self::assertSame(VoterInterface::ACCESS_DENIED, $res, 'Admin must not be able to delete Super admin');
    }

    public function testAdminCanDeleteRegularUserAndOtherAdmin(): void
    {
        // User is ADMIN
        $security = $this->createSecurityMock(['ROLE_ADMIN']);
        $voter = new UserVoter($security);

        $currentUser = new User();
        $this->forceId($currentUser, 10);
        $token = $this->tokenFor($currentUser);

        // Admin can delete user
        $userToDelete = new User();
        $this->forceId($userToDelete, 5);
        $res = $voter->vote($token, $userToDelete, [UserVoter::DELETE]);
        self::assertSame(VoterInterface::ACCESS_GRANTED, $res, 'Admin must be able to delete other simple user');

        // Admin can delete other admin
        $adminToDelete = new User();
        $this->forceId($userToDelete, 4);
        $adminToDelete->setRoles(['ROLE_ADMIN']);
        $res = $voter->vote($token, $adminToDelete, [UserVoter::DELETE]);
        self::assertSame(VoterInterface::ACCESS_GRANTED, $res, 'Admin must be able to delete other admin');
    }

    public function testSuperAdminCanDeleteOtherUser(): void
    {
        // User is SUPER ADMIN
        $security = $this->createSecurityMock(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
        $voter = new UserVoter($security);

        $currentUser = new User();
        $this->forceId($currentUser, 10);
        $token = $this->tokenFor($currentUser);

        // Super admin can delete user
        $userToDelete = new User();
        $userToDelete->setRoles(['ROLE_USER']);
        $this->forceId($userToDelete, 5);
        $res = $voter->vote($token, $userToDelete, [UserVoter::DELETE]);
        self::assertSame(VoterInterface::ACCESS_GRANTED, $res, 'Super Admin must be able to delete user');

        // Super admin can delete admin
        $adminToDelete = new User();
        $adminToDelete->setRoles(['ROLE_ADMIN']);
        $this->forceId($adminToDelete, 6);
        $res = $voter->vote($token, $adminToDelete, [UserVoter::DELETE]);
        self::assertSame(VoterInterface::ACCESS_GRANTED, $res, 'Super Admin must be able to delete admin');

        // Super admin can delete other super admin
        $superAdminToDelete = new User();
        $superAdminToDelete->setRoles(['ROLE_SUPER_ADMIN']);
        $this->forceId($superAdminToDelete, 7);
        $res = $voter->vote($token, $superAdminToDelete, [UserVoter::DELETE]);
        self::assertSame(VoterInterface::ACCESS_GRANTED, $res, 'Super Admin must be able to delete ohter super admin');
    }

    public function testVoterHaveToAbstainOnAnotherAttribute(): void
    {
        // User is SUPER ADMIN
        $security = $this->createSecurityMock(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
        $voter = new UserVoter($security);

        $currentUser = new User();
        $this->forceId($currentUser, 10);
        $token = $this->tokenFor($currentUser);

        // Super admin can delete user
        $userToDelete = new User();
        $userToDelete->setRoles(['ROLE_USER']);
        $this->forceId($userToDelete, 5);
        $res = $voter->vote($token, $userToDelete, ['OTHER_ATTRIBUTE']);
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $res, 'Voter should not vote for other attribute');
    }

    public function testVoterHaveToAbstainOnAnotherSubject(): void
    {
        // User is SUPER ADMIN
        $security = $this->createSecurityMock(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
        $voter = new UserVoter($security);

        $currentUser = new User();
        $this->forceId($currentUser, 10);
        $token = $this->tokenFor($currentUser);

        // Super admin can delete user
        $subjectToDelete = new stdClass();
        $res = $voter->vote($token, $subjectToDelete, [UserVoter::DELETE]);
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $res, 'Voter should not vote for other subject');
    }
}
