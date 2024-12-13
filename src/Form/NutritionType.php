<?php

namespace App\Form;

use App\Entity\Nutrition;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NutritionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Energie')
            ->add('saturatedFattyAcid', null, [
                'label' => 'Acides gras saturés'
            ])
            ->add('sugar', null, [
                'label' => 'Sucres'
            ])
            ->add('salt', null, [
                'label' => 'Sels'
            ])
            ->add('proteins', null, [
                'label' => 'Protéïnes'
            ])
            ->add('fibers', null, [
                'label' => 'Fibres'
            ])
            ->add('lipids', null, [
                'label' => 'Lipides'
            ])
            ->add('carbohydrates', null, [
                'label' => 'Carbohydrates'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Nutrition::class,
        ]);
    }
}
