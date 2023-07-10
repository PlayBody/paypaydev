<?php

namespace Plugin\ALIJSettlementIV;

use Eccube\Entity\Payment;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\PluginRepository;
use Eccube\Service\PluginService;
use Plugin\ALIJSettlementIV\Entity\Config;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Plugin\ALIJSettlementIV\Service\Method\LinkCreditCard;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginManager extends AbstractPluginManager
{
    public function enable(array $meta, ContainerInterface $container)
    {
        $this->createLinkPayment($container);
        $this->createConfig($container);
    }

    // アップデート時
    public function update(array $meta, ContainerInterface $container)
    {
        $Plugin = $container->get(PluginRepository::class)->findByCode('ALIJSettlementIV');
        $PluginService = $container->get(PluginService::class);

        if (!$Plugin) {
            throw new NotFoundHttpException();
        }

        $config = $PluginService->readConfig($PluginService->calcPluginDir($Plugin->getCode()));

        $PluginService->generateProxyAndUpdateSchema($Plugin, $config);
    }

    private function createLinkPayment(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $paymentRepository = $container->get(PaymentRepository::class);

        $Payment = $paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $Payment ? $Payment->getSortNo() + 1 : 1;

        $Payment = $paymentRepository->findOneBy(['method_class' => LinkCreditCard::class]);
        if ($Payment) {
            return;
        }

        $Payment = new Payment();
        $Payment->setCharge(0);
        $Payment->setSortNo($sortNo);
        $Payment->setVisible(true);
        $Payment->setMethod('アナザーレーン決済(リンク式)');
        $Payment->setMethodClass(LinkCreditCard::class);

        $entityManager->persist($Payment);
        $entityManager->flush($Payment);
    }

    private function createConfig(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $Config = $entityManager->find(Config::class, 1);
        if ($Config) {
            return;
        }

        $Config = new Config();
        $Config->setSiteId('');
        $Config->setSitePassword('');
        $Config->setUseCont(false);
        $Config->setUseQuick(false);
        $Config->setUseTest(false);
        $Config->setUseAmountCheck(true);
        $Config->setServerIP('0.0.0.0');

        $entityManager->persist($Config);
        $entityManager->flush($Config);
    }
}