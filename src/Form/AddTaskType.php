<?php

namespace App\Form;

use App\Entity\Tache;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class AddTaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('date_fin')
            ->add('description')
            ->add('photo', FileType::class, [
                'label' => false,
                'mapped'=>false,
                'attr' => array('accept' => 'photo/jpeg,photo/png,photo/jpg'),
                'required' => false,
            ])
            ->add('statut',ChoiceType::class, [
                'placeholder' => 'Sélectionnez le statut',
                'choices'=>[
                    'À faire'=>'À faire',
                    'En cours'=>'En cours',
                    'Effectué'=>'Effectué'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tache::class,
        ]);
    }
}
