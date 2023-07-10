<?php

namespace Plugin\ALIJSettlementIV\Form\Type\Admin;

use Plugin\ALIJSettlementIV\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Ip;

class ConfigType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('siteId', TextType::class, [
            'constraints' => [
                new NotBlank(),
            ],
        ])
            ->add('sitePassword', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
            ])->add('serverIP', TextType::class, [
                'constraints' => [
                    new Ip(),
                ],
            ])->add('useCont', CheckboxType::class, array(
                    'label' => '有効',
                    'required' => false,
                )
            )->add('useQuick', CheckboxType::class, array(
                    'label' => '有効',
                    'required' => false,
                )
            ) ->add('useAmountCheck',CheckboxType::class,array(
                    'label' => '有効',
                    'required' => false,
                )
            ) ->add('useTest',CheckboxType::class,array(
                'label' => '有効',
                'required' => false,
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
        ]);
    }
}