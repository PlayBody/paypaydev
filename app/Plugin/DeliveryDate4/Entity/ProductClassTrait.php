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

namespace Plugin\DeliveryDate4\Entity;

use Eccube\Annotation\EntityExtension;
use Doctrine\ORM\Mapping as ORM;

/**
 * @EntityExtension("Eccube\Entity\ProductClass")
 */

trait ProductClassTrait
{
    /**
     * @var int|null
     *
     * @ORM\Column(name="delivery_date_days", type="integer", nullable=true)
     */
    private $delivery_date_days;

    public function setDeliveryDateDays($days)
    {
        $this->delivery_date_days = $days;

        return $this;
    }

    public function getDeliveryDateDays()
    {
        return $this->delivery_date_days;
    }
}
