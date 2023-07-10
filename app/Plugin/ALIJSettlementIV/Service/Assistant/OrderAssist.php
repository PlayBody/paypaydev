<?php

namespace Plugin\ALIJSettlementIV\Service\Assistant;

use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Entity\Order;
use Eccube\Controller\AbstractController;
use Eccube\Repository\OrderRepository;

class OrderAssist extends AbstractController
{
    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    public function __construct(
        OrderStatusRepository $orderStatusRepository,
        OrderRepository $orderRepository
    ) {
        $this->orderStatusRepository = $orderStatusRepository;
        $this->orderRepository = $orderRepository;
    }


    /**
     * 注文番号で受注を検索する.
     *
     * @param $orderNo
     *
     * @return Order
     */
    public function getOrderByNo($orderNo)
    {
        /** @var Order $Order */
        $Order = $this->orderRepository->findOneBy([
            'order_no' => $orderNo,
        ]);
        return $Order;
    }

    /**
     * オーダーステータスの変更
     *
     * @param $Order
     * @param $orderStatus
     */
    public function changeStatus(Order $Order, $orderStatus)
    {
        $OrderStatus = $this->orderStatusRepository->find($orderStatus);
        $Order->setOrderStatus($OrderStatus);
        switch ($orderStatus) {
            case '6': // 入金済へ
                $Order->setPaymentDate(new \DateTime());
                break;
        }
        $this->entityManager->flush();
    }

    public function getBankStatus($bankStatus)
    {
        switch ($bankStatus){
            case 'PAYWAIT':
                return '入金待ち';
            case 'LESS':
                return '入金不足';
            case 'MORE':
                return '入金超過';
            default:
                return '不明な状態です';
        }
    }
}