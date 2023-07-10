<?php

namespace Plugin\MarketPlace4;

use Eccube\Event\TemplateEvent;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Plugin\MarketPlace4\Service\TwigRenderService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Eccube\Repository\MemberRepository;

class MarketPlace4Event implements EventSubscriberInterface
{
    /**
     * @var TwigRenderService
     */
    protected $twigRenderService;

    /**
     * EventSubscriber constructor.
     *
     * @param TwigRenderService $twigRenderService
     */
    public function __construct(
        TwigRenderService $twigRenderService
    ) {
        $this->twigRenderService = $twigRenderService;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            '@admin/Product/product_class.twig' => 'onRenderTemplateAdminProductClassTwig',
            '@admin/Product/index.twig' => 'onRenderAdminProductIndexTwig',
            '@admin/Product/product.twig' => 'onRenderAdminProductDetailTwig',
            '@admin/Setting/System/member.twig' => 'onRenderTemplateAdminMemberIndex',
            '@admin/Setting/System/member_edit.twig' => 'onRenderTemplateAdminMemberEdit',
            '@admin/Setting/Shop/delivery_edit.twig' => 'onRenderTemplateAdminDeliveryEdit',
            '@admin/Order/index.twig' => 'onRenderTemplateAdminOrderIndexTwig',
            '@admin/Order/edit.twig' => 'onRenderTemplateAdminOrderEditTwig',
            'Product/list.twig' => 'onRenderTemplateProductListTwig',
            'Product/detail.twig' => 'onRenderTemplateProductDetailTwig',
            'Cart/index.twig' => 'onRenderTemplateCartIndex',
            'Shopping/index.twig' => 'onRenderTemplateShoppingIndex',
            'Shopping/confirm.twig' => 'onRenderTemplateShoppingConfirm',
        ];
    }
    public function onRenderAdminProductIndexTwig(TemplateEvent $event)
    {
        $event->addSnippet('@MarketPlace4/admin/Product/product_index_js.twig');
    }

    public function onRenderTemplateAdminProductClassTwig(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@MarketPlace4/admin/Product/product_class_js.twig');
    }

    public function onRenderAdminProductDetailTwig(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@MarketPlace4/admin/Product/product_edit_js.twig');
    }

    public function onRenderTemplateAdminMemberIndex(TemplateEvent $templateEvent)
    {
        // if (!$this->configService->isKeyBool(ConfigSetting::SETTING_KEY_LIST_VIEW)) {
        //     // 一覧表示がOFFの場合
        //     return;
        // }

        $insertPositionIndex = 2;

        // Title追加
        $this->twigRenderService
            ->initRenderService($templateEvent)
            ->insertBuilder()
            ->find('table > thead > tr >th')
            ->eq($insertPositionIndex)
            ->setTargetId('plugin_emailex_member_index_title')
            ->setInsertModeAfter();
        $this->twigRenderService
            ->initRenderService($templateEvent)
            ->insertBuilder()
            ->find('table > thead > tr >th')
            ->eq($insertPositionIndex+1)
            ->setTargetId('plugin_matomeex_member_index_title')
            ->setInsertModeAfter();


        // list 追加
        /** @var SlidingPagination $pagination */
        $Members = $templateEvent->getParameter('Members');

        if($Members) {
            foreach ($Members as $item) {

                $this->twigRenderService
                    ->insertBuilder()
                    ->find('#ex-member-' . $item->getId())
                    ->find('td')
                    ->eq($insertPositionIndex)
                    ->setTargetId('plugin_emailex_member_index_list_' . $item->getId())
                    ->setInsertModeAfter();
                $this->twigRenderService
                    ->insertBuilder()
                    ->find('#ex-member-' . $item->getId())
                    ->find('td')
                    ->eq($insertPositionIndex+1)
                    ->setTargetId('plugin_matomeex_member_index_list_' . $item->getId())
                    ->setInsertModeAfter();
            }
        }

        $this->twigRenderService->addSupportSnippet('@MarketPlace4/admin/Setting/System/member_index_ex.twig');
    }

    public function onRenderTemplateAdminMemberEdit(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@MarketPlace4/admin/Setting/System/member_edit_ex.twig');
    }

    public function onRenderTemplateAdminOrderIndexTwig(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@MarketPlace4/admin/Order/order_index_js.twig');
    }
    public function onRenderTemplateAdminOrderEditTwig(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@MarketPlace4/admin/Order/order_edit_js.twig');
    }
    public function onRenderTemplateProductListTwig(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@MarketPlace4/Product/product_list_js.twig');
    }
    public function onRenderTemplateProductDetailTwig(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@MarketPlace4/Product/product_detail_js.twig');
    }


    public function onRenderTemplateCartIndex(TemplateEvent $templateEvent)
    {

        $templateEvent->addSnippet('@MarketPlace4/Cart/cart_index_js.twig');
    }

    public function onRenderTemplateAdminDeliveryEdit(TemplateEvent $templateEvent){
        $templateEvent->addSnippet('@MarketPlace4/admin/Setting/Shop/delivery_edit_ex.twig');
    }


    public function onRenderTemplateShoppingIndex(TemplateEvent $templateEvent)
    {
        $Order = $templateEvent->getParameter('Order');
        $form = $templateEvent->getParameter('form');
        $templateEvent->addSnippet('@MarketPlace4/Shopping/shopping_index_js.twig');
    }
    public function onRenderTemplateShoppingConfirm(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@MarketPlace4/Shopping/shopping_confirm_js.twig');
    }
}
