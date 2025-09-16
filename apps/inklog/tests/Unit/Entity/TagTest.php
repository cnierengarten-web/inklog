<?php declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Tag;
use PHPUnit\Framework\TestCase;

final class TagTest extends TestCase
{
    public function testToString(): void
    {
        $tag = new Tag();
        self::assertSame('', $tag->__toString(), 'ToString return empty string if name is not defined or empty');

        $tag->setName('Not   Empty  ');
        self::assertSame('Not Empty', $tag->__toString(), 'toString return tag name');

    }

}
