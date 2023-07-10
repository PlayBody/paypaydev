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


class DeliveryFeeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('free_fee', PriceType::class, [
                'label' => false,
            ])
            ->add('add_fee', PriceType::class, [
                'label' => false,
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
        return DeliveryFeeType::class;
    }
}
