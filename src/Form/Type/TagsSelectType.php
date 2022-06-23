<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\Tag;
use App\Repository\Query\TagFormTypeQuery;
use App\Repository\TagRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select a tag.
 */
class TagsSelectType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'multiple' => true,
            'class' => Tag::class,
            'label' => 'label.tag',
            'allow_create' => false,
            'choice_label' => function (Tag $tag) {
                return $tag->getName();
            },
        ]);

        $resolver->setDefault('query_builder', function (Options $options) {
            return function (TagRepository $repo) use ($options) {
                $query = new TagFormTypeQuery();
                $query->setUser($options['user']);

                return $repo->getQueryBuilderForFormType($query);
            };
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['allow_create']) {
            $view->vars['attr'] = array_merge($view->vars['attr'], [
                'data-create' => 'true',
            ]);
        }
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
