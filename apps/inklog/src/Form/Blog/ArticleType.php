<?php declare(strict_types=1);

namespace App\Form\Blog;

use App\Entity\Blog\Article;
use App\Entity\Blog\Category;
use App\Entity\Tag;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'Saisissez le titre de l\'article...',
                ],
            ])
            ->add('summary', TextareaType::class, [
                'label' => 'Résumé',
                'required' => false,
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'Résumé de l\'article',
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'empty_data' => '',
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'Rédigez votre article...',
                ],
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Image de couverture',
                'required' => false,
                'download_uri' => false,
                'image_uri' => false,
                'allow_delete' => true,
                'delete_label' => 'Supprimer l\'image',
                'asset_helper' => true,


            ])
            ->add('publishedAt', DateTimeType::class, [
                'label' => 'Date de publication',
                'required' => false,
                'widget' => 'single_text',
                'help' => 'Laissez vide pour un brouillon',
                'input' => 'datetime_immutable',
                'html5' => true,
                'with_seconds' => true,
                'model_timezone' => 'UTC',
                'view_timezone' => 'Europe/Paris',
                'attr' => ['step' => 60],
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'label' => 'Tags',
                'help' => 'Sélectionner un ou plusieurs tags',
                'by_reference' => false,
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('t')->orderBy('t.name', 'ASC'),
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'label' => 'Catégories',
                'help' => 'Sélectionnez une ou  plusieurs catégories',
                'by_reference' => false,
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('c')->orderBy('c.name', 'ASC'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,

        ]);
    }

    public function __serialize(): array
    {
        $data = (array)$this;
        unset($data["\0".self::class."\0imageFile"]);

        return $data;
    }
}
