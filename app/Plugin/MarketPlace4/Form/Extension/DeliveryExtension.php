<?php

namespace Plugin\MarketPlace4\Form\Extension;

use Eccube\Form\Type\PriceType;
use Eccube\Form\Type\Admin\DeliveryFeeType;
use Eccube\Form\Type\Admin\DeliveryType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormBuilderInterface;


class DeliveryExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('free_fee_all', PriceType::class, [
                'label' => false,
                'required' => false,
                'mapped' => false,
            ])
            ->add('add_fee_all', PriceType::class, [
                'label' => false,
                'required' => false,
                'mapped' => false,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getExtendedType()
    {
        return DeliveryType::class;
    }
}
