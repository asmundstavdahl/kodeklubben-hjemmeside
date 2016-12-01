<?php

namespace CodeClubBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClubType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, array(
            'label' => 'Navn',
            ))
            ->add('region', TextType::class, array(
                'label' => 'Region',
            ))
            ->add('email', EmailType::class, array(
                'label' => 'E-post',
            ))
            ->add('facebook', TextType::class, array(
                'label' => 'facbook.com/',
                'required' => false,
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }

    public function getBlockPrefix()
    {
        return 'code_club_bundle_club_type';
    }
}
