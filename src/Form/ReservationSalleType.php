<?php

namespace App\Form;

use App\Entity\ReservationSalle;
use App\Entity\Salle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReservationSalleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('salle', EntityType::class, [
                'class' => Salle::class,
                'choice_label' => 'nom',
                'label' => 'Salle',
                'attr' => ['class' => 'form-control'],
                'required' => true,
                'placeholder' => 'Sélectionnez une salle',
                'constraints' => [
                    new NotBlank([
                        'message' => 'La salle est obligatoire'
                    ])
                ]
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'label' => 'Date de début',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La date de début est obligatoire'
                    ])
                ]
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'label' => 'Date de fin',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La date de fin est obligatoire'
                    ])
                ]
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Annulée' => 'Annulée',
                    'Confirmée' => 'Confirmée',
                    'En attente' => 'En attente'
                ],
                'label' => 'Statut',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le statut est obligatoire'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReservationSalle::class,
        ]);
    }
} 