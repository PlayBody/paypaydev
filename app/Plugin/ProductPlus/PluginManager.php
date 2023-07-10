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

use Eccube\Plugin\AbstractPluginManager;
use Eccube\Entity\Master\DeviceType;
use Eccube\Repository\CsvRepository;
use Eccube\Repository\Master\CsvTypeRepository;
use Plugin\ProductPlus\Entity\ProductItem;
use Plugin\ProductPlus\Repository\ProductItemRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class PluginManager extends AbstractPluginManager
{
    public function install(array $meta, ContainerInterface $container)
    {

    }

    public function uninstall(array $meta, ContainerInterface $container)
    {

    }

    public function enable(array $meta, ContainerInterface $container)
    {
        $translator = $container->get('translator');
        $ymlPath = $container->getParameter('plugin_realdir') . '/ProductPlus/Resource/locale/messages.'.$translator->getLocale().'.yaml';
        if(!file_exists($ymlPath))$ymlPath = $container->getParameter('plugin_realdir') . '/ProductPlus/Resource/locale/messages.ja.yaml';
        $messages = Yaml::parse(file_get_contents($ymlPath));

        $entityManager = $container->get('doctrine.orm.entity_manager');
        $now = new \DateTime();
        // CSV項目登録
        $ProductItems = $container->get(ProductItemRepository::class)->findAll();

        foreach($ProductItems as $ProductItem){
            // 商品項目
            $CsvType = $container->get(CsvTypeRepository::class)->find(\Eccube\Entity\Master\CsvType::CSV_TYPE_PRODUCT);
            $sortNo = $entityManager->createQueryBuilder()
                    ->select('MAX(c.sort_no)')
                    ->from('Eccube\Entity\Csv','c')
                    ->where('c.CsvType = :csvType')
                    ->setParameter(':csvType',$CsvType)
                    ->getQuery()
                    ->getSingleScalarResult();
            if (!$sortNo) {
                $sortNo = 0;
            }

            if($ProductItem->getInputType() >= ProductItem::SELECT_TYPE){
                $Csv = new \Eccube\Entity\Csv();
                $Csv->setCsvType($CsvType);
                $Csv->setEntityName('Plugin\\ProductPlus\\Entity\\ProductData');
                $Csv->setFieldName('product_item_id');
                $Csv->setReferenceFieldName($ProductItem->getId());
                $Csv->setDispName($ProductItem->getName().$messages['productplus.csv.common.id']);
                $Csv->setEnabled(false);
                $Csv->setSortNo(++$sortNo);
                $Csv->setCreateDate(new \DateTime());
                $Csv->setUpdateDate(new \DateTime());
                $entityManager->persist($Csv);

                $Csv = new \Eccube\Entity\Csv();
                $Csv->setCsvType($CsvType);
                $Csv->setEntityName('Plugin\\ProductPlus\\Entity\\ProductData');
                $Csv->setFieldName('product_item_value');
                $Csv->setReferenceFieldName($ProductItem->getId());
                $Csv->setDispName($ProductItem->getName().$messages['productplus.csv.common.name']);
                $Csv->setEnabled(false);
                $Csv->setSortNo(++$sortNo);
                $Csv->setCreateDate(new \DateTime());
                $Csv->setUpdateDate(new \DateTime());
                $entityManager->persist($Csv);
            }else{
                $Csv = new \Eccube\Entity\Csv();
                $Csv->setCsvType($CsvType);
                $Csv->setEntityName('Plugin\\ProductPlus\\Entity\\ProductData');
                $Csv->setFieldName('product_item_value');
                $Csv->setReferenceFieldName($ProductItem->getId());
                $Csv->setDispName($ProductItem->getName());
                $Csv->setEnabled(false);
                $Csv->setSortNo(++$sortNo);
                $Csv->setCreateDate(new \DateTime());
                $Csv->setUpdateDate(new \DateTime());
                $entityManager->persist($Csv);
            }
        }
        $entityManager->flush();
    }

    public function disable(array $meta, ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $Csvs = $container->get(CsvRepository::class)->findBy(['entity_name' => 'Plugin\\ProductPlus\\Entity\\ProductData']);
        foreach($Csvs as $Csv){
            $entityManager->remove($Csv);
        }
        $entityManager->flush();
    }
}
