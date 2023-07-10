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

namespace Plugin\ProductPlus\Entity;

use Eccube\Annotation\EntityExtension;
use Doctrine\ORM\Mapping as ORM;

/**
 * @EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{
        /**
         * @var \Doctrine\Common\Collections\Collection
         *
         * @ORM\OneToMany(targetEntity="Plugin\ProductPlus\Entity\ProductData", mappedBy="Product", cascade={"persist","remove"})
         */
        private $ProductDatas;

        public function getProductData($product_item_id)
        {
            foreach($this->ProductDatas as $ProductData){
                if($ProductData->getProductItem()->getId() == $product_item_id)return $ProductData->getValue();
            }
            return null;
        }

        public function getIdData($product_item_id)
        {
            foreach($this->ProductDatas as $ProductData){
                if($ProductData->getProductItem()->getId() == $product_item_id)return $ProductData->getNumValue();
            }
            return null;
        }

        public function getValueData($product_item_id)
        {
            foreach($this->ProductDatas as $ProductData){
                if($ProductData->getProductItem()->getId() == $product_item_id)return $ProductData->getDataValue();
            }
            return null;
        }

        public function getViewData($product_item_id)
        {
            foreach($this->ProductDatas as $ProductData){
                if($ProductData->getProductItem()->getId() == $product_item_id)return $ProductData->getViewValue();
            }
            return null;
        }

        public function __construct()
        {
            $this->ProductDatas = new \Doctrine\Common\Collections\ArrayCollection();
        }

        public function addProductData(\Plugin\ProductPlus\Entity\ProductData $productData)
        {
            $this->ProductDatas[] = $productData;

            return $this;
        }

        public function removeProductData(\Plugin\ProductPlus\Entity\ProductData $productData)
        {
            return $this->ProductDatas->removeElement($productData);
        }

        public function getProductDatas()
        {
            return $this->ProductDatas;
        }
}
