<?php

namespace Raddit\AppBundle\Form;

use Doctrine\ORM\EntityRepository;
use Eo\HoneypotBundle\Form\Type\HoneypotType;
use Raddit\AppBundle\Entity\Forum;
use Raddit\AppBundle\Entity\ForumCategory;
use Raddit\AppBundle\Entity\Theme;
use Raddit\AppBundle\Form\Type\MarkdownType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class ForumType extends AbstractType {
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker) {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        if ($options['honeypot']) {
            $builder->add('email', HoneypotType::class);
        }

        $editing = $builder->getData() && $builder->getData()->getId() !== null;

        $builder
            ->add('name', TextType::class)
            ->add('title', TextType::class)
            ->add('description', TextareaType::class)
            ->add('sidebar', MarkdownType::class, [
                'label' => 'label.sidebar',
            ])
            ->add('category', EntityType::class, [
                'class' => ForumCategory::class,
                'choice_label' => 'name',
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('fc')
                        ->orderBy('fc.name', 'ASC');
                },
                'required' => false,
                'placeholder' => 'forum_form.uncategorized_placeholder',
            ])
        ;

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $builder->add('featured', CheckboxType::class, [
                'required' => false,
            ]);
        }

        $builder->add('submit', SubmitType::class, [
            'label' => $editing ? 'forum_form.save' : 'forum_form.create',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Forum::class,
            'label_format' => 'forum_form.%name%',
            'honeypot' => true,
        ]);

        $resolver->setAllowedTypes('honeypot', ['bool']);
    }
}
