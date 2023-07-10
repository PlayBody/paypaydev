<?php

namespace Plugin\ProductFilter4;

use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductFilter4Event implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Product/list.twig' => 'onRenderTemplateProductListTwig',
        ];
    }

    public function onRenderTemplateProductListTwig(TemplateEvent $event)
    {
        $event->addSnippet('@ProductFilter4/Product/product_filter_left_js.twig');
    }
}
