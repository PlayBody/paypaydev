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

namespace Plugin\ProductPlus\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\ProductPlus\Entity\ProductItem;
use Plugin\ProductPlus\Entity\ProductData;
use Plugin\ProductPlus\Entity\ProductDataDetail;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ProductDataRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry, string $entityClass = \Plugin\ProductPlus\Entity\ProductData::class)
    {
        parent::__construct($registry, $entityClass);
    }

    public function regist(ProductData $ProductData, ProductItem $ProductItem, $value)
    {
        $em = $this->getEntityManager();

        $ProductData->setProductItem($ProductItem);
        $details = $ProductData->getDetails();
        if(count($details) > 0){
            foreach($details as $detail){
                $ProductData->removeDetail($detail);
                $em->remove($detail);
            }
        }
        $em->persist($ProductData);
        $em->flush($ProductData);

        if($ProductItem->getInputType() == ProductItem::CHECKBOX_TYPE){
            $arrValue = explode(',', $value);
        }else{
            $arrValue = [$value];
        }
        if($ProductItem->getInputType() == ProductItem::IMAGE_TYPE){
            $arrValue = [];
        }
        $sortNo = 0;
        foreach($arrValue as $value){
            $detail = new ProductDataDetail();
            $detail->setProductData($ProductData);
            $detail->setSortNo(++$sortNo);
            $disp_value = $value;
            if($ProductItem->getInputType() == ProductItem::DATE_TYPE){
                if(!is_null($value)){
                    $value = new \DateTime($value);
                }
                $detail->setDateValue($value);
            }
            if($ProductItem->getInputType() >= ProductItem::SELECT_TYPE){
                $detail->setNumValue(intval($value));
                foreach($ProductItem->getOptions() as $Option){
                    if($value == $Option->getId())$disp_value = $Option->getText();
                }
            }
            $detail->setValue($disp_value);
            $ProductData->addDetail($detail);
        }
        $em->persist($ProductData);
        $em->flush($ProductData);

        return $ProductData;
    }
}