<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class, array(
                'label' => 'Fornavn',
            ))
            ->add('lastName', TextType::class, array(
                'label' => 'Etternavn',
            ))
            ->add('email', EmailType::class, array(
                'label' => 'E-post',
            ))
            ->add('phone', TextType::class, array(
                'label' => 'Telefon',
            ))
            ->add(
                'password',
                RepeatedType::class,
                array(
                    'type' => PasswordType::class,
                    'first_options' => array('label' => 'Passord (minst 8 tegn)'),
                    'second_options' => array('label' => 'Gjenta Password'),
                    'options' => array(
                        'attr' => array(
                            'pattern' => '.{8,}',
                        ),
                    ),
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\User',
        ));
    }
}
