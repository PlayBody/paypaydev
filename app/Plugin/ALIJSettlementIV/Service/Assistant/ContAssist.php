<?php

namespace Plugin\ALIJSettlementIV\Service\Assistant;

use Eccube\Entity\Order;
use Plugin\ALIJSettlementIV\Repository\ConfigRepository;

class ContAssist
{
    public $alijContEnable;

    public $alijContTimes;

    public $alijContAmount2;

    public $configRepository;

    public function __construct(
        ConfigRepository $configRepository
    )
    {
        $this->configRepository = $configRepository;
    }

    /**
     * 決済タイプを取得
     *
     * @param Order $Order
     * @return string
     */
    public function getPayType(Order $Order)
    {
        $siteConfig = $this->configRepository->get();
        if($siteConfig->getUseCont() === false) {
            return 'onePay';
        }

        $orderItems = $Order->getProductOrderItems();
        $itemQuantity = sizeof($orderItems);
        foreach ($orderItems as $orderItem)
        {
            $useCont = $orderItem->getProductClass()->getALIJContEnable();

            if($useCont && $itemQuantity == 1)
            {
                return 'cont';
            }
            if($useCont && $itemQuantity > 1)
            {
                return 'error';
            }
        }
        return 'onePay';
    }

    /**
     * 次回金額を計算
     *
     * @param Order $Order
     * @return float|int|string
     */
    public function calcAmount2(Order $Order)
    {
        $productOrderItem = $Order->getProductOrderItems()['0'];
        //設定値
        $amount2Value = $productOrderItem->getProductClass()->getALIJContAmount2();
        if($amount2Value != null)
        {   
            $taxRate = $productOrderItem['tax_rate'];
            $quantity = $productOrderItem['quantity'];
            $amount2 = null;
            // 連続空白除去
            $array = preg_replace('/\s(?=\S)/',' ',$amount2Value);
            // カンマ除去
            $array = str_replace(',', '', $array);
            foreach (explode(' ', $array) as $amountValue) {           
                $tmp = (int)($amountValue * $quantity * (1 + $taxRate/100.0) + $Order->getDeliveryFeeTotal() + $Order->getCharge());
                $amount2 = $amount2==null ? strval($tmp) : $amount2 . " " . strval($tmp);
            }
            //値引きを無視
            
        }
        return $amount2;
    }

    /**
     * 継続回数を取得
     *
     * @param Order $Order
     * @return mixed
     */
    public function getContTimes(Order $Order)
    {
        $productOrderItem = $Order->getProductOrderItems()['0'];
        $times = $productOrderItem->getProductClass()->getALIJContTimes();
        return $times;
    }
}