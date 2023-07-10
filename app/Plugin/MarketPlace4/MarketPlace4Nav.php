<?php

namespace Plugin\MarketPlace4;

use Eccube\Common\EccubeNav;

class MarketPlace4Nav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'product' => [
                'children' => [
                    'product_stock_csv_inout' => [
                        'name' => 'market_place4.admin.product_stock_csv_inout.title',
                        'url' => 'market_place4_admin_product_stock_csv_inout',
                    ],
                ],
            ],
        ];
    }
}
