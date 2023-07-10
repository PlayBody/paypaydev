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

namespace Plugin\ProductPlus;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Plugin\ProductPlus\Entity\ProductItem;
use Plugin\ProductPlus\Entity\ProductData;
use Plugin\ProductPlus\Entity\ProductDataDetail;
use Plugin\ProductPlus\Repository\ProductItemRepository;
use Plugin\ProductPlus\Repository\ProductDataRepository;
use Plugin\ProductPlus\Repository\ProductDataDetailRepository;
use Plugin\ProductPlus\Service\ProductPlusService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

class ProductPlusEvent implements EventSubscriberInterface
{
    private $entityManager;
    private $eccubeConfig;
    private $productItemRepository;
    private $productDataRepository;
    private $productDataDetailRepository;
    private $productPlusService;

    public function __construct(
            EntityManagerInterface $entityManager,
            EccubeConfig $eccubeConfig,
            ProductItemRepository $productItemRepository,
            ProductDataRepository $productDataRepository,
            ProductDataDetailRepository $productDataDetailRepository,
            ProductPlusService $productPlusService
            )
    {
        $this->entityManager = $entityManager;
        $this->eccubeConfig = $eccubeConfig;
        $this->productItemRepository = $productItemRepository;
        $this->productDataRepository = $productDataRepository;
        $this->productDataDetailRepository = $productDataDetailRepository;
        $this->productPlusService = $productPlusService;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            '@admin/Product/product.twig' => 'onTemplateAdminProductEdit',
            EccubeEvents::ADMIN_PRODUCT_EDIT_COMPLETE => 'hookAdminProductEditComplete',
            EccubeEvents::ADMIN_PRODUCT_COPY_COMPLETE => 'hookAdminProductCopyComplete',
            EccubeEvents::ADMIN_PRODUCT_CSV_EXPORT => 'hookAdminProductCsvExport',
            'csvimportproductext.admin.product.csv.import.product.descriptions' => 'hookAdminProductCsvImportProductDescriptions',
            'csvimportproductext.admin.product.csv.import.product.check'=> 'hookAdminProductCsvImportProductCheck',
            'csvimportproductext.admin.product.csv.import.product.process' => 'hookAdminProductCsvImportProductProcess',
        ];
    }

    public function onTemplateAdminProductEdit(TemplateEvent $event)
    {
        $parameters = $event->getParameters();

        $ProductItems = $this->productItemRepository->getList();
        $EnabledItems = $this->productPlusService->getEnabledProductPlusForm();
        foreach($ProductItems as $key => $ProductItem){
            foreach($EnabledItems as $EnabledItem){
                if($ProductItem->getId() == $EnabledItem->getId())unset($ProductItems[$key]);
            }
        }
        $parameters['ProductItems'] = $ProductItems;
        $event->setParameters($parameters);

        $source = $event->getSource();

        if(preg_match("/\{\%\sfor\sf\sin\sform(\sif|\|filter\(f\s\=\>)\sf\.vars\.eccube\_form\_options\.auto\_render\s\%\}/",$source, $result)){
            $search = $result[0];
            $replace = "{{ include('@ProductPlus/admin/Product/ext_edit.twig') }}" . $search;
            $source = str_replace($search, $replace, $source);
        }

        $event->setSource($source);

        $event->addSnippet('@ProductPlus/admin/Product/upload_js.twig');
        $twig = '@ProductPlus/admin/Product/datepicker_js.twig';
        $event->addAsset($twig);
    }

    public function hookAdminProductEditComplete(EventArgs $event)
    {
        $Product = $event->getArgument('Product');
        $form = $event->getArgument('form');
        $request = $event->getRequest();

        $ProductItems = $this->productItemRepository->getList();
        foreach($ProductItems as $ProductItem){
            if($form->has('productplus_'.$ProductItem->getId())){
                $ProductData = $this->productDataRepository->findOneBy(['ProductItem' => $ProductItem, 'Product' => $Product]);
                if(!$ProductData){
                    $ProductData = new ProductData();
                    $ProductData->setProductItem($ProductItem);
                    $ProductData->setProduct($Product);
                }
                if($ProductItem->getInputType() == ProductItem::IMAGE_TYPE) {
                    $add_images = $form->get('productplus_'.$ProductItem->getId().'_add_images')->getData();
                    $i = 0;
                    foreach ($add_images as $add_image) {
                        $i++;
                        $Detail = new ProductDataDetail();
                        $Detail
                            ->setValue($add_image)
                            ->setProductData($ProductData)
                            ->setSortNo($i);
                        $ProductData->addDetail($Detail);
                        $this->entityManager->persist($Detail);

                        $file = new File($this->eccubeConfig['eccube_temp_image_dir'].'/'.$add_image);
                        $file->move($this->eccubeConfig['eccube_save_image_dir']);
                    }

                    $delete_images = $form->get('productplus_'.$ProductItem->getId().'_delete_images')->getData();
                    foreach ($delete_images as $delete_image) {
                        $Detail = $this->productDataDetailRepository->findOneBy(['value' => $delete_image]);

                        if ($Detail instanceof ProductDataDetail) {
                            $ProductData->removeDetail($Detail);
                            $this->entityManager->remove($Detail);
                        }
                        $this->entityManager->persist($ProductData);

                        $fs = new Filesystem();
                        $fs->remove($this->eccubeConfig['eccube_save_image_dir'].'/'.$delete_image);
                    }
                    $this->entityManager->persist($ProductData);
                    $Product->addProductData($ProductData);
                    $this->entityManager->flush();

                    $sortNos = $request->get('productplus_'.$ProductItem->getId().'_sort_no_images');
                    if ($sortNos) {
                        foreach ($sortNos as $sortNo) {
                            list($filename, $sortNo_val) = explode('//', $sortNo);
                            $Detail = $this->productDataDetailRepository
                                ->findOneBy([
                                    'value' => $filename,
                                    'ProductData' => $ProductData,
                                ]);
                            if($Detail){
                                $Detail->setSortNo($sortNo_val);
                                $this->entityManager->persist($Detail);
                            }
                        }
                    }
                    $this->entityManager->flush();
                }else{
                    $value = $form->get('productplus_'.$ProductItem->getId())->getData();
                    if ($value instanceof \DateTime){
                        $value = $value->format('Y-m-d');
                    }elseif ($ProductItem->getInputType() == ProductItem::CHECKBOX_TYPE && is_array($value)) {
                        $value = implode(',', $value);
                    }
                    $ProductData = $this->productDataRepository->regist($ProductData, $ProductItem, $value);
                    $Product->addProductData($ProductData);
                }
            }
        }
    }

    public function hookAdminProductCopyComplete(EventArgs $event)
    {
        $Product = $event->getArgument('Product');
        $CopyProduct = $event->getArgument('CopyProduct');

        foreach ($Product->getProductDatas() as $oldProductData) {
            $newProductData = new ProductData();
            $newProductData->setProduct($CopyProduct);
            $newProductData->setProductItem($oldProductData->getProductItem());
            foreach($oldProductData->getDetails() as $oldDetail){
                $newDetail = new ProductDataDetail();
                $newDetail->setValue($oldDetail->getValue())
                          ->setNumValue($oldDetail->getNumValue())
                          ->setDateValue($oldDetail->getDateValue())
                          ->setProductData($newProductData);
                $newProductData->addDetail($newDetail);
            }
            $CopyProduct->addProductData($newProductData);
            $this->entityManager->persist($newProductData);
        }
        $this->entityManager->persist($CopyProduct);
        $this->entityManager->flush();
    }

    public function hookAdminProductCsvExport(EventArgs $event)
    {
        $ExportCsvRow = $event->getArgument('ExportCsvRow');
        if ($ExportCsvRow->isDataNull()) {
            $ProductClass = $event->getArgument('ProductClass');
            $Product = $ProductClass->getProduct();
            $Csv = $event->getArgument('Csv');

            $csvEntityName = str_replace('\\\\', '\\', $Csv->getEntityName());
            $value = null;
            if($csvEntityName == 'Plugin\ProductPlus\Entity\ProductData'){
                $product_item_id = $Csv->getReferenceFieldName();
                if($Csv->getFieldName() == 'product_item_id'){
                    $value = $Product->getIdData($product_item_id);
                }elseif($Csv->getFieldName() == 'product_item_value'){
                    $value = $Product->getValueData($product_item_id);
                }
                if(is_array($value))$value = implode(',', $value);
                $ExportCsvRow->setData($value);
            }
        }
    }

    public function hookAdminProductCsvImportProductDescriptions(EventArgs $event)
    {
        $header = $event->getArgument('header');
        $key = $event->getArgument('key');
        $ProductItems = $this->productItemRepository->getList();
        foreach($ProductItems as $ProductItem){
            if($key == $ProductItem->getName() . trans('productplus.csv.common.id')){
                $header['description'] = trans('productplus.admin.product.product_csv.product_plus.id_description');
                $header['required'] = false;
            }elseif($key == $ProductItem->getName() && $ProductItem->getInputType() == ProductItem::IMAGE_TYPE){
                $header['description'] = trans('productplus.admin.product.product_csv.product_plus.image_description');
                $header['required'] = false;
            }
        }

        $event->setArgument('header',$header);
    }

    public function hookAdminProductCsvImportProductCheck(EventArgs $event)
    {
        $row = $event->getArgument('row');
        $data = $event->getArgument('data');
        $errors = $event->getArgument('errors');

        $ProductItems = $this->productItemRepository->getList();
        foreach($ProductItems as $ProductItem){
            if(isset($row[$ProductItem->getName() . trans('productplus.csv.common.id')])){
                if($row[$ProductItem->getName() . trans('productplus.csv.common.id')] !== '' && preg_match("/[^0-9,]/", $row[$ProductItem->getName() . trans('productplus.csv.common.id')])){
                    $message = trans('productplus.admin.product.product_csv.not_correct', [
                        '%line%' => $data->key() + 1,
                        '%name%' => $ProductItem->getName() . trans('productplus.csv.common.id'),
                    ]);
                    $errors[] = $message;
                }
            }
        }

        $event->setArgument('errors',$errors);
    }

    public function hookAdminProductCsvImportProductProcess(EventArgs $event)
    {
        $row = $event->getArgument('row');
        $data = $event->getArgument('data');
        $ProductClass = $event->getArgument('ProductClass');
        $Product = $ProductClass->getProduct();

        $ProductItems = $this->productItemRepository->getList();
        foreach($ProductItems as $ProductItem){
            if(isset($row[$ProductItem->getName() . trans('productplus.csv.common.id')])
             || isset($row[$ProductItem->getName()])){
                $ProductData = $this->productDataRepository->findOneBy(['ProductItem' => $ProductItem, 'Product' => $Product]);
                if(!$ProductData){
                    $ProductData = new ProductData();
                    $ProductData->setProductItem($ProductItem);
                    $ProductData->setProduct($Product);
                }
                if(isset($row[$ProductItem->getName() . trans('productplus.csv.common.id')])){
                    $value = $row[$ProductItem->getName() . trans('productplus.csv.common.id')];
                    if (is_array($value)) {
                        $value = implode(',', $value);
                    }
                }
                if(isset($row[$ProductItem->getName()])){
                    $value = $row[$ProductItem->getName()];
                }
                if($ProductItem->getInputType() == ProductItem::IMAGE_TYPE) {
                    foreach($ProductData->getDetails() as $removeDetail){
                        $ProductData->removeDetail($removeDetail);
                        $this->entityManager->remove($removeDetail);
                    }
                    $arrValue = explode(',',$value);
                    $sortNo = 0;
                    foreach($arrValue as $value){
                        $Detail = new ProductDataDetail();
                        $Detail
                            ->setValue($value)
                            ->setProductData($ProductData)
                            ->setSortNo(++$sortNo);
                        $ProductData->addDetail($Detail);
                        $this->entityManager->persist($Detail);
                    }

                    $this->entityManager->persist($ProductData);
                    $Product->addProductData($ProductData);
                    $this->entityManager->flush();
                }else{
                    $ProductData = $this->productDataRepository->regist($ProductData, $ProductItem, $value);
                    $Product->addProductData($ProductData);
                }
            }else{

            }
        }
    }
}