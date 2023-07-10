<?php

namespace Plugin\ALIJSettlementIV\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Service\CartService;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseException;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Service\ShoppingService;
use Plugin\ALIJSettlementIV\Repository\ConfigRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Eccube\Service\MailService;
use Plugin\ALIJSettlementIV\Service\Assistant\OrderAssist;
use Plugin\ALIJSettlementIV\Service\Assistant\ErrorCheck;



class PaymentController extends AbstractController
{
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var PurchaseFlow
     */
    protected $purchaseFlow;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var
     */
    protected $orderAssist;

    /**
     * @var
     */
    protected $errorCheck;

    /**
     * PaymentController constructor.
     *
     * @param OrderRepository $orderRepository
     * @param OrderStatusRepository $orderStatusRepository
     * @param ConfigRepository $configRepository
     * @param PurchaseFlow $shoppingPurchaseFlow
     * @param CartService $cartService
     * @param MailService $mailService
     * @param OrderAssist $orderAssist
     * @param ErrorCheck $errorCheck
     */
    public function __construct(
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository,
        ConfigRepository $configRepository,
        PurchaseFlow $shoppingPurchaseFlow,
        CartService $cartService,
        MailService $mailService,
        OrderAssist $orderAssist,
        ErrorCheck $errorCheck
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->configRepository = $configRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->cartService = $cartService;
        $this->mailService = $mailService;
        $this->orderAssist = $orderAssist;
        $this->errorCheck = $errorCheck;
    }

    /**
     * @Route("/ALIJ_cancel", name="ALIJ_cancel")
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function back(Request $request)
    {
        if($_POST == null){
            throw new NotFoundHttpException();
        }
        $callback = $_POST;
        $orderNo = $callback['SiteTransactionId'];
        $Order = $this->orderAssist->getOrderByNo($orderNo);

        if (!$Order) {
            logs('ALIJSettlementIV')->info("該当オーダーが見つかりませんでした。");
            throw new NotFoundHttpException();
        }

        if ($this->getUser() != $Order->getCustomer()) {
            logs('ALIJSettlementIV')->info("ユーザが一致しませんでした。");
            throw new NotFoundHttpException();
        }

        // purchaseFlow::rollbackを呼び出し, 購入処理をロールバックする.
        $this->purchaseFlow->rollback($Order, new PurchaseContext());

        // 受注ステータスを購入処理中へ変更,決済ステータスを未決済へ変更
        $this->orderAssist->changeStatus($Order, OrderStatus::PROCESSING);

        return $this->redirectToRoute('shopping');
    }

    /**
     * 完了画面へ遷移する.
     *
     * @Route("/ALIJ_complete", name="ALIJ_complete")
     */
    public function complete(Request $request)
    {
        if($_POST == null){
            logs('ALIJSettlementIV')->info("決済サーバからデータを正しく受け取れませんでした。");
            throw new NotFoundHttpException();
        }
        $callback = $_POST;
        $orderNo = $callback['SiteTransactionId'];
        $Order = $this->orderAssist->getOrderByNo($orderNo);

        if (!$Order) {
            logs('ALIJSettlementIV')->info("該当オーダーが見つかりませんでした。");
            throw new NotFoundHttpException();
        }

        if ($this->getUser() != $Order->getCustomer()) {
            logs('ALIJSettlementIV')->info("ユーザが一致しませんでした。");
            throw new NotFoundHttpException();
        }

        $this->session->set('eccube.front.shopping.order.id', $Order->getId());

        $this->entityManager->flush();

        return $this->redirectToRoute('shopping_complete');
    }

    /**
     * 結果通知URLを受け取る.
     *
     * @Route("/ALIJ_receive_complete", name="ALIJ_receive_complete")
     *
     * @throws PurchaseException
     */
    public function receiveComplete(Request $request)
    {
        if($_GET == null){
            logs('ALIJSettlementIV')->err("決済サーバからデータを正しく受け取れませんでした。");
            return new Response("Null Parameter error");
        }
        $callback = $_GET;
        logs('ALIJSettlementIV')->info("決済サーバからデータを受け取りました。");
        // 決済会社から受注番号を受け取る
        $orderNo = $callback['SiteTransactionId'];
        $Order = $this->orderAssist->getOrderByNo($orderNo);
        // オーダーチェック
        if (!$Order) {
            logs('ALIJSettlementIV')->err("該当オーダーが見つかりませんでした。");
            return new Response("Order error");
        }

        $siteConfig = $this->configRepository->get();
        // 決済金額チェック
        if ($this->errorCheck->checkAmount($siteConfig, $Order, $callback)){
            logs('ALIJSettlementIV')->warn("オーダー金額と決済金額が一致していません。");
            return new Response("Amount error");
        }

        // IPチェック
        if ($this->errorCheck->checkIP($siteConfig, $request)){
            logs('ALIJSettlementIV')->warn("IPアドレスが確認できませんでした。", array('リモートIP',$request->getClientIp()));
            return new Response("IP error");
        }

        $this->session->set("cart_keys",$callback['cartKey']);

        switch($callback['saleResult'])
        {
            case 'SALE':
                //クレジット支払いの場合
                if($callback['Result'] == 'OK')
                {
                    $this->cartService->clear();
                    // purchaseFlow::commitを呼び出し, 購入処理を完了させる.
                    $this->purchaseFlow->commit($Order, new PurchaseContext());
                    $this->mailService->sendOrderMail($Order);
                    $Order->setPaymentMethod($callback['paymentMethodName']);
                }
                //$this->orderAssist->changeStatus($Order, OrderStatus::NEW);
                $this->orderAssist->changeStatus($Order, OrderStatus::PAID);
                logs('ALIJSettlementIV')->info("決済が成功しました。");
                return new Response('SALE OK!!');
            case 'PAYWAIT':
                //銀振の入金不足と入金超過の場合はスキップ
                if(array_key_exists('Result', $callback) && 'PAYWAIT' != $callback['Result'] ) {
                    break;
                }

                $this->cartService->clear();
                // purchaseFlow::commitを呼び出し, 購入処理を完了させる.
                $this->purchaseFlow->commit($Order, new PurchaseContext());
                $Order->setPaymentMethod($callback['paymentMethodName']);
                $this->orderAssist->changeStatus($Order, OrderStatus::NEW);
                $this->mailService->sendOrderMail($Order);
                logs('ALIJSettlementIV')->info("申込が成功しました。");
                return new Response('ENTRY OK!!');
                break;
            default:
                logs('ALIJSettlementIV')->info("未知のエラーが発生されました。");
                return new Response('UNKNOWN NG!!');
        }
    }

}