<?php

namespace Plugin\ProductFilter4;

use Eccube\Common\EccubeNav;

class ProductFilter4Nav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return  [
            'product' => [
                'children' => [
                    'plg_product_filter4_tag_group' => [
                        'name' => 'plg_product_filter4.admin.tag_group.nav_title',
                        'url' => 'plg_product_filter4_admin_tag_group_index',
                    ],
                ],
            ],
        ];
    }
}
