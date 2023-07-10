<?php
/*
* Plugin Name : DeliveryDate4
*
* Copyright (C) BraTech Co., Ltd. All Rights Reserved.
* http://www.bratech.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\DeliveryDate4\Form\Extension;

use Eccube\Form\Type\Admin\ProductClassType;
use Plugin\DeliveryDate4\Repository\ConfigRepository;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ProductClassExtension extends AbstractTypeExtension
{
    private $configRepository;

    public function __construct(
        ConfigRepository $configRepository
    ) {
        $this->configRepository = $configRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('delivery_date_days', Type\NumberType::class, [
                'label' => trans('deliverydate.common.1'),
                'required' => false,
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => "/^\d+$/u",
                        'message' => 'form.type.numeric.invalid'
                        ]),
                    ],
            ]);

        $builder
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                /** @var \Eccube\Entity\ProductClass $data */
                $ProductClass = $event->getData();
                /** @var \Symfony\Component\Form\Form $form */
                $form = $event->getForm();
                if (is_null($ProductClass)) {
                    return;
                }
                if (is_null($ProductClass->getId())) {
                    $Config = $this->configRepository->findOneBy(['name' => 'default_deliverydate_days']);
                    if($Config){
                        $form['delivery_date_days']->setData($Config->getValue());
                    }
                }
            });
    }

    public function getExtendedType()
    {
        return ProductClassType::class;
    }

    public function getExtendedTypes(): iterable
    {
        return [ProductClassType::class];
    }
}
