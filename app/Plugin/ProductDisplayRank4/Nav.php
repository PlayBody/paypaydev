<?php

namespace Plugin\ProductDisplayRank4;

use Eccube\Common\EccubeNav;

class Nav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'product' => [
                'children' => [
                    'product_display_rank_csv_import' => [
                        'name' => 'admin.product.product_display_rank_csv_upload',
                        'url' => 'admin_product_display_rank_csv_import',
                    ],
                ],
            ],
        ];
    }
}
