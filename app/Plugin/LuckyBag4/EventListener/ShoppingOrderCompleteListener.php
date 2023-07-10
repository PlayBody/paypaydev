<?php
namespace Plugin\LuckyBag4\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Controller\Admin\Product\ProductClassController;
use Eccube\Controller\ShoppingController;
use Eccube\Entity\CartItem;
use Eccube\Entity\MailHistory;
use Eccube\Entity\Master\SaleType;
use Eccube\Entity\OrderItem;
use Eccube\Request\Context;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;

use Eccube\Service\CartService;
use Eccube\Repository\CartRepository;
use Eccube\Repository\CartItemRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

use Eccube\Repository\MailTemplateRepository;
use Eccube\Entity\BaseInfo;
use Eccube\Repository\MailHistoryRepository;

use Plugin\MarketPlace4\Repository\OrderMemberRepository;
use Eccube\Repository\BaseInfoRepository;

use Eccube\Repository\MemberRepository;
use Eccube\Repository\PluginRepository;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\ProductClassRepository;
use Plugin\LuckyBag4\Repository\LuckyBag4ConfigRepository;
use Plugin\LuckyBag4\Repository\ProductLuckyRepository;

class ShoppingOrderCompleteListener implements EventSubscriberInterface
{
    /**
     * @var Context
     */
    protected $context;
    /**
     * @var LuckyBag4ConfigRepository
     */
    protected $configRepository;
    /**
     * @var ProductLuckyRepository
     */
    protected $productLuckyRepository;

    /**
     * @var ProductClassRepository
     */
    protected $productClassRepository;
    /**
     * @var MemberRepository
     */
    protected $memberRepository;
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;
    /**
     * @var PluginRepository
     */
    protected $pluginRepository;
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;



