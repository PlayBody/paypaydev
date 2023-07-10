<?php

namespace Plugin\ALIJSettlementIV\Entity;

use Eccube\Annotation\EntityExtension;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Eccube\Annotation\FormAppend;

/**
 * @EntityExtension("Eccube\Entity\ProductClass")
 */
trait ProductClassTrait
{
    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @FormAppend(
     *  auto_render=true,
     *  options={
     *     "label": "ALIJ.admin.product_class.alij_cont_enable"
     *  })
     */
    private $alij_cont_enable;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\GreaterThanOrEqual(2)
     * @FormAppend(
     *  auto_render=true,
     *  options={
     *     "label": "ALIJ.admin.product_class.alij_cont_times"
     *  })
     */
    private $alij_cont_times;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\GreaterThanOrEqual(0)
     * @FormAppend(
     *  auto_render=true,
     *  options={
     *     "label": "ALIJ.admin.product_class.alij_cont_amount2"
     *  })
     */
    private $alij_cont_amount2;

    public function setALIJContEnable($alij_cont_enable){
        $this->alij_cont_enable = $alij_cont_enable;
    }

    public function getALIJContEnable() {
        return $this->alij_cont_enable;
    }

    public function setALIJContAmount2($alij_cont_amount2){
        $this->alij_cont_amount2 = $alij_cont_amount2;
    }

    public function getALIJContAmount2() {
        return $this->alij_cont_amount2;
    }

    public function setALIJContTimes($alij_cont_times){
        $this->alij_cont_times = $alij_cont_times;
    }

    public function getALIJContTimes() {
        return $this->alij_cont_times;
    }

}