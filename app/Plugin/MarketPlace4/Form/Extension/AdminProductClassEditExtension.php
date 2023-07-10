<?php

namespace Plugin\MarketPlace4\Form\Extension;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Eccube\Form\Type\Admin\ProductClassEditType;
use Eccube\Form\Type\Admin\MemberType;
use Symfony\Component\Validator\Constraints as Assert;

use Eccube\Request\Context;

use Eccube\Form\DataTransformer;
use Eccube\Entity\Member;

class AdminProductClassEditExtension extends AbstractTypeExtension
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;
    /**
     * ProductListWhereCustomizer constructor.
     *
     * @param Context $context
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(Context $context,EntityManagerInterface $entityManager)
    {
        $this->context = $context;
        $this->entityManager = $entityManager;
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $Member = $builder->getData();
        if (empty($Member)) $Member = $this->context->getCurrentUser();
        $member_id = $Member->getId();
//        if ($Member->getId()) {
//            $market_place4_email = $Member->getMarketPlace4Email();
//            $market_place_matome = $Member->isMarketPlaceMatome();
//        }
        $builder
            ->add(
                $builder
                    ->create('Member', HiddenType::class, [
                        'eccube_form_options' => [
                            'auto_render'=>true,
                        ],
                    ])
                ->addModelTransformer(new DataTransformer\EntityToIdTransformer($this->entityManager, '\Eccube\Entity\Member'))

            )
        ;

    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getExtendedType()
    {
        return ProductClassEditType::class;
    }
}
