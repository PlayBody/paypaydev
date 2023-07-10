<?php

namespace Plugin\ProductDisplayRank4;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Eccube\Entity\Product;
use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Event implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            '@admin/Product/index.twig' => 'onAdminProductIndexTwig',
            Events::preUpdate => 'postPersist',
            Events::postPersist => 'postPersist',
        ];
    }

    public function onAdminProductIndexTwig(TemplateEvent $event)
    {
        $event->addSnippet('@ProductDisplayRank4/admin/Product/index_js.twig');
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        /* @var $Product Product */
        $Product = $args->getObject();

        if ($Product instanceof Product) {
            if (is_null($Product->getDisplayRank())) {

                $Product->setDisplayRank(0);
            }
        }
    }
}
