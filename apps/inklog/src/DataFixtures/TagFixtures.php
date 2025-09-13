<?php declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

class TagFixtures extends Fixture implements FixtureGroupInterface
{

    public const string TAG_BALLADE = 'tag-ballade';
    public const string TAG_CREATURES = 'tag-creature';
    public const string TAG_IA = 'user-ia';

    public function __construct(
        private readonly Generator $faker,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $manager->persist($this->createTag('Ballades', self::TAG_BALLADE));
        $manager->persist($this->createTag('Créatures', self::TAG_CREATURES));
        $manager->persist($this->createTag('IA', self::TAG_IA));

        $this->faker->seed(2543);
        for ($i = 0; $i < 4; $i++) {
            $name = ucfirst($this->faker->unique()->words(2, true));
            $manager->persist($this->createTag($name));
        }
        $manager->flush();
    }

    private function createTag(string $name, ?string $reference = null): Tag
    {

        $tag = new Tag();
        $tag->setName($name);
        if (isset($reference)) {
            $this->addReference($reference, $tag);
        }

        return $tag;
    }

    public static function getGroups(): array
    {
        return ['dev', 'test'];
    }
}
