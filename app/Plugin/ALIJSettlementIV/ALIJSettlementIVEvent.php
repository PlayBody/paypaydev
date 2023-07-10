<?php

namespace Plugin\ALIJSettlementIV;

use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin\ALIJSettlementIV\Repository\ConfigRepository;

class ALIJSettlementIVEvent implements EventSubscriberInterface {

    public $configRepository;

    public function __construct(
        ConfigRepository $configRepository
    )
    {
        $this->configRepository = $configRepository;
    }

    public static function getSubscribedEvents()
    {
        /*
        return[
        '@admin/Product/product_class.twig' => 'useContProductClass',
        '@admin/Product/product.twig' => 'useContProduct',
        ];
        */
        return[];
    }

    public function useContProductClass(TemplateEvent $event)
    {
        $siteConfig = $this->configRepository->get();
        $event->setParameter("useCont", $siteConfig->getUseCont());
        $event->addSnippet('ALIJSettlementIV/Resource/template/admin/use_cont_product_class.twig');
    }

    public function useContProduct(TemplateEvent $event)
    {
        $siteConfig = $this->configRepository->get();
        $event->setParameter("useCont", $siteConfig->getUseCont());
        $event->addSnippet('ALIJSettlementIV/Resource/template/admin/use_cont_product.twig');
    }
}