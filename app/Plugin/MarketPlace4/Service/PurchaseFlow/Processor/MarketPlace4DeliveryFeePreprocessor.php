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

namespace Plugin\MarketPlace4\Service\PurchaseFlow\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\DeliveryFee;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\DeliveryFeeRepository;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\Processor\DeliveryFeePreprocessor;
use Plugin\MarketPlace4\Repository\DeliveryTypeRepository;
/**
 * 送料明細追加.
 */
class MarketPlace4DeliveryFeePreprocessor implements ItemHolderPreprocessor
{
    /**
     * @var DeliveryFeeRepository
     */
    protected $deliveryFeeRepository;

    /**
     * DeliveryFeePreprocessor constructor.
     *
     * @param DeliveryFeeRepository $deliveryFeeRepository
     * @param DeliveryTypeRepository $deliveryTypeRepository
     * @param BaseInfoRepository $baseInfoRepository
     */
    public function __construct(
        DeliveryFeeRepository $deliveryFeeRepository,
        DeliveryTypeRepository $deliveryTypeRepository,
        BaseInfoRepository $baseInfoRepository
    ) {

        $this->deliveryFeeRepository = $deliveryFeeRepository;
        $this->deliveryTypeRepository = $deliveryTypeRepository;
        $this->baseInfoRepository = $baseInfoRepository;
    }

    /**
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     */
    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        $this->updateDeliveryFeeItem($itemHolder);
    }

    /**
     * @param ItemHolderInterface $itemHolder
     */
    private function updateDeliveryFeeItem(ItemHolderInterface $itemHolder)
    {
        $BaseInfo = $this->baseInfoRepository->get();
        $deliveryFreeAmount = $BaseInfo->getDeliveryFreeAmount();

        /** @var Order $Order */
        $Order = $itemHolder;
        $sum = [];
        foreach ($Order->getShippings() as $Shipping) {
            $sum[$Shipping->getId()]['shipping'] = $Shipping;
            /** @var OrderItem $item */
            foreach ($Shipping->getOrderItems() as $item) {
                if ($item->isProduct()) {
                    $sum[$Shipping->getId()]['list'][$item->getProductClass()->getMember()->getId()]['amount'] += $item->getPriceIncTax()*$item->getQuantity();
                    $sum[$Shipping->getId()]['list'][$item->getProductClass()->getMember()->getId()]['member'] = $item->getProductClass()->getMember();
                }
            }
        }

        $matomeSum1 = 0;
        foreach ($sum as $ship_id=>$shipData){
            if (empty( $shipData['shipping']->getDeliveryType())){
                $DelveryType = $this->deliveryTypeRepository->find(2);
                //$shipData['shipping']->setDeliveryType( $DelveryType );
                $delivery_type = 2;
            }else{
                $delivery_type = $shipData['shipping']->getDeliveryType()->getId();
            }

            foreach ($shipData['list'] as $member_id => $memberData){
                if ($delivery_type==1){
                    if ($memberData['member']->isMarketPlaceMatome()){
                        $matomeSum1 = $matomeSum1 + $memberData['amount'];
                    }
                }
            }
        }

        $deliveryAmounts = [];
        $deliveryAddAmounts = [];
        foreach ($sum as $ship_id=>$shipData){
            if (empty( $shipData['shipping']->getDeliveryType())){
                $DelveryType1[$ship_id] = $this->deliveryTypeRepository->find(2);
                $shipData['shipping']->setDeliveryType( $DelveryType1[$ship_id] );
                $delivery_type = 2;
            }else{
                $delivery_type = $shipData['shipping']->getDeliveryType()->getId();
            }

            $sum = 0;
            $matomeSum = 0;

            $deliveryFee = $this->deliveryFeeRepository->findOneBy(['Delivery'=>$shipData['shipping']->getDelivery(), 'Pref'=>$shipData['shipping']->getPref()]);

            foreach ($shipData['list'] as $member_id => $memberData){
                if ($delivery_type==1){
                    if ($memberData['member']->isMarketPlaceMatome()){
                        $matomeSum = $matomeSum + $memberData['amount'];
                    }else{
                        if ($memberData['amount'] < $deliveryFreeAmount){
                            $sum += $deliveryFee->getFee();
                        }else{
                            $sum += $deliveryFee->getFreeFee();
                        }
                    }
                }else{
                    if ($memberData['amount'] < $deliveryFreeAmount ){
                        $sum += $deliveryFee->getFee();
                    }else{
                        $sum += $deliveryFee->getFreeFee();
                    }
                }
            }

            if ( $delivery_type==1) {
                if ($matomeSum1 < $deliveryFreeAmount){
                    $sum += $deliveryFee->getFee();
                }else{
                    $sum += $deliveryFee->getFreeFee();
                }
            }
            $deliveryAmounts[$ship_id] = $sum;
            $deliveryAddAmounts[$ship_id] = empty($deliveryFee->getAddFee()) ? 0 : $deliveryFee->getAddFee();

        }
        /* @var Shipping $Shipping */
        foreach ($Order->getShippings() as $Shipping) {
            /** @var OrderItem $item */
            foreach ($Shipping->getOrderItems() as $item) {
                if ($item->isDeliveryFee()) {
                    $item->setPrice($deliveryAmounts[$Shipping->getId()]+$deliveryAddAmounts[$Shipping->getId()]);
                    $item->setQuantity(1);
                }
            }
        }
    }
}