    /**
    * ShoppingOrderCompleteListener constructor.
    *
     * @param Context $context
     * @param LuckyBag4ConfigRepository $ConfigRepository
     * @param ProductLuckyRepository $productLuckyRepository
     * @param ProductClassRepository $productClassRepository
     * @param MemberRepository $memberRepository
     * @param CustomerRepository $customerRepository
     * @param PluginRepository $pluginRepository
     * @param EntityManagerInterface $entityManager
    */
    public function __construct(
        Context $context,
        LuckyBag4ConfigRepository $ConfigRepository,
        ProductLuckyRepository $productLuckyRepository,
        ProductClassRepository $productClassRepository,
        MemberRepository $memberRepository,
        CustomerRepository $customerRepository,
        PluginRepository $pluginRepository,
        EntityManagerInterface $entityManager
        ) {
        $this->context = $context;
        $this->configRepository = $ConfigRepository;
        $this->productLuckyRepository = $productLuckyRepository;
        $this->productClassRepository = $productClassRepository;
        $this->memberRepository = $memberRepository;
        $this->customerRepository = $customerRepository;
        $this->pluginRepository = $pluginRepository;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EccubeEvents::FRONT_SHOPPING_COMPLETE_INITIALIZE => 'onFrontShoppingComplete',
        ];
    }

    public function onFrontShoppingComplete(EventArgs $eventArgs)
    {
        $Order = $eventArgs->getArgument('Order');

        $Config = $this->configRepository->find(1);

        foreach ($Order->getOrderItems() as $OrderItem){
            if (!$OrderItem->isProduct()) continue;

            $SaleType = $OrderItem->getProductClass()->getSaleType();

            if ($SaleType == $Config->getSaleType()){
                if ($Order->getLuckyBag4SaleType() != $SaleType->getId()){
                    $Order->setLuckyBag4SaleType($SaleType->getId());
                    $this->entityManager->persist($Order);
                    $this->entityManager->flush();
                }

                for ($i=0; $i<$OrderItem->getQuantity(); $i++){
                    $selData = $this->getLuckyProduct($OrderItem);
                    if ($selData['is_add_point']){
                        $Customer = $this->context->getCurrentUser();
                        $Customer->setPoint($Customer->getPoint() + $selData['product_code']);
                        $this->entityManager->persist($Customer);

                        $NewOrderItem = new OrderItem();
                        $NewOrderItem
                            ->setOrder($Order)
                            ->setShipping($OrderItem->getShipping())
                            ->setRoundingType($OrderItem->getRoundingType())
                            ->setTaxType($OrderItem->getTaxType())
                            ->setTaxDisplayType($OrderItem->getTaxDisplayType())
                            ->setOrderItemType($OrderItem->getOrderItemType())
                            ->setProduct($OrderItem->getProduct())
                            ->setProductCode('ハズレ('.$selData['product_code'].'ポイント付与)')
                            ->setProductName($OrderItem->getProductName())
                            ->setPrice($OrderItem->getPrice())
                            ->setQuantity(1)
                            ->setPointRate($selData['product_code']);

                        $this->entityManager->persist($NewOrderItem);
                        $this->entityManager->flush();

                    } else {
                        $Admin = $this->memberRepository->find(1);
                        $product_code = $selData['product_code'];
                        $ProductClasses = $this->productClassRepository->createQueryBuilder('pc')
                            ->where('pc.code = :product_code')->setParameter('product_code', $product_code)
                            ->andWhere('pc.visible = true')
                            ->andWhere('pc.Member = :Admin')->setParameter('Admin', $Admin)
                            ->andWhere('pc.stock > 0 OR pc.stock_unlimited = true')
                            ->getQuery()->getResult();

                        if (empty($ProductClasses)) continue;

                        $ProductClass = $ProductClasses[0];

                        $NewOrderItem = new OrderItem();
                        $NewOrderItem
                            ->setOrder($Order)
                            ->setProduct($OrderItem->getProduct())
                            ->setProductClass($ProductClasses[0])
                            ->setShipping($OrderItem->getShipping())
                            ->setRoundingType($OrderItem->getRoundingType())
                            ->setTaxType($OrderItem->getTaxType())
                            ->setTaxDisplayType($OrderItem->getTaxDisplayType())
                            ->setOrderItemType($OrderItem->getOrderItemType())
                            ->setProductName($OrderItem->getProductName())
                            ->setProductCode($ProductClass->getCode())
                            ->setPrice($OrderItem->getPrice())
                            ->setQuantity(1)
                            ->setTax($OrderItem->getTax())
                            ->setTaxRate($OrderItem->getTaxRate())
                            ->setTaxAdjust($OrderItem->getTaxAdjust())
                            ->setTaxRuleId($OrderItem->getTaxRuleId())
                            ->setCurrencyCode($OrderItem->getCurrencyCode())
                            ->setPointRate($OrderItem->getPointRate());

                        $this->entityManager->persist($NewOrderItem);
                        $this->entityManager->flush();

                        if (!$ProductClass->isStockUnlimited()){
                            $ProductClass->setStock($ProductClass->getStock()-1);
                            $ProductStock = $ProductClass->getProductStock();
                            $ProductStock->setStock($ProductClass->getStock());

                            $this->entityManager->persist($ProductClass);
                            $this->entityManager->persist($ProductStock);
                            $this->entityManager->flush();
                            $this->updateProductlucky($OrderItem->getProduct());
                        }
                    }

                }
                $this->entityManager->remove($OrderItem);
                $this->entityManager->flush();

            }
        }
        $this->entityManager->flush();

    }

    private function getLuckyProduct($OrderItem){
        $Product = $OrderItem->getProduct();
        $ProductLuckies = $this->productLuckyRepository->findBy(['Product' => $Product]);

        $Admin = $this->memberRepository->find(1);

        $insMax = 0;
        $min = 0;
        $data = [];
        foreach ($ProductLuckies as $ProductLucky){

            $temp = [];
            $product_code = $ProductLucky->getProductCode();
            if (!$ProductLucky->isAddPoint()){
                // if MarketPlacePlugin
                $Plugin = $this->pluginRepository->findOneBy(['code'=>'MarketPlace4']);
                if (!empty($Plugin)){
                    $ProductClasses = $this->productClassRepository->createQueryBuilder('pc')
                        ->where('pc.code = :product_code')->setParameter('product_code', $product_code)
                        ->andWhere('pc.visible = true')
                        ->andWhere('pc.Member = :Admin')->setParameter('Admin', $Admin)
                        ->andWhere('pc.stock > 0 OR pc.stock_unlimited = true')
                        ->getQuery()->getResult();
                    ;

                }else{
                    $ProductClasses = $this->productClassRepository->createQueryBuilder('pc')
                        ->where('pc.code = :product_code')->setParameter('product_code', $product_code)
                        ->andWhere('pc.visible = true')
                        ->andWhere('pc.stock > 0 OR pc.stock_unlimited = true')
                        ->getQuery()->getResult();
                    ;
                }
                if (empty($ProductClasses)) continue;
            }
            $insMax += $ProductLucky->getLuckyRate();
            $temp['min'] = $min + 1;
            $temp['max'] = $min + $ProductLucky->getLuckyRate();
            $temp['product_code'] = $product_code;
            $temp['is_add_point'] = $ProductLucky->isAddPoint();
            $data[] = $temp;
            $min = $min + $ProductLucky->getLuckyRate();
        }

        if (empty($data)){
            $Config = $this->configRepository->find(1);
            $item=[];
            $item['product_code'] = $Config->getAddPoint();
            $item['is_add_point'] = true;

            return $item;
        }

        $random = rand(1, $insMax);
        foreach ($data as $item){
            if ($random >= $item['min'] && $random <= $item['max']){
                return $item;
            }
        }

        return null;
    }

    private function updateProductlucky($Product){
        $ProductLuckies = $this->productLuckyRepository->findBy(['Product' => $Product]);
        $insMax = 0;
        $min = 0;
        $data = [];

        $Admin = $this->memberRepository->find(1);

        foreach ($ProductLuckies as $ProductLucky){
            $temp = [];
            $product_code = $ProductLucky->getProductCode();
            if (!$ProductLucky->isAddPoint()){

                // if MarketPlacePlugin
                $Plugin = $this->pluginRepository->findOneBy(['code'=>'MarketPlace4']);
                if (!empty($Plugin)){
                    $ProductClasses = $this->productClassRepository->createQueryBuilder('pc')
                        ->where('pc.code = :product_code')->setParameter('product_code', $product_code)
                        ->andWhere('pc.visible = true')
                        ->andWhere('pc.Member = :Admin')->setParameter('Admin', $Admin)
                        ->andWhere('pc.stock > 0 OR pc.stock_unlimited = true')
                        ->getQuery()->getResult();
                    ;

                }else{
                    $ProductClasses = $this->productClassRepository->createQueryBuilder('pc')
                        ->where('pc.code = :product_code')->setParameter('product_code', $product_code)
                        ->andWhere('pc.visible = true')
                        ->andWhere('pc.stock > 0 OR pc.stock_unlimited = true')
                        ->getQuery()->getResult();
                    ;
                }

                if (empty($ProductClasses)){
                    $ProductLucky->setLuckyRate(0);
                    $this->entityManager->persist($ProductLucky);
                    $this->entityManager->flush();
                    continue;
                }
            }

            $insMax += $ProductLucky->getLuckyRate();
            $temp['min'] = $min + 1;
            $temp['max'] = $min + $ProductLucky->getLuckyRate();
            $temp['ProductLucky'] = $ProductLucky;
            $data[] = $temp;
            $min = $min + $ProductLucky->getLuckyRate();
        }

        foreach ($data as $item){
            $rate = round(($item['max']-$item['min']+1)/$insMax*100);
            $ProductLucky = $item['ProductLucky'];
            $ProductLucky->setLuckyRate($rate);
            $this->entityManager->persist($ProductLucky);
            $this->entityManager->flush();

        }
    }

}
?>