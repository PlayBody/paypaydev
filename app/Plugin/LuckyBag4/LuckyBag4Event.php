<?php

namespace Plugin\LuckyBag4;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Eccube\Event\TemplateEvent;

class LuckyBag4Event implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            '@admin/Product/product.twig' => 'onRenderAdminProductEditTwig',
            'Shopping/complete.twig' => 'onRenderShoppingCompleteTwig',
        ];
    }

    public function onRenderAdminProductEditTwig(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@LuckyBag4/admin/Product/product_edit_js.twig');
    }

    public function onRenderShoppingCompleteTwig(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@LuckyBag4/Shopping/complete_js.twig');
    }

}
