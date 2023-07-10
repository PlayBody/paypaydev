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

namespace Plugin\ProductPlus\Form\Extension\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\Admin\ProductType;
use Plugin\ProductPlus\Entity\ProductItem;
use Plugin\ProductPlus\Entity\ProductData;
use Plugin\ProductPlus\Repository\ProductItemRepository;
use Plugin\ProductPlus\Repository\ProductItemOptionRepository;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ProductExtension extends AbstractTypeExtension
{
    private $eccubeConfig;
    private $productItemRepository;
    private $productItemOptionRepository;

    public function __construct(
            EccubeConfig $eccubeConfig,
            ProductItemRepository $productItemRepository,
            ProductItemOptionRepository $productItemOptionRepository
            )
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->productItemRepository = $productItemRepository;
        $this->productItemOptionRepository = $productItemOptionRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $ProductItems = $this->productItemRepository->getList();

        foreach($ProductItems as $ProductItem){
            $constraints = [];
            if($ProductItem->getIsRequired()){
                $required = true;
                $constraints[] = new Assert\NotBlank();
            }else{
                $required = false;
            }
            $inputType = $ProductItem->getInputType();
            $name = 'productplus_'.$ProductItem->getId();
            $label = $ProductItem->getName();
            if($inputType == ProductItem::TEXT_TYPE || $inputType == ProductItem::TEXTAREA_TYPE){
                $options = [
                    'label' => $label,
                    'required' => $required,
                    'mapped' => false,
                    'constraints' => $constraints,
                ];
                switch($inputType){
                    case ProductItem::TEXT_TYPE:
                        $form_type = Type\TextType::class;
                        break;
                    case ProductItem::TEXTAREA_TYPE:
                        $form_type = Type\TextareaType::class;
                        $options['attr']['rows'] = 6;
                        break;
                    default:
                        break;
                }
                $builder
                    ->add(
                        $name,
                        $form_type,
                        $options
                    );
            }elseif($inputType == ProductItem::DATE_TYPE){
                $builder
                    ->add($name, Type\DateType::class, [
                        'label' => $label,
                        'required' => $required,
                        'input' => 'datetime',
                        'mapped' => false,
                        'widget' => 'single_text',
                        'format' => 'yyyy-MM-dd',
                        'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                        'attr' => [
                            'class' => 'datetimepicker-input',
                            'data-target' => '#'.$builder->getName().'_'.$name,
                            'data-toggle' => 'datetimepicker',
                        ],
                    ]);
            }elseif($inputType == ProductItem::IMAGE_TYPE){
                $options = [
                    'label' => $label,
                    'required' => $required,
                    'multiple' => true,
                    'mapped' => false,
                    'attr' => ['data-item-id' => $ProductItem->getId(),
                        'class' => 'productplus_file_upload',
                        ],
                ];
                $builder
                    ->add(
                        $name,
                        Type\FileType::class,
                        $options
                    );
                $image_options = ['entry_type' => Type\HiddenType::class,
                            'prototype' => true,
                            'mapped' => false,
                            'allow_add' => true,
                            'allow_delete' => true,
                            'required' => $required,
                    ];
                $builder
                    ->add(
                        $name . '_images', Type\CollectionType::class,
                        $image_options
                    );
                $builder
                    ->add(
                        $name . '_add_images', Type\CollectionType::class,[
                            'entry_type' => Type\HiddenType::class,
                            'prototype' => true,
                            'mapped' => false,
                            'allow_add' => true,
                            'allow_delete' => true,
                    ]);
                $builder
                    ->add(
                        $name . '_delete_images', Type\CollectionType::class,[
                            'entry_type' => Type\HiddenType::class,
                            'prototype' => true,
                            'mapped' => false,
                            'allow_add' => true,
                            'allow_delete' => true,
                    ]);
            }elseif($inputType >= ProductItem::SELECT_TYPE){
                switch($inputType){
                    case ProductItem::SELECT_TYPE:
                        $expanded = false;
                        $multiple = false;
                        break;
                    case ProductItem::RADIO_TYPE:
                        $expanded = true;
                        $multiple = false;
                        break;
                    case ProductItem::CHECKBOX_TYPE:
                        $expanded = true;
                        $multiple = true;
                        break;
                    default:
                        break;
                }
                $Options = $this->productItemOptionRepository->getList($ProductItem);
                $choices = [];
                foreach($Options as $Option){
                    $choices[$Option->getId()] = $Option->getText();
                }
                $options = [
                    'label' => $label,
                    'required' => $required,
                    'mapped' => false,
                    'expanded' => $expanded,
                    'multiple' => $multiple,
                    'choices' => array_flip($choices),
                    'constraints' => $constraints,
                    ];
                if($inputType == ProductItem::SELECT_TYPE){
                    $options['placeholder'] = trans('productplus.form.productitem.choice.empty');
                }else{
                    $options['placeholder'] = false;
                }
                $builder
                    ->add(
                        $name,
                        Type\ChoiceType::class,
                        $options
                    );
            }
        }

        $builder
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use($ProductItems) {
                $Product = $event->getData();
                /** @var \Symfony\Component\Form\Form $form */
                $form = $event->getForm();
                if (is_null($Product)) {
                    return;
                }
                $ProductDatas = $Product->getProductDatas();
                if(!is_null($ProductDatas) && count($ProductDatas) > 0){
                    foreach($ProductDatas as $ProductData){
                        $ProductItem = $ProductData->getProductItem();
                        if(in_array($ProductItem,$ProductItems)){
                            $product_item_id = $ProductItem->getId();
                            if($form->has('productplus_' . $product_item_id)){
                                $value = $ProductData->getValue();
                                if($value){
                                    if($ProductItem->getInputType() == ProductItem::IMAGE_TYPE){
                                        $form['productplus_'.$product_item_id.'_images']->setData($value);
                                    }elseif($ProductItem->getInputType() == ProductItem::DATE_TYPE){
                                        $value = new \DateTime($value);
                                        $form['productplus_'.$product_item_id]->setData($value);
                                    }else{
                                        $form['productplus_'. $product_item_id]->setData($value);
                                    }
                                }
                            }
                        }
                    }
                }
            });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use($ProductItems){
            $form = $event->getForm();
            $Product = $event->getData();
            $saveImgDir = $this->eccubeConfig['eccube_save_image_dir'];
            $tempImgDir = $this->eccubeConfig['eccube_temp_image_dir'];

            if (is_array($ProductItems)) {
                foreach ($ProductItems as $ProductItem) {
                    if($ProductItem->getInputType() == ProductItem::IMAGE_TYPE){
                        $this->validateFilePath($form->get('productplus_'.$ProductItem->getId().'_delete_images'), [$saveImgDir, $tempImgDir]);
                        $this->validateFilePath($form->get('productplus_'.$ProductItem->getId().'_add_images'), [$tempImgDir]);
                        if($ProductItem->getIsRequired()){
                            $images = $form['productplus_'.$ProductItem->getId().'_images']->getData();
                            $add_images = $form['productplus_'.$ProductItem->getId().'_add_images']->getData();
                            $delete_images = $form['productplus_'.$ProductItem->getId().'_delete_images']->getData();
                            if(count($images) + count($add_images) - count($delete_images) <= 0){
                                $form['productplus_'.$ProductItem->getId()]->addError(new FormError(trans('productplus.productitem.error.image.required')));
                            }
                        }
                    }
                }
            }
        });
    }

   /**
    * 指定された複数ディレクトリのうち、いずれかのディレクトリ以下にファイルが存在するかを確認。
    *
    * @param $form FormInterface
    * @param $dirs array
    */
    private function validateFilePath($form, $dirs)
    {
        foreach ($form->getData() as $fileName) {
            $fileInDir = array_filter($dirs, function ($dir) use ($fileName) {
                $filePath = realpath($dir.'/'.$fileName);
                $topDirPath = realpath($dir);
                return strpos($filePath, $topDirPath) === 0 && $filePath !== $topDirPath;
            });
            if (!$fileInDir) {
                $imageFormName = $form->getName();
                $imageFormName = str_replace('_add_images', '', $imageFormName);
                $imageFormName = str_replace('_delete_images', '', $imageFormName);
                $form->getRoot()[$imageFormName]->addError(new FormError(trans('productplus.type.image.path.error')));
            }
        }
    }

    public function getExtendedType()
    {
        return ProductType::class;
    }

    public function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }
}
