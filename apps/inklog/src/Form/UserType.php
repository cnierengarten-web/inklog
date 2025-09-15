<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints;

class UserType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'email',
                'attr' => [
                    'placeholder' => 'votreemail@nomdedomaine.com',
                    'class' => 'form-control',
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'hash_property_path' => 'password',
                    'constraints' => $options['is_creation']
                        ? [
                            new Constraints\NotBlank([],'Le mot de passe doit être renseigné'),
                            new Constraints\PasswordStrength([],Constraints\PasswordStrength::STRENGTH_VERY_STRONG),
                        ]
                        : [],
                ],
                'second_options' => ['label' => 'Confirmation'],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'required' => $options['is_creation'],
            ])
            ->add('username', TextType::class, [
                'label' => 'Nom d\'utilisateur',
                'attr' => [
                    'placeholder' => 'Miss alice...',
                    'class' => 'form-control',
                ],
            ]);


        /** @var User $editedUser */
        $editedUser = $options['data'];
        $newUser = $editedUser->getId() === null;
        $editingSuper = !$newUser && in_array('ROLE_SUPER_ADMIN', $editedUser->getRoles(), true);
        $editingSelf = $this->security->getUser()?->getId() === $editedUser->getId();
        $currentIsSuper = $this->security->isGranted('ROLE_SUPER_ADMIN');


        if ($editingSelf                            // User is not allowed to change his own roles
            || ($editingSuper && !$currentIsSuper)  // only superAdmin can edit superAdmin user
        ) {
            $builder->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'expanded' => true,
                'multiple' => true,
                'empty_data' => [],
                'choices' => [
                    'Administrateur' => 'ROLE_ADMIN',
                    'Super Admininistrateurs' => 'ROLE_SUPER_ADMIN',
                ],
                'disabled' => true,
                'help' => 'Un utilisateur aura toujours le rôle "Utilisateur"',
            ]);
        } else {
            if ($currentIsSuper) {
                // only super admin can create super admin
                $choices = [
                    'Administrateur' => 'ROLE_ADMIN',
                    'Super Administrateurs' => 'ROLE_SUPER_ADMIN',
                ];
            } else {
                $choices = [
                    'Administrateur' => 'ROLE_ADMIN',
                ];
            }
            $builder->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'expanded' => true,
                'multiple' => true,
                'choices' => $choices,
                'empty_data' => [],
                'help' => 'Un utilisateur aura toujours le rôle "Utilisateur"',
            ]);
        }

        $builder = $this->postSubmitEvent($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_creation' => false,
        ]);
    }

    private function postSubmitEvent(FormBuilderInterface $builder): FormBuilderInterface
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $e) {
            /** @var User $edited */
            $edited = $e->getData();
            $form = $e->getForm();

            $current = $this->security->getUser();
            if (!$edited instanceof User || !$current instanceof UserInterface) {
                return;
            }

            $editingSelf = $edited->getId() !== null && $edited->getId() === $current->getId();
            $editedIsSuper = in_array('ROLE_SUPER_ADMIN', $edited->getRoles(), true);

            if ($editingSelf || $editedIsSuper && !$this->security->isGranted('ROLE_SUPER_ADMIN')) {
                // only super admin can create super admin
                if ($form->has('roles')) {
                    $form->get('roles')->addError(
                        new FormError(
                            "Modification des rôles non autorisée pour cet utilisateur."
                        )
                    );
                }
            }
        });

        return $builder;
    }
}
