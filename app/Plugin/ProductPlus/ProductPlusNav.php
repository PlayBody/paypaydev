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

use Eccube\Common\EccubeNav;

class ProductPlusNav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'product' => [
                'children' => [
                    'productitem' => [
                        'id' => 'product_productitem',
                        'name' => 'productplus.admin.nav.product.productitem',
                        'url' => 'admin_product_productitem',
                    ],
                ],
            ],
        ];
    }
}