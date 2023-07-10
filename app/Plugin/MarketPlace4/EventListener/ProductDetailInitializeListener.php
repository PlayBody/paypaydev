<?php
namespace Plugin\MarketPlace4\EventListener;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;


class ProductDetailInitializeListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            EccubeEvents::FRONT_PRODUCT_DETAIL_INITIALIZE => 'onFrontProductDetailInitialize',
        ];
    }

    public function onFrontProductDetailInitialize(EventArgs $eventArgs)
    {
        $Product = $eventArgs->getArgument('Product');

        $CategoryData = [];
        foreach ($Product->getProductClasses() as $ProductClass){
            if (!$ProductClass->isVisible()) continue;
            $ClassCategory1 = $ProductClass->getClassCategory1();
            $ClassCategory2 = $ProductClass->getClassCategory2();
            if ($ClassCategory1){
                if ($ClassCategory2){
                    //$CategoryData[$ClassCategory1->getId()][$ClassCategory2->getId()] = $ProductClass->getStockFind();
                }else{
                    if (!$CategoryData[$ClassCategory1->getId()]){
                        $CategoryData[$ClassCategory1->getId()] =  $ProductClass->getStockFind();
                    }
                }
            }
        }

        foreach ($Product->getProductClasses() as $ProductClass){
            if (!$ProductClass->isVisible()) continue;
            $ClassCategory1 = $ProductClass->getClassCategory1();
            $ClassCategory2 = $ProductClass->getClassCategory2();
            if ($ClassCategory1){
                if ($ClassCategory2){
                    //$CategoryData[$ClassCategory1->getId()][$ClassCategory2->getId()] = $ProductClass->getStockFind();
                }else{
                    if ($CategoryData[$ClassCategory1->getId()]){
                        if (!$ProductClass->getStockFind()){
                            $Product->removeProductClass($ProductClass);
                        }
                    }
                }
            }
        }

    }

}
?>