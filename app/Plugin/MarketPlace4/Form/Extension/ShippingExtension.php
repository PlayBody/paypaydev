<?php

namespace Plugin\MarketPlace4\Form\Extension;

use Eccube\Entity\Shipping;
use Eccube\Form\Type\PriceType;
use Eccube\Form\Type\Shopping\ShippingType;
use Eccube\Form\Type\Admin\DeliveryType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Delivery;
use Eccube\Entity\DeliveryTime;
use Eccube\Repository\DeliveryFeeRepository;
use Eccube\Repository\DeliveryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Plugin\MarketPlace4\Repository\DeliveryTypeRepository;



class ShippingExtension extends AbstractTypeExtension
{
    /**
     * @var DeliveryTypeRepository
     */
    protected $deliveryTypeRepository;

    /**
     * ShippingType constructor.
     *
     * @param DeliveryTypeRepository $deliveryTypeRepository
     */
    public function __construct(DeliveryTypeRepository $deliveryTypeRepository)
    {
        $this->deliveryTypeRepository = $deliveryTypeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $deliveryTypes = $this->deliveryTypeRepository->findAll();
				
				$Shipping = $event->getData();
                
				$form = $event->getForm();
				
                $form->add(
                    'DeliveryType',
                    EntityType::class,
                    [
                        'required' => false,
                        'label' => 'shipping.label.delivery_hour',
                        'class' => 'Plugin\MarketPlace4\Entity\DeliveryType',
                        'choice_label' => 'name',
                        'expanded' => true,
                        'choices' => $deliveryTypes,
                        'placeholder' => false,
                        'constraints' => [
                            new NotBlank(),
                        ],
                    ]
                );

            }
        );

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var Shipping $Shipping */
            $Shipping = $event->getData();
            $form = $event->getForm();
            /** @var Delivery $DeliveryType */
            $DeliveryType = $form['DeliveryType']->getData();
            if ($DeliveryType) {
                $Shipping->setDeliveryType($DeliveryType);
            } else {
                $Shipping->setDeliveryType($this->deliveryTypeRepository->find(2));
            }

        });

    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getExtendedType()
    {
        return ShippingType::class;
    }
}
