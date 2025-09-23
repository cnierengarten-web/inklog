<?php declare(strict_types=1);

namespace App\Entity\Blog;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\Blog\ArticleRepository;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Table(
    name: 'blog_article',
)]
#[ORM\Index('idx_article_published_at', ['published_at'])]
#[ORM\Index('idx_article_created_at', ['created_at'])]
#[ORM\Index('idx_article_updated_at', ['updated_at'])]
#[ORM\UniqueConstraint(name: 'UNIQ_BLOG_ARTICLE_SLUG', fields: ['slug'])]
#[Vich\Uploadable]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/articles/preview',
            normalizationContext: ['groups' => ['article:read', 'article:preview:read']]
        ),
        new GetCollection(
            uriTemplate: '/articles',
            normalizationContext: ['groups' => ['article:read', 'article:collection:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['article:read', 'article:item:read']]
        ),
    ],
    normalizationContext: ['groups' => ['article:read']],
    order: ['publishedAt' => 'DESC'],
    paginationEnabled: true,
    paginationItemsPerPage: 10,
)]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[ApiProperty(identifier: false)]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Groups(['article:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups('article:collection:read')]
    private ?string $summary = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le contenu est obligatoire')]
    #[Assert\Length(
        min: 10,
        minMessage: 'Le contenu doit contenir au moins {{ limit }} caractères'
    )]
    #[Groups(['article:item:read'])]
    private ?string $content = null;

    #[Gedmo\Slug(fields: ['title'])]
    #[ORM\Column(length: 255)]
    #[Groups(['article:preview:read', 'article:collection:read'])]
    #[ApiProperty(identifier: true)]
    private ?string $slug = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $createdAt = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['article:read'])]
    private ?DateTimeImmutable $publishedAt = null;

    #[Vich\UploadableField(
        mapping: 'article_image',
        fileNameProperty: 'imageName',
        size: 'imageSize',
        mimeType: 'imageMimeType',
        originalName: 'imageOriginalName',
    )]
    #[Assert\File(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        maxSizeMessage: 'L’image ne doit pas dépasser {{ limit }}.',
        mimeTypesMessage: 'Formats autorisés : JPEG, PNG, WEBP, GIF.'
    )]
    #[Assert\Image(
        minWidth: 300,
        minHeight: 300,
        detectCorrupted: true,
        minWidthMessage: 'Largeur minimale : {{ min_width }}px.',
        minHeightMessage: 'Hauteur minimale : {{ min_height }}px.',
        corruptedMessage: 'Fichier image corrompu.'
    )]
    #[Ignore] // Ignore imageFile for serialization
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $imageSize = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageMimeType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageOriginalName = null;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'articles')]
    #[ORM\JoinTable(name: 'article_tag')]
    #[Assert\Count(
        max: 10,
        maxMessage: 'Vous ne pouvez pas sélectionner plus de {{ limit }} tags'
    )]
    private Collection $tags;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'articles')]
    #[ORM\JoinTable(name: 'article_category')]
    #[Assert\Count(
        min: 1,
        max: 5,
        minMessage: 'Vous devez sélectionner au moins une catégorie',
        maxMessage: 'Vous ne pouvez pas sélectionner plus de {{ limit }} catégories',
    )]
    private Collection $categories;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['article:read'])]
    private ?User $author = null;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->categories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = trim($title);

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): static
    {
        $this->summary = $summary;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }


    // ------------ Image getter/setter -----
    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $file): static
    {
        $this->imageFile = $file;
        if (null !== $file) {
            // force change detect - used when only the file change
            $this->updatedAt = new DateTimeImmutable();
        }

        return $this;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): static
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getImageSize(): ?int
    {
        return $this->imageSize;
    }

    public function setImageSize(?int $imageSize): static
    {
        $this->imageSize = $imageSize;

        return $this;
    }

    public function getImageMimeType(): ?string
    {
        return $this->imageMimeType;
    }

    public function setImageMimeType(?string $imageMimeType): static
    {
        $this->imageMimeType = $imageMimeType;

        return $this;
    }

    public function getImageOriginalName(): ?string
    {
        return $this->imageOriginalName;
    }

    public function setImageOriginalName(?string $imageOriginalName): static
    {
        $this->imageOriginalName = $imageOriginalName;

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            if (!$tag->getArticles()->contains($this)) {
                $tag->getArticles()->add($this);
            }
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        if ($this->tags->removeElement($tag)) {
            $tag->getArticles()->removeElement($this);
        }

        return $this;
    }


    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            if (!$category->getArticles()->contains($this)) {
                $category->getArticles()->add($this);
            }
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        if ($this->categories->removeElement($category)) {
            $category->getArticles()->removeElement($this);
        }

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        if ($this->author === $author) {
            return $this;
        }

        $this->author?->getArticles()->removeElement($this);

        $this->author = $author;
        if (null !== $author && !$author->getArticles()->contains($this)) {
            $author->getArticles()->add($this);
        }

        return $this;
    }

    public function isPublished(?DateTimeImmutable $now = null): bool
    {
        $now ??= new DateTimeImmutable();

        return null !== $this->publishedAt && $this->publishedAt <= $now;
    }


    public function publish(?DateTimeImmutable $now = null): static
    {
        $this->publishedAt = $now ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return $this;
    }

    public function unpublish(): static
    {
        $this->publishedAt = null;

        return $this;
    }

    public function __serialize(): array
    {
        $data = (array)$this;
        unset($data["\0".self::class."\0imageFile"]);

        return $data;
    }

    #[Groups(['article:item:read', 'article:collection:read'])]
    public function getImageUrl(): ?string
    {
        return $this->imageName ? '/medias/articles/'.$this->imageName : null;
    }
}
