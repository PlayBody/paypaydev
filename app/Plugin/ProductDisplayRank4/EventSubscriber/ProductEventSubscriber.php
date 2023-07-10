<?php

namespace Plugin\ProductDisplayRank4\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Eccube\Entity\Product;

class ProductEventSubscriber implements EventSubscriber
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * TaxRuleEventSubscriber constructor.
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
        ];
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        /* @var $Product Product */
        $Product = $args->getObject();
        if ($Product instanceof Product) {
            if (is_null($Product->getDisplayRank())) {
                $Config = $this->entityManager->getRepository('Plugin\ProductDisplayRank4\Entity\Config')->find(1);
                $Product->setDisplayRank(
                    ($Config && !is_null($Config->getCsvImportDefaultRank())) ? $Config->getCsvImportDefaultRank() : 0
                );
            }
        }
    }
}
