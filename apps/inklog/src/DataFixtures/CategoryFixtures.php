<?php declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Blog\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

class CategoryFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private readonly Generator $faker,
    ) {
    }

    public const string CAT_MONTAGE = 'cat-montage';
    public const string CAT_CONCEPT_ART = 'cat-concept-art';
    public const string CAT_INFO = 'cat-info';

    public function load(ObjectManager $manager): void
    {
        $this->faker->seed(8524);
        $manager->persist(
            $this->createCategory('Montage vidéo', self::CAT_MONTAGE)
        );
        $manager->persist(
            $this->createCategory('Concept Art', self::CAT_CONCEPT_ART)
        );
        $manager->persist(
            $this->createCategory('Informatiques', self::CAT_INFO)
        );

        for ($i = 0; $i < 4; $i++) {
            $name = ucfirst($this->faker->unique()->words(2, true));
            $manager->persist($this->createCategory($name));
        }
        $manager->flush();
    }

    private function createCategory(string $name, ?string $reference = null): Category
    {
        $category = new Category();
        $category->setName($name);
        $category->setDescription(
            $this->faker->optional(0.6)->paragraph(1)
        );
        if (isset($reference)) {
            $this->addReference($reference, $category);
        }

        return $category;
    }

    public static function getGroups(): array
    {
        return ['dev', 'test'];
    }
}
