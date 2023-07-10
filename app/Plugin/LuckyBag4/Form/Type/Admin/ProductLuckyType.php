<?php

namespace Plugin\LuckyBag4\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

use Plugin\LuckyBag4\Entity\ProductLucky;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Eccube\Repository\ProductClassRepository;
use Eccube\Repository\MemberRepository;
use Eccube\Repository\PluginRepository;
use Plugin\LuckyBag4\Repository\LuckyBag4ConfigRepository;

class ProductLuckyType extends AbstractType
{


    /**
     * @var ValidatorInterface
     */
    protected $validator;
    /**
     * @var ProductClassRepository
     */
    protected $productClassRepository;
    /**
     * @var LuckyBag4ConfigRepository
     */
    protected $luckyBag4ConfigRepository;
    /**
     * @var MemberRepository
     */
    protected $memberRepository;

    /**
     * @var PluginRepository
     */
    protected $pluginRepository;


    /**
     * ProductClassEditType constructor.
     *
     * @param ValidatorInterface $validator
     * @param ProductClassRepository $productClassRepository
     * @param LuckyBag4ConfigRepository $luckyBag4ConfigRepository
     * @param MemberRepository $memberRepository
     * @param PluginRepository $pluginRepository
     */
    public function __construct(
        ValidatorInterface $validator,
        ProductClassRepository $productClassRepository,
        LuckyBag4ConfigRepository $luckyBag4ConfigRepository,
        MemberRepository $memberRepository,
        PluginRepository $pluginRepository
    ) {
        $this->validator = $validator;
        $this->productClassRepository = $productClassRepository;
        $this->luckyBag4ConfigRepository = $luckyBag4ConfigRepository;
        $this->memberRepository = $memberRepository;
        $this->pluginRepository = $pluginRepository;
    }
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('checked', CheckboxType::class, [
                'label' => false,
                'required' => false,
                'mapped' => false,
            ])
            ->add('product_code', TextType::class, [
                'required' => false,
            ])
            ->add('lucky_rate', NumberType::class, [
                'required' => false,
            ])
            ->add('add_point', CheckboxType::class, [
                'label' => false,
                'required' => false,
            ])
        ;
        $this->setCheckbox($builder);
        $this->addValidations($builder);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProductLucky::class,
        ]);
    }

    protected function setCheckbox(FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $data = $event->getData();
            if (!$data instanceof ProductLucky) {
                return;
            }
            if ($data->getId() && $data->isVisible()) {
                $form = $event->getForm();
                $form['checked']->setData(true);
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();
            $data->setVisible($form['checked']->getData() ? true : false);
        });
    }

    protected function addValidations(FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $form->getData();
            if (!$form['checked']->getData()) {
                // チェックがついていない場合はバリデーションしない.
                return;
            }
            $errors = $this->validator->validate($data->getProductCode(), [
                new Assert\NotBlank()
            ]);
            $this->addErrors('product_code', $form, $errors);

            $isPoint = $data->isAddPoint();
            if ($isPoint){
                $errors = $this->validator->validate($data->getProductCode(), [
                    new Assert\NotBlank(),
                    new Assert\Regex([
                        'pattern' => "/^\d+$/u",
                        'message' => 'form_error.numeric_only',
                    ]),
                ]);
                $this->addErrors('product_code', $form, $errors);
            }else{
                $ProductClasses = $this->productClassRepository->createQueryBuilder('pc')
                    ->where('pc.code = :product_code')
                    ->setParameter('product_code', $data->getProductCode())
                    ->andWhere('pc.visible = true')
                    ->getQuery()->getResult();

                if (empty($ProductClasses)){
                    $form['product_code']->addError(new FormError(trans('plg_lucky_bag4.form_error.product_code_error')));
                }

                // if MarketPlacePlugin
                $Plugin = $this->pluginRepository->findOneBy(['code'=>'MarketPlace4']);
                if (!empty($Plugin)){
                    $Admin = $this->memberRepository->find(1);

                    $ProductClasses = $this->productClassRepository->createQueryBuilder('pc')
                        ->where('pc.code = :product_code')
                        ->setParameter('product_code', $data->getProductCode())
                        ->andWhere('pc.visible = true')
                        ->andWhere('pc.Member = :Admin')->setParameter('Admin', $Admin)
                        ->getQuery()->getResult();

                    if (empty($ProductClasses)){
                        $form['product_code']->addError(new FormError(trans('plg_lucky_bag4.form_error.product_code_admin_error')));
                    }
                }



                $isStock = false;
                $isLucky = false;
                if (count($ProductClasses)>0) $isLucky = true;
                $Config = $this->luckyBag4ConfigRepository->find(1);
                foreach ($ProductClasses as $pc){
                    if ($pc->isStockUnlimited() || $pc->getStock()>0){
                        $isStock = true;
                    }
                    if ($pc->getSaleType() != $Config->getSaleType()){
                        $isLucky = false;
                    }
                }

                if ($isLucky){
                    $form['product_code']->addError(new FormError(trans('plg_lucky_bag4.form_error.product_code_lucky_product_error')));
                }

                if (count($ProductClasses)>0 && !$isStock && $data->getLuckyRate()>0){
                    $form['lucky_rate']->addError(new FormError(trans('plg_lucky_bag4.form_error.lucky_rate_stock_error')));
                }
            }

            $errors = $this->validator->validate($data->getLuckyRate(), [
                new Assert\NotBlank(),
                new Assert\Regex([
                    'pattern' => "/^\d+$/u",
                    'message' => 'form_error.numeric_only',
                ]),
            ]);

            $this->addErrors('lucky_rate', $form, $errors);

            if ($data->getLuckyRate()>100){
                $form['lucky_rate']->addError(new FormError(trans('plg_lucky_bag4.form_error.lucky_rate_max_error')));
            }

        });
    }

    protected function addErrors($key, FormInterface $form, ConstraintViolationListInterface $errors)
    {
        foreach ($errors as $error) {
            $form[$key]->addError(new FormError($error->getMessage()));
        }
    }
}
