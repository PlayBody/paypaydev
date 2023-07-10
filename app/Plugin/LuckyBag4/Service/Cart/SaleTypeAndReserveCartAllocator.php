<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\LuckyBag4\Service\Cart;

use Eccube\Entity\CartItem;
use Eccube\Service\Cart\CartItemAllocator;
use Eccube\Repository\ProductRepository;
use Plugin\LuckyBag4\Repository\LuckyBag4ConfigRepository;

/**
 * 販売種別ごとにカートを振り分けるCartItemAllocator
 */
class SaleTypeAndReserveCartAllocator implements CartItemAllocator
{

    public function allocate(CartItem $Item)
    {

        $ProductClass = $Item->getProductClass();
        if ($ProductClass && $ProductClass->getSaleType()) {
            if ( (string) $ProductClass->getSaleType()->getName() == '福袋専用'){
                return 'L';
            }else{
                return 'R';
            }
        }
        throw new \InvalidArgumentException('ProductClass/SaleType not found');
    }
}
