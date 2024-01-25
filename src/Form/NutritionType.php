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
            ->add('energy')
            ->add('saturatedFattyAcid')
            ->add('sugar')
            ->add('salt')
            ->add('proteins')
            ->add('fibers')
            ->add('lipids')
            ->add('carbohydrates')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Nutrition::class,
        ]);
    }
}
