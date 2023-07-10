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

namespace Plugin\ProductFilter4;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Layout;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Form\Type\Master\MailTemplateType;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\PageRepository;
use Eccube\Entity\Master\CsvType;
use Plugin\ProductFilter4\Entity\ProductFilter4Config;
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
        $max_price = $Config->getMaxPrice();
        if (null === $max_price) {
            $Config->setMaxPrice('10000');
            $em->persist($Config);
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

        $Config = $em->find(ProductFilter4Config::class, 1);

        $this->removeResourceFiles($container);

    }

    protected function createConfig(EntityManagerInterface $em)
    {
        $Config = $em->find(ProductFilter4Config::class, 1);
        if ($Config) {
            return $Config;
        }
        $Config = new ProductFilter4Config();
        $Config->setName('ProductFilter4');
        $Config->setMaxPrice('10000');

        $em->persist($Config);
        $em->flush($Config);

        return $Config;
    }

    protected function copyResourceFiles(ContainerInterface $container)
    {
        $templatePath = $container->getParameter('eccube_html_dir')
            .'/plugin/ProductFilter4/assets/';
        $fs = new Filesystem();
        if ($fs->exists($templatePath)) {
            return;
        }
        $fs->mkdir($templatePath);
        $fs->mirror(__DIR__.'/Resource/assets/', $templatePath);
    }

    protected function removeResourceFiles(ContainerInterface $container)
    {
        $templatePath = $container->getParameter('eccube_html_dir')
            .'/plugin/ProductFilter4';
        $fs = new Filesystem();
        $fs->remove($templatePath);
    }
}
