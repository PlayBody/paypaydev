<?php

namespace Plugin\MarketPlace4\Form\Extension;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Form\Type\Shopping\OrderType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Eccube\Repository\CartItemRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Eccube\Service\CartService;
use Eccube\Form\DataTransformer;
use Symfony\Component\Validator\Constraints\NotBlank;


class OrderExtension extends AbstractTypeExtension
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;
    /**
     * @var CartItemRepository
     */
    protected $cartItemRepository;

    /**
     * @var CartService
     */
    protected $cartService;
    /**
     * ShippingType constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param CartItemRepository $cartItemRepository
     * @param CartService $cartService
     */
    public function __construct(EntityManagerInterface $entityManager, CartItemRepository $cartItemRepository, CartService $cartService)
    {
        $this->entityManager = $entityManager;
        $this->cartItemRepository = $cartItemRepository;
        $this->cartService = $cartService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['skip_add_form']) {
            return;
        }
        $Members = $this->cartItemRepository->createQueryBuilder('ci')
            ->select('m.id, m.name')
            ->leftJoin('ci.ProductClass', 'pc')
            ->leftJoin('pc.Member', 'm')
            ->where('ci.Cart = :Cart')->setParameter('Cart', $this->cartService->getCart())
            ->groupBy('m')
            ->getQuery()->getResult()
        ;
        $choiceList = [];
        foreach ($Members as $member){
            $choiceList[$member['name']] = $member['id'];
        }
        $builder->add(
            $builder->create('MessageMember', ChoiceType::class,
                [
                    'required' => false,
                    'label' => '配送タイプ',
                    //'choice_label' => 'name',
                    //'expanded' => false,
                    'choices' => $choiceList,
                    'placeholder' => false,
                ])
            ->addModelTransformer(new DataTransformer\EntityToIdTransformer($this->entityManager, '\Eccube\Entity\Member'))
        );
//        $builder->addEventListener(
//            FormEvents::PRE_SET_DATA,
//            function (FormEvent $event) {
//                $Members = $this->cartItemRepository->createQueryBuilder('ci')
//                    ->select('m')
//                    ->leftJoin('ci.ProductClass', 'pc')
//                    ->leftJoin('pc.Member', 'm')
//                    ->where('ci.Cart = :Cart')->setParameter('Cart', $this->cartService->getCart())
//                    ->groupBy('m')
//                    ->getQuery()->getResult()
//                ;
//                // 配送業者のプルダウンにセット.
//                $form = $event->getForm();
//                $form->add(
//                    'MessageMember',
//                    EntityType::class,
//                    [
//                        'required' => false,
//                        'label' => 'shipping.label.delivery_hour',
//                        'class' => 'Eccube\Entity\Member',
//                        'choice_label' => 'name',
//                        'choices' => $Members,
//                        'placeholder' => false,
//                        'constraints' => [
//                            new NotBlank(),
//                        ],
//                    ]
//                );
//            }
//        );
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getExtendedType()
    {
        return OrderType::class;
    }
}
