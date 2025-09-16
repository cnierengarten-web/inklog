<?php declare(strict_types=1);

namespace App\Tests\Unit\Entity\Blog;

use App\Entity\Blog\Category;
use PHPUnit\Framework\TestCase;

final class CategoryTest extends TestCase
{
    public function testToString(): void
    {
        $cat = new Category();
        self::assertSame('', $cat->__toString(), 'ToString return empty string if name is not defined or empty');

        $cat->setName('Not   Empty ');
        self::assertSame('Not Empty', $cat->__toString(), 'toString return Category name');

    }

}
