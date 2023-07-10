<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\LuckyBag4;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Layout;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\Master\SaleType;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Form\Type\Master\MailTemplateType;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\PageRepository;
use Eccube\Entity\Master\CsvType;
use Plugin\LuckyBag4\Entity\LuckyBag4Config;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class PluginManager extends AbstractPluginManager
{
    /**
     * プラグイン有効時の処理
     *
     * @param $meta
     * @param ContainerInterface $container
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');

        $Config = $this->createConfig($em);
        $SaleType = $Config->getSaleType();
        if (null === $SaleType) {
            $SaleType = $this->createSaleType($em);

            $Config->setSaleType($SaleType);
            $em->persist($Config);
            $em->flush($Config);
        }

        $MailTemplate = $Config->getMailTemplate();
        if (null === $MailTemplate) {
            $MailTemplate = $this->createMailTemaplate($em);
            $Config->setMailTemplate($MailTemplate);
            $em->flush($Config);
        }

        $this->copyResourceFiles($container);
    }

    public function disable(array $meta, ContainerInterface $container)
    {

    }

    public function uninstall(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');

        $Config = $em->find(LuckyBag4Config::class, 1);

        if ($Config) {
            $SaleType = $Config->getSaleType();
            if ($SaleType){
                $em->remove($SaleType);
                $Config->setSaleType(null);

                $em->flush($Config);
            }

            $MailTemplate = $Config->getMailTemplate();
            if ($MailTemplate){
                $em->remove($MailTemplate);

                $Config->setMailTemplate(null);
                $em->flush($Config);
            }
        }
        $this->removeResourceFiles($container);
    }

    protected function createConfig(EntityManagerInterface $em)
    {
        $Config = $em->find(LuckyBag4Config::class, 1);
        if ($Config) {
            return $Config;
        }

        $Config = new LuckyBag4Config();
        $Config->setName('LuckyBag4');
        $Config->setProductLuckyMax('10');
        $Config->setAddPoint('10');

        $em->persist($Config);
        $em->flush($Config);

        return $Config;
    }

    protected function createSaleType(EntityManagerInterface $em)
    {
        $SaleType = $em->getRepository(SaleType::class)->findOneBy(['name' => '福袋専用']);

        if (!empty($SaleType)) return $SaleType;

        $result = $em->createQueryBuilder('ct')
            ->select('COALESCE(MAX(st.id), 0) AS id, COALESCE(MAX(st.sort_no), 0) AS sort_no')
            ->from(SaleType::class, 'st')
            ->getQuery()
            ->getSingleResult();

        $result['id']++;
        $result['sort_no']++;

        $SaleType = new SaleType();
        $SaleType
            ->setId($result['id'])
            ->setName('福袋専用')
            ->setSortNo($result['sort_no']);
        $em->persist($SaleType);
        $em->flush($SaleType);

        return $SaleType;
    }

    protected function copyResourceFiles(ContainerInterface $container)
    {
        $fs = new Filesystem();
        $fs->mirror(__DIR__.'/Resource/config/', __DIR__.'/../../config/eccube/packages/');

        $templatePath = $container->getParameter('eccube_html_dir')
            .'/plugin/LuckyBag4/assets/';
        if ($fs->exists($templatePath)) {
            return;
        }
        $fs->mkdir($templatePath);
        $fs->mirror(__DIR__.'/Resource/assets/', $templatePath);
    }

    protected function removeResourceFiles(ContainerInterface $container)
    {
        $templatePath = $container->getParameter('eccube_html_dir')
            .'/plugin/LuckyBag4';
        $fs = new Filesystem();
        $fs->remove($templatePath);
    }

    protected function createMailTemaplate(EntityManagerInterface $em)
    {
        $MailTemplate = $em->getRepository(MailTemplate::class)->findOneBy(['name' => '福袋当選商品メール']);

        if (!empty($MailTemplate)) return $MailTemplate;

        $MailTemplate = new MailTemplate();
        $MailTemplate->setName('福袋当選商品メール');
        $MailTemplate->setFileName('LuckyBag4/Resource/template/admin/Mail/lucky.twig');
        $MailTemplate->setMailSubject('ご注文ありがとうございます');
        $em->persist($MailTemplate);
        $em->flush($MailTemplate);

        return $MailTemplate;
    }
}
