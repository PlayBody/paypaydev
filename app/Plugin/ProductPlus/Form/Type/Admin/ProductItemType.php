<?php
/*
* Plugin Name : ProductPlus
*
* Copyright (C) BraTech Co., Ltd. All Rights Reserved.
* http://www.bratech.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\ProductPlus\Form\Type\Admin;

use Plugin\ProductPlus\Entity\ProductItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductItemType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $arrTYPE = [];
        $arrTYPE[ProductItem::TEXT_TYPE] = trans('productplus.form.productitem.choice.text');
        $arrTYPE[ProductItem::TEXTAREA_TYPE] = trans('productplus.form.productitem.choice.textarea');
        $arrTYPE[ProductItem::SELECT_TYPE] = trans('productplus.form.productitem.choice.select');
        $arrTYPE[ProductItem::RADIO_TYPE] = trans('productplus.form.productitem.choice.radio');
        $arrTYPE[ProductItem::CHECKBOX_TYPE] = trans('productplus.form.productitem.choice.checkbox');
        $arrTYPE[ProductItem::IMAGE_TYPE] = trans('productplus.form.productitem.choice.image');
        $arrTYPE[ProductItem::DATE_TYPE] = trans('productplus.form.productitem.choice.date');

        $builder
            ->add('name', Type\TextType::class, [
                'label' => trans('productplus.form.productitem.label.name'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('input_type', Type\ChoiceType::class, [
                'label' => trans('productplus.form.productitem.label.input_type'),
                'required' => true,
                'expanded' => false,
                'multiple' => false,
                'choices' => array_flip($arrTYPE),
                'placeholder' => false,
            ])
            ->add('is_required', Type\CheckboxType::class, [
                'label' => trans('productplus.form.productitem.label.is_required'),
                'required' => false,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Plugin\ProductPlus\Entity\ProductItem',
        ]);
    }
}
