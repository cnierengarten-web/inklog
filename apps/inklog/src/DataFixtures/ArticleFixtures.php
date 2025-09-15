<?php declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Blog\Article;
use App\Entity\Blog\Category;
use App\Entity\Tag;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

class ArticleFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    /** @var Tag[] */
    private array $tagsPool;

    /** @var Category[] */
    private array $categoriesPool;

    public function __construct(
        private readonly Generator $faker,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->tagsPool = [
            $this->getReference(TagFixtures::TAG_IA, Tag::class),
            $this->getReference(TagFixtures::TAG_CREATURES, Tag::class),
            $this->getReference(TagFixtures::TAG_BALLADE, Tag::class),
        ];

        $this->categoriesPool = [
            $this->getReference(CategoryFixtures::CAT_INFO, Category::class),
            $this->getReference(CategoryFixtures::CAT_MONTAGE, Category::class),
            $this->getReference(CategoryFixtures::CAT_CONCEPT_ART, Category::class),
        ];

        $this->faker->seed(8524);

        $alice = $this->getReference(UserFixtures::USER_ALICE, User::class);
        $manager->persist($this->createArticle($alice, 'Création d\'une petite créature aquatique'));
        $manager->persist($this->createArticle($alice, 'Quelle IA pour développer ?'));
        for ($i = 0; $i < 5; $i++) {
            $manager->persist($this->createArticle($alice));
        }

        $albert = $this->getReference(UserFixtures::USER_ALBERT, User::class);
        $manager->persist($this->createArticle($albert, 'Premier montage'));
        for ($i = 0; $i < 10; $i++) {
            $manager->persist($this->createArticle($albert));
        }

        $manager->flush();
    }

    private function createArticle(User $author, ?string $title = null): Article
    {
        $article = new Article();

        if (!isset($title)) {
            $title = ucfirst($this->faker->unique()->words(2, true));
        }
        $article->setTitle($title);
        $summary = $this->faker->optional(0.4)->paragraph(2);
        $article->setSummary($summary);

        $content = $this->faker->paragraphs(5, true);
        $article->setContent($content);

        $this->faker->setDefaultTimezone('UTC');
        $publishedAt = $this->faker->optional(0.3)->dateTimeBetween('-2 years', '+2 months');
        $article->setPublishedAt($publishedAt ? DateTimeImmutable::createFromMutable($publishedAt) : null);

        $article->setAuthor($author);

        $tags = $this->faker->randomElements(
            $this->tagsPool,
            $this->faker->numberBetween(0, 3),
        );
        foreach ($tags as $t) {
            $article->addTag($t);
        }

        $cats = $this->faker->randomElements(
            $this->categoriesPool,
            $this->faker->numberBetween(1, 3),
        );

        foreach ($cats as $c) {
            $article->addCategory($c);
        }

        return $article;
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            TagFixtures::class,
            CategoryFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['dev', 'test'];
    }
}
