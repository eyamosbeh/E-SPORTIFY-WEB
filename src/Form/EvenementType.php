<?php

namespace App\Form;

use App\Entity\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'événement'
            ])
            ->add('date', DateType::class, [
                'label' => 'Date de l\'événement',
                'widget' => 'single_text',
                'attr' => ['class' => 'js-datepicker'],
                'required' => true,
            ])
            ->add('localisation', TextType::class, [
                'label' => 'Localisation'
            ])
            ->add('capacite', IntegerType::class, [
                'label' => 'Capacité'
            ]);

        // Ajouter un listener pour transformer la valeur null
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if (empty($data['date'])) {
                $data['date'] = (new \DateTime())->format('Y-m-d');
                $event->setData($data);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}