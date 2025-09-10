<?php declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testEmailIsNormalized(): void
    {
        $u = new User();
        $u->setEmail("  Admin@TesT.FR  ");
        self::assertSame('admin@test.fr', $u->getEmail());
        self::assertSame('admin@test.fr', $u->getUserIdentifier(), 'User identifier have to be the normalized email');
    }

    public function testRolesAlwaysContainUserAndAreUnique(): void
    {
        $u = new User();
        $u->setRoles(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ADMIN']);
        $roles = $u->getRoles();

        self::assertContains('ROLE_USER', $roles);
        self::assertContains('ROLE_ADMIN', $roles);
        self::assertSame(array_values(array_unique($roles)), $roles, 'Roles have to be unique');
    }


    public function testRolesEnsureUserWhenEmpty(): void
    {
        $u = (new User())->setRoles([]);
        self::assertContains('ROLE_USER', $u->getRoles());
    }

    public function testPasswordIsObfuscatedInSerialize(): void
    {
        $u = new User();
        $u->setEmail('x@y.z');
        $u->setPassword('HASHED_DB_VALUE');

        $data = $u->__serialize();

        $key = "\0".User::class."\0password";
        self::assertArrayHasKey($key, $data);
        self::assertSame(hash('crc32c', 'HASHED_DB_VALUE'), $data[$key]);
        self::assertNotSame('HASHED_DB_VALUE', $data[$key], 'Original hash does not be in session.');
    }
}
