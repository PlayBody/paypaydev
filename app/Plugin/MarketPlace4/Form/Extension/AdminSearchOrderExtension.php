<?php

namespace Plugin\MarketPlace4\Form\Extension;

use Eccube\Entity\Shipping;
use Eccube\Form\Type\PriceType;
use Eccube\Form\Type\Shopping\ShippingType;
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
use Plugin\MarketPlace4\Entity\DeliveryType;
use Eccube\Form\Type\Admin\SearchOrderType;
use Symfony\Component\Validator\Constraints as Assert;
use Plugin\MarketPlace4\Repository\DeliveryTypeRepository;
use Eccube\Repository\MemberRepository;

class AdminSearchOrderExtension extends AbstractTypeExtension
{
    /**
     * @var DeliveryTypeRepository
     */
    protected $deliveryTypeRepository;
    /**
     * @var MemberRepository
     */
    protected $memberRepository;

    public function __construct(
        DeliveryTypeRepository $deliveryTypeRepository,
        MemberRepository $memberRepository

    )
    {
        $this->deliveryTypeRepository = $deliveryTypeRepository;
        $this->memberRepository = $memberRepository;
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
                $choieList = [];
                foreach ($deliveryTypes as $deliveryType){
                    $choieList[$deliveryType->getName()] = $deliveryType->getId();
                }

                $form = $event->getForm();
                $form->add(
                    'DeliveryType',
                    ChoiceType::class,
                    [
                        'required' => false,
                        'label' => '配送タイプ',
                        //'class' => 'Plugin\MarketPlace4\Entity\DeliveryType',
                        //'choice_label' => 'name',
                        //'expanded' => false,
                        'choices' => ['common.select' => '__unselected'] + $choieList,
                        'placeholder' => false,
                    ]
                );

            }
        );
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $Members = $this->memberRepository->findAll();

                $form = $event->getForm();
                $form->add(
                    'Member',
                    EntityType::class,
                    [
                        'required' => false,
                        'label' => '配送タイプ',
                        'class' => 'Eccube\Entity\Member',
                        'choice_label' => 'name',
                        'expanded' => true,
                        'multiple' => true,
                        'choices' => $Members,
                        'placeholder' => false,
                        'constraints' => [
                            new NotBlank(),
                        ],
                    ]
                );

            }
        );

    }

    public function getExtendedType()
    {
        return SearchOrderType::class;
    }
}
