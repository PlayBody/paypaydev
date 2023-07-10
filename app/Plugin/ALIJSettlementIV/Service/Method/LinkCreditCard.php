<?php

namespace Plugin\ALIJSettlementIV\Service\Method;

use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Exception\ShoppingException;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseException;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\ALIJSettlementIV\Repository\ConfigRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Plugin\ALIJSettlementIV\Service\Assistant\ContAssist;
use Eccube\Common\Constant;
use Eccube\Repository\PluginRepository;
use Eccube\Service\Payment\PaymentResult;

class LinkCreditCard implements PaymentMethodInterface
{
    /**
     * @CONST 決済URL
     */
    const ProductionURL = "https://payment.alij.ne.jp/service/credit";
    const TestURL = "https://test-payment.alij.ne.jp/service/credit";

    /**
     * @var Order
     */
    private $Order;

    /**
     * @var FormInterface
     */
    private $form;

    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * @var PurchaseFlow
     */
    private $purchaseFlow;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var ContAssist
     */
    private $contInfo;

    /**
     * @var
     */
    private $pluginRepository;

    /**
     * LinkCreditCard constructor.
     *
     * @param OrderStatusRepository $orderStatusRepository
     * @param PurchaseFlow $shoppingPurchaseFlow
     * @param ConfigRepository $configRepository
     * @param SessionInterface $session
     * @param ContAssist $contInfo
     * @param PluginRepository $pluginRepository
     */
    public function __construct(
        OrderStatusRepository $orderStatusRepository,
        PurchaseFlow $shoppingPurchaseFlow,
        ConfigRepository $configRepository,
        SessionInterface $session,
        ContAssist $contInfo,
        PluginRepository $pluginRepository
    ) {
        $this->orderStatusRepository = $orderStatusRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->configRepository = $configRepository;
        $this->session = $session;
        $this->contInfo = $contInfo;
        $this->pluginRepository = $pluginRepository;
    }

    public function setFormType(FormInterface $form)
    {
        $this->form = $form;
    }

    public function setOrder(Order $Order)
    {
        $this->Order = $Order;
    }

    /**
     * 注文時に呼び出される.
     *
     * 決済サーバのカード入力画面へリダイレクトする.
     *
     * @return PaymentDispatcher
     *
     * @throws PurchaseException
     * @throws ShoppingException
     */
    public function apply()
    {
        // 送信パラメータの設定
        $parameters = http_build_query($this->setParameters());

        // 受注ステータスを購入処理中へ変更
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PROCESSING);
        $this->Order->setOrderStatus($OrderStatus);
        // 受注ステータスを決済処理中へ変更
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);
        $this->Order->setOrderStatus($OrderStatus);

        // purchaseFlow::prepareを呼び出し, 購入処理を進める.
        $this->purchaseFlow->prepare($this->Order, new PurchaseContext());

        // 決済サーバのカード入力画面へリダイレクトする.
        $siteConfig = $this->configRepository->get();
        if ($siteConfig->getUseTest() == '1') {
            $url = self::TestURL . '?' . $parameters;
        } else {
            $url = self::ProductionURL . '?' . $parameters;
        }
        $response = new RedirectResponse($url);
        $dispatcher = new PaymentDispatcher();
        $dispatcher->setResponse($response);

        logs('ALIJSettlementIV')->info("パラメータを送信します。" . $parameters);

        return $dispatcher;
    }

    /**
     * 送信パラメータの設定
     *
     * @return array
     * @throws ShoppingException
     */
    public function setParameters ()
    {
        $siteConfig = $this->configRepository->get();

        $arrParameter = array(
            'SiteId' => $siteConfig->getSiteId(),
            'SitePass' => $siteConfig->getSitePassword(),
            'amount' => $this->Order->getTotal(),
            'max_cont' => '1',
            'SiteTransactionId' => $this->Order->getOrderNo(),
            'mail' => $this->Order->getEmail(),
            'cartKey' => $this->session->get('cart_keys', []),
            'eccube_version' => Constant::VERSION,
            'plugin_version' => $this->pluginRepository->findOneBy(array('code' => 'ALIJSettlementIV'))['version'],
        );

        switch ($this->contInfo->getPayType($this->Order))
        {
            case 'cont':
                $arrParameter = $this->setContParameters($arrParameter);
                break;
            case 'onePay':
                if($siteConfig->getUseQuick() === true && !is_null($this->Order->getCustomer())) {
                    $arrParameter = $this->setQuickParameters($arrParameter);
                }
                break;
            case 'error':
                throw new ShoppingException("定期商品は個別で購入してください");
                break;
            default:
                logs('ALIJSettlementIV')->info("未知のエラーです。");
        }

        return $arrParameter;
    }

    /**
     * 継続パラメータの設定
     *
     * @param $arrParameter
     * @return array
     */
    public function setContParameters ($arrParameter)
    {
        $contParameter = array(
            'amount2' => $this->contInfo->calcAmount2($this->Order),
            'max_cont' => $this->contInfo->getContTimes($this->Order),
        );

        return array_merge($arrParameter, $contParameter);
    }

    /**
     * クイックチャージパラメータの設定
     *
     * @param $arrParameter
     * @return array
     */
    public function setQuickParameters ($arrParameter)
    {
        $quickParameter = array(
            'CustomerId' => $this->Order->getCustomer()->getId(),
            'CustomerPass' => $this->Order->getCustomer()->getPhoneNumber(),
        );
        return array_merge($arrParameter, $quickParameter);
    }

    /**
     * 有効性チェック
     *
     * @return PaymentResult
     */
    public function verify()
    {
        //継続商品を購入する有効性チェック
        if($this->contInfo->getPayType($this->Order) == 'error')
        {
            $result = new PaymentResult();
            $result->setSuccess(false);
            $result->setErrors(['定期商品は個別で購入してください']);
            return $result;
        }
    }

    public function checkout()
    {
        // TODO: Implement checkout() method.
    }
}