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

namespace Plugin\MarketPlace4\Controller;

use Eccube\Entity\CustomerAddress;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Exception\ShoppingException;
use Eccube\Form\Type\Front\CustomerLoginType;
use Eccube\Form\Type\Front\ShoppingShippingType;
use Eccube\Form\Type\Shopping\CustomerAddressType;
use Eccube\Form\Type\Shopping\OrderType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Service\OrderHelper;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\MarketPlace4\Entity\OrderMember;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Eccube\Controller\AbstractShoppingController;
use Eccube\Repository\OrderItemRepository;
use Eccube\Repository\ShippingRepository;
use Eccube\Repository\MemberRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Repository\DeliveryFeeRepository;
use Plugin\MarketPlace4\Repository\OrderMemberRepository;
use Eccube\Entity\BaseInfo;
use Plugin\MarketPlace4\Repository\DeliveryTypeRepository;

class ShoppingExController extends AbstractShoppingController
{
    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;
    /**
     * @var OrderItemRepository
     */
    protected $orderItemRepository;
    /**
     * @var ShippingRepository
     */
    protected $shippingRepository;
    /**
     * @var MemberRepository
     */
    protected $memberRepository;
    /**
     * @var ProductClassRepository
     */
    protected $productClassRepository;
    /**
     * @var DeliveryFeeRepository
     */
    protected $deliveryFeeRepository;
    /**
     * @var DeliveryTypeRepository
     */
    protected $deliveryTypeRepository;
	
    /**
     * @var OrderMemberRepository
     */
    protected $orderMemberRepoistory;
    /**
     * @var BaseInfoRepository
     */
    protected $baseInfoRepository;

    public function __construct(
        CartService $cartService,
        MailService $mailService,
        OrderRepository $orderRepository,
        OrderHelper $orderHelper,
        OrderItemRepository $orderItemRepository,
        ShippingRepository $shippingRepository,
        MemberRepository $memberRepository,
        ProductClassRepository $productClassRepository,
        DeliveryFeeRepository $deliveryFeeRepository,
        DeliveryTypeRepository $deliveryTypeRepository,
        OrderMemberRepository $orderMemberRepository,
        BaseInfoRepository $baseInfoRepository

    ) {
        $this->cartService = $cartService;
        $this->mailService = $mailService;
        $this->orderRepository = $orderRepository;
        $this->orderHelper = $orderHelper;
        $this->orderItemRepository = $orderItemRepository;
        $this->shippingRepository = $shippingRepository;
        $this->memberRepository = $memberRepository;
        $this->productClassRepository = $productClassRepository;
        $this->deliveryTypeRepository = $deliveryTypeRepository;
        $this->deliveryFeeRepository = $deliveryFeeRepository;
        $this->orderMemberRepoistory = $orderMemberRepository;
        $this->baseInfoRepository = $baseInfoRepository;
    }

    /**
     * @Route("/shopping/market_place4_ex/summary", name="shopping_summary_market_place4_ex")
     */
    public function renderSummary(Request $request)
    {
        $BaseInfo = $this->baseInfoRepository->get();
        $deliveryFreeAmount = $BaseInfo->getDeliveryFreeAmount();

        $id = $request->get('id');

        $Order = $this->orderRepository->find($id);

        $OrderMembers = $this->orderMemberRepoistory->findBy(['Order' => $Order]);
        foreach ($OrderMembers as $om){
            $this->entityManager->remove($om);
        }
        $this->entityManager->flush();

        $OrderItems = $this->orderItemRepository->createQueryBuilder('oi')
            ->where('oi.Order = :Order')->setParameter('Order', $Order)
            ->getQuery()->getResult()
        ;

        $items = [];
        foreach ($OrderItems as $item){
            if ($item->getOrderItemType()->isProduct()){
                $items[$item->getShipping()->getId()][$item->getProductClass()->getMember()->getId()][$item->getProductClass()->getId()] = ($item->getPrice()+$item->getTax()) * $item->getQuantity();
            }
        }

        $matome_all_sum = 0;
        foreach ($items as $ship_id => $ship){

            $ship_data = $this->shippingRepository->find($ship_id);
			
            if (empty( $ship_data->getDeliveryType())){
                $DelveryType = $this->deliveryTypeRepository->find(2);
                $ship_data->setDeliveryType( $DelveryType );
                $ships['deliveryType'] = 2;
            }else{
				$ships['deliveryType'] = $ship_data->getDeliveryType()->getId();
                //$delivery_type = $shipData['shipping']->getDeliveryType()->getId();
            }
			
            

            foreach ($ship as $member_id=>$member){
                $MemberData = $this->memberRepository->find($member_id);
                $sum = 0;
                foreach ($member as $pc_id => $val){
                    $sum = $sum + $val;
                }

                if ( $ships['deliveryType']==1){

                    if ($MemberData->isMarketPlaceMatome()){
                        $matome_all_sum = $matome_all_sum + $sum;
                    }
                }
            }
        }

        $sums = [];

        $allDeliveryFeeAmount = 0;
        $allAmount = 0;
		
		$addFee = 0;
        $ii=0;
        foreach ($items as $ship_id => $ship){
            $ships = [];
            $ii++;
            if (count($items)>1){
                $ships['ship'] = 'お届け先('.$ii.')';
            }else{
                $ships['ship'] = 'お届け先';
            }
            $ships['ship_amount'] = 0;
            $ships['ship_delivery_amount'] = 0;
            $ship_delivery_amount = 0;
            $ship_amount = 0;
            $matomeSum = 0;
            $matomeCount = 0;

            $ship_data = $this->shippingRepository->find($ship_id);
            $ships['deliveryType'] = $ship_data->getDeliveryType()->getId();

            $deliveryFee = $this->deliveryFeeRepository->findOneBy(['Delivery'=>$ship_data->getDelivery(), 'Pref'=>$ship_data->getPref()]);
            $ships['deliveryAddFee'] = $deliveryFee->getAddFee();

            $members = [];
            foreach ($ship as $member_id=>$member){
                $tmp = [];
                $tmp['member'] = $this->memberRepository->find($member_id);
                $sum = 0;
                foreach ($member as $pc_id => $val){
                    $sum = $sum + $val;
                }
                $tmp['sum'] = $sum;
                $tmp['deliveryFee'] = $deliveryFee->getFee();
                $tmp['deliveryFreeFee'] = $deliveryFee->getFreeFee();
                //$tmp['deliveryType'] = $ship_data->getDeliveryType()->getId();
                $members[] = $tmp;

                $ship_amount += $tmp['sum'];

                if ( $ships['deliveryType']==2){
                    if ($tmp['sum'] < $deliveryFreeAmount){
                        $ship_delivery_amount = $ship_delivery_amount + $tmp['deliveryFee'];
                    }else{
                        $ship_delivery_amount = $ship_delivery_amount + $tmp['deliveryFreeFee'];
                    }
                }

                if ( $ships['deliveryType']==1){

                    if ($tmp['member']->isMarketPlaceMatome()){
                        $matomeCount++;
                        $matomeSum = $matomeSum + $tmp['sum'];
                    }else{
                        if ($tmp['sum'] < $deliveryFreeAmount){
                            $ship_delivery_amount = $ship_delivery_amount + $tmp['deliveryFee'];
                        }else{
                            $ship_delivery_amount = $ship_delivery_amount + $tmp['deliveryFreeFee'];
                        }
                    }
                }
                $OrderMember = new OrderMember();
                $OrderMember->setOrder($Order);
                $OrderMember->setShipping($ship_data);
                $OrderMember->setMember($tmp['member']);
                $OrderMember->setMatome($tmp['member']->isMarketPlaceMatome());
                if ( $ships['deliveryType']!=1){
                    if ($tmp['sum'] < $deliveryFreeAmount) {
                        $OrderMember->setAmount($tmp['deliveryFee']);
                    }else{
                        $OrderMember->setAmount($tmp['deliveryFreeFee']);
                    }
                }
                $this->entityManager->persist($OrderMember);
                $this->entityManager->flush();
            }

            if ( $ships['deliveryType']==1) {
                if ($matome_all_sum < $deliveryFreeAmount){
                    $ship_delivery_amount = $ship_delivery_amount + $deliveryFee->getFee();
                }else{
                    $ship_delivery_amount = $ship_delivery_amount + $deliveryFee->getFreeFee();
                }
                foreach ($ship as $member_id=>$member){
                    $ItemMember = $this->memberRepository->find($member_id);

                    $OrderMember = $this->orderMemberRepoistory->findOneBy(['Shipping'=>$ship_data, 'Member' => $ItemMember]);
                    if ($matomeSum < $deliveryFreeAmount){
                        $OrderMember->setAmount($deliveryFee->getFee());
                    }else{
                        $OrderMember->setAmount($deliveryFee->getFreeFee());
                    }
                    $this->entityManager->persist($OrderMember);
                    $this->entityManager->flush();
                }
            }
            $ships['matome_count'] = $matomeCount;
            $ships['matome_amount'] = $matome_all_sum;

            $ships['ship_delivery_amount'] = $ship_delivery_amount;
            $ships['ship_amount'] = $ship_amount;
            $ships['members'] = $members;
            $sums[] = $ships;
            $allAmount = $allAmount + $ship_amount;
            $allDeliveryFeeAmount  = $allDeliveryFeeAmount + $ship_delivery_amount;
			
			$addFee = $addFee + +$ships['deliveryAddFee'];
        }

        $view = 'MarketPlace4/Resource/template/Shopping/shopping_index_summary_ex.twig';
        return $this->render($view, array(
            'allAmount'=> $allAmount + $allDeliveryFeeAmount ,
            'allDeliveryFee'=> $allDeliveryFeeAmount,
            'Order'=>$Order,
            'sums' => $sums,
            'deliveryFee' => $allDeliveryFeeAmount,
            'addFee' => $addFee
        ));
    }
}
