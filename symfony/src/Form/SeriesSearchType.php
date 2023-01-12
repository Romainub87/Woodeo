<?php

namespace App\Form;

use App\Entity\SeriesSearch;
use App\Entity\Genre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SeriesSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', null, [
                'label' => false,
            ])
            ->add('genre', EntityType::class, [
                'class' => Genre::class,
                'label' => false,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'placeholder' => 'Tous genres ⌄',
            ])
            ->add('Trier', ChoiceType::class, [
                'label' => false,
                'required' => false,
                'placeholder' => 'Ordre alphabétique ⌄',
                'choices'  => [
                    '⏱️ Plus récents' => 1,
                    '⏳ Plus ancients' => 2,
                    '❤️ Meilleurs notes' => 3,
                    '❤️‍🩹 Pires notes' => 4,
                ],
            ])
            #->add('date', DateType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SeriesSearch::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
