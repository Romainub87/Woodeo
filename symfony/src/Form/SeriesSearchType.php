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
            ->add('titre')
            ->add('genre', EntityType::class, [
                'class' => Genre::class,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'placeholder' => 'Tous',
            ])
            ->add('Trier', ChoiceType::class, [
                'choices'  => [
                    'Aucun' => 0,
                    'Les plus récents d abord' => 1,
                    'Les plus anciens d abord' => 2,
                    'Les meilleurs notations d abord' => 3,
                    'Les pires notations d abord' => 4,
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
