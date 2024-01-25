<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Product;
use App\Entity\Category;
use App\Entity\Nutrition;
use App\Entity\Promotion;
use App\Form\NutritionType;
use Symfony\Component\Form\FormEvent;
use App\Repository\CategoryRepository;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('price')
            ->add('imageFile', FileType::class, [
                'label' => 'Image (fichier JPG)',
                'mapped' => false, 
                'required' => false, 
                'constraints' => [
                    new File([
                        'maxSize' => '1024k', 
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg'
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG ou PNG)',
                    ]),
                ],
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => function (Category $category) {
                    return $category->getParent() ? "-- {$category->getName()}" : $category->getName();
                },
                'multiple' => true,
                'expanded' => true,
                'query_builder' => function (CategoryRepository $cr) {
                    return $cr->createQueryBuilder('c')
                        ->orderBy('c.parent', 'ASC'); 
                },
                'by_reference' => false,
            ])
            ->add('promotion', EntityType::class, [
                'class' => Promotion::class,
'choice_label' => 'rising',
            ])
            ->add('nutrition', NutritionType::class)
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $product = $event->getData();
                $form = $event->getForm();
    
                if ($product && $product->getId() === null) {
                    // Formulaire d'ajout
                    $form->add('createdAt', HiddenType::class, [
                        'data' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                        'mapped' => false,
                    ]);
                } else {
                    // Formulaire d'édition
                    $form->add('updatedAt', HiddenType::class, [
                        'data' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                        'mapped' => false,
                    ]);
                }
            });
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
