<?php

namespace Plugin\ALIJSettlementIV\Service\Assistant;

use Eccube\Entity\Order;

class ErrorCheck
{
    /**
     * 金額チェック
     * @param $siteConfig
     * @param Order $Order
     * @param $callback
     * @return bool
     */
    public function checkAmount($siteConfig, Order $Order,$callback)
    {
        if ($siteConfig->getUseAmountCheck() === false) {
            return false;
        }

        if(array_key_exists('billingAmount',$callback))
        {
            //オーダー合計金額と銀振などの請求金額一致ではない場合
            if($Order->getTotal() != $callback['billingAmount'])
            {
                return true;
            }
        }
        else
        {
            //オーダー合計金額とクレジットカード決済などの請求金額一致ではない場合
            if($Order->getTotal() != $callback['Amount'])
            {
                return true;
            }
        }
        return false;
    }

    /**
     * IPチェック
     * @param $siteConfig
     * @param $request
     * @return bool
     */
    public function checkIP($siteConfig, $request)
    {
        // 0.0.0.0と空の場合は、チェックしない
        if ($siteConfig->getServerIP() === '0.0.0.0' || $siteConfig->getServerIP() === '') {
            return false;
        }

        // 不一致の場合
        if ($siteConfig->getServerIP() !== $request->getClientIp()){
            return true;
        }

        return false;
    }


}