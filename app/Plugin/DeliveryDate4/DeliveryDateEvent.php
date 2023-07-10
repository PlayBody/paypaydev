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

namespace Plugin\DeliveryDate4;

use Eccube\Repository\ProductClassRepository;
use Eccube\Event\EccubeEvents;
use Eccube\Event\TemplateEvent;
use Eccube\Event\EventArgs;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;

class DeliveryDateEvent implements EventSubscriberInterface
{
    private $entityManager;

    private $productClassRepository;

    public function __construct(
            EntityManagerInterface $entityManager,
            ProductClassRepository $productClassRepository
            )
    {
        $this->entityManager = $entityManager;
        $this->productClassRepository = $productClassRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            '@admin/Setting/Shop/delivery_edit.twig' => 'onTemplateAdminSettingShopDeliveryEdit',
            EccubeEvents::ADMIN_SETTING_SHOP_DELIVERY_EDIT_COMPLETE => 'hookAdminSettingShopDeliveryEditComplete',
            EccubeEvents::ADMIN_PRODUCT_COPY_COMPLETE => 'hookAdminProductCopyComplete',
            '@admin/Product/product.twig' => 'onTemplateAdminProductEdit',
            '@admin/Product/product_class.twig' => 'onTemplateAdminProductClassEdit',
            'csvimportproductext.admin.product.csv.import.product.descriptions' => 'hookAdminProductCsvImportProductDescriptions',
            'csvimportproductext.admin.product.csv.import.product.check'=> 'hookAdminProductCsvImportProductCheck',
            'csvimportproductext.admin.product.csv.import.product.process' => 'hookAdminProductCsvImportProductProcess',
        ];
    }

    public function onTemplateAdminSettingShopDeliveryEdit(TemplateEvent $event)
    {
        $twig = '@DeliveryDate4/admin/Setting/Shop/delivery_date.twig';
        $event->addSnippet($twig);
    }

    public function hookAdminSettingShopDeliveryEditComplete(EventArgs $event)
    {
        $form = $event->getArgument('form');
        $Delivery = $event->getArgument('Delivery');

        $DeliveryDates = $form['delivery_dates']->getData();
        foreach($DeliveryDates as $DeliveryDate){
            $DeliveryDate->setDelivery($Delivery);
            $Delivery->addDeliveryDate($DeliveryDate);
            $this->entityManager->persist($DeliveryDate);
        }
        $this->entityManager->flush();
    }

    public function hookAdminProductCopyComplete(EventArgs $event)
    {
        $Product = $event->getArgument('Product');
        $CopyProduct = $event->getArgument('CopyProduct');
        $orgProductClasses = $Product->getProductClasses();

        foreach ($orgProductClasses as $ProductClass) {
            $CopyProductClass = $this->productClassRepository->findOneBy(['Product'=> $CopyProduct, 'ClassCategory1' => $ProductClass->getClassCategory1(), 'ClassCategory2' => $ProductClass->getClassCategory2()]);
            if($CopyProductClass){
                $CopyProductClass->setDeliveryDateDays($ProductClass->getDeliveryDateDays());
                $this->entityManager->persist($CopyProductClass);
            }
        }

        $this->entityManager->flush();
    }

    public function onTemplateAdminProductEdit(TemplateEvent $event)
    {
        $twig = '@DeliveryDate4/admin/Product/product_days.twig';
        $event->addSnippet($twig);
    }

    public function onTemplateAdminProductClassEdit(TemplateEvent $event)
    {
        $twig = '@DeliveryDate4/admin/Product/product_class_days.twig';
        $event->addSnippet($twig);
    }

    public function hookAdminProductCsvImportProductDescriptions(EventArgs $event)
    {
        $header = $event->getArgument('header');
        $key = $event->getArgument('key');
        if($key == trans('deliverydate.common.1')){
            $header['description'] = trans('deliverydate.admin.product.product_csv.delivery_date_description');
            $header['required'] = false;
        }

        $event->setArgument('header',$header);
    }

    public function hookAdminProductCsvImportProductCheck(EventArgs $event)
    {
        $row = $event->getArgument('row');
        $data = $event->getArgument('data');
        $errors = $event->getArgument('errors');

        if(isset($row[trans('deliverydate.common.1')])){
            if($row[trans('deliverydate.common.1')] !== '' && !is_numeric($row[trans('deliverydate.common.1')]) || $row[trans('deliverydate.common.1')] < 0){
                $message = trans('admin.common.csv_invalid_greater_than_zero', [
                    '%line%' => $data->key() + 1,
                    '%name%' => trans('deliverydate.common.1'),
                ]);
                $errors[] = $message;
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

        if(isset($row[trans('deliverydate.common.1')])){
            if($row[trans('deliverydate.common.1')] != ''){
                $ProductClass->setDeliveryDateDays($row[trans('deliverydate.common.1')]);
            }else{
                $ProductClass->setDeliveryDateDays(NULL);
            }
        }
    }
}
