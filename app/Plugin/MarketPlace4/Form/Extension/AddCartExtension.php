<?php

namespace Plugin\MarketPlace4\Form\Extension;

use Eccube\Form\DataTransformer\EntityToIdTransformer;
use Eccube\Form\Type\AddCartType;
use Eccube\Repository\ProductClassRepository;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;


use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\CartItem;
use Eccube\Entity\ProductClass;

use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContext;

class AddCartExtension extends AbstractTypeExtension
{

    protected $productClassRepository;

    protected $doctrine;
    /**
     * ShippingType constructor.
     *
     * @param ProductClassRepository $productClassRepository
     */
    public function __construct(ManagerRegistry $doctrine, ProductClassRepository $productClassRepository)
    {
        $this->doctrine = $doctrine;
        $this->productClassRepository = $productClassRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $Product = $options['product'];

        $ProductClasses = $Product->getProductClasses();
        $classCategories1 = [];
        $classCategories2 = [];

        $default_category1 = -1;
        $default_productClass = null;
        $flag_category1 = [];
        foreach ($ProductClasses as $ProductClass) {
            // stock_find
            if ($ProductClass->isVisible() == false) {
                continue;
            }

            $ClassCategory1 = $ProductClass->getClassCategory1();
            $ClassCategory2 = $ProductClass->getClassCategory2();
            if ($ClassCategory1 && !$ClassCategory1->isVisible()) {
                continue;
            }
            if ($ClassCategory2 && !$ClassCategory2->isVisible()) {
                continue;
            }

            if ($ProductClass->getClassCategory1()) {
                $classCategoryId1 = $ProductClass->getClassCategory1()->getId();
                if (!empty($classCategoryId1)) {
                    if ($ProductClass->getClassCategory2()) {
                            $classCategories1[$ProductClass->getClassCategory1()->getId()] = $ProductClass->getClassCategory1()->getName();
                            $classCategories2[$ProductClass->getClassCategory1()->getId()][$ProductClass->getClassCategory2()->getId()] = $ProductClass->getClassCategory2()->getName();
                    } else {
                        if (!($flag_category1[$ProductClass->getClassCategory1()->getId()])) {
                            $classCategories1[$ProductClass->getClassCategory1()->getId()] = $ProductClass->getClassCategory1()->getName() . ($ProductClass->getStockFind() ? '' : trans('front.product.out_of_stock_label'));
                            $flag_category1[$ProductClass->getClassCategory1()->getId()] = $ProductClass->getStockFind();
                            if ($ProductClass->getStockFind() && $default_category1<0){
                                $default_category1 = $ProductClass->getClassCategory1()->getId();
                                $default_productClass = $ProductClass;
                            }
                        }
                    }
                }
            }
        }
        if ($Product->getStockFind()) {
            $builder
                ->add(
                    $builder
                        ->create('ProductClass', HiddenType::class, [
                            'data_class' => null,
                            'data' => $default_productClass,
                            'constraints' => [
                                new Assert\NotBlank(),
                            ],
                        ])
                        ->addModelTransformer(new EntityToIdTransformer($this->doctrine->getManager(), ProductClass::class))
                );
            if ($Product && $Product->getProductClasses()) {
                if (!is_null($Product->getClassName1()) && is_null($Product->getClassName2())) {
                    $builder->add('classcategory_id1', ChoiceType::class, [
                        'label' => $Product->getClassName1(),
                        'choices' => array_flip($classCategories1),
                        'mapped' => false,
                    ]);
                    $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use( $default_category1) {
                        $data = $event->getData();
                        $form = $event->getForm();
                        $form['classcategory_id1']->setData($default_category1);
                    });
                }
                if (!is_null($Product->getClassName2())) {
                    $builder->add('classcategory_id2', ChoiceType::class, [
                        'label' => $Product->getClassName2(),
                        'choices' => ['common.select' => '__unselected'],
                        'mapped' => false,
                    ]);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getExtendedType()
    {
        return AddCartType::class;
    }
}
