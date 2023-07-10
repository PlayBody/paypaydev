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

namespace Plugin\MarketPlace4;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Layout;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Form\Type\Master\MailTemplateType;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\PageRepository;
use Eccube\Entity\Master\CsvType;
use Plugin\MarketPlace4\Entity\DeliveryType;
use Plugin\MarketPlace4\Entity\MarketPlace4Config;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Eccube\Entity\Csv;
use Eccube\Entity\Product;

class PluginManager extends AbstractPluginManager
{
    private $pages = [
        [
            'name' => 'ProductDetailShop',
            'url' => 'product_ex_detail_shop',
            'filename' => 'Product/detail',
        ],
    ];
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

        $MailTemplate = $Config->getMailTemplate();
        if (null === $MailTemplate) {
            $MailTemplate = $this->createMailTemaplate($em);
            $Config->setMailTemplate($MailTemplate);
            $em->flush($Config);
        }
        // CSV出力項目設定を追加
        $CsvType = $Config->getCsvType();
        if (null === $CsvType) {
            $CsvType = $this->createCsvType($em);
            $this->createCsvData($em, $CsvType);

            $Config->setCsvType($CsvType);
            $em->flush($Config);
        }


        $this->createDeliveryType($em);
        $mailType = $this->createMailTemaplate($em);

        //$Config = $this->createConfig($em);
        // ページを追加
        foreach ($this->pages as $pageInfo) {
            $Page = $container->get(PageRepository::class)->findOneBy(['url' => $pageInfo['url']]);
            if (null === $Page) {
                $this->createPage($em, $pageInfo['name'], $pageInfo['url'], $pageInfo['filename']);
            }
        }

        $this->copyResourceFiles($container);

    }

    public function disable(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');

        // ページを削除
        foreach ($this->pages as $pageInfo) {
            $this->removePage($em, $pageInfo['url']);
        }

    }

    public function uninstall(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');

        $Config = $em->find(MarketPlace4Config::class, 1);

        if ($Config) {
            $MailTemplate = $Config->getMailTemplate();
            if ($MailTemplate){
                $em->remove($MailTemplate);

                $Config->setMailTemplate(null);
                $em->flush($Config);
            }

            $CsvType = $Config->getCsvType();
            if ($CsvType){

                $this->removeCsvData($em, $CsvType);

                $Config->setCsvType(null);
                $em->flush($Config);

                $em->remove($CsvType);
                $em->flush($CsvType);
            }
        }

        $Products = $em->getRepository(Product::class)->findAll();
        foreach ($Products as $Product){
            foreach ($Product->getProductClasses() as $ProductClass){
                if ($ProductClass->getClassCategory1()){
                    $ProductClass->setVisible(false);
                    $em->persist($ProductClass);
                }else{
                    $ProductClass->setVisible(true);
                    $em->persist($ProductClass);
                }
            }
        }
        $em->flush();

    }

    protected function createConfig(EntityManagerInterface $em)
    {
        $Config = $em->find(MarketPlace4Config::class, 1);
        if ($Config) {
            return $Config;
        }
        $Config = new MarketPlace4Config();
        $Config->setName('MarketPlace4');

        $em->persist($Config);
        $em->flush($Config);

        return $Config;
    }

    protected function createPage(EntityManagerInterface $em, $name, $url, $filename)
    {
        $Page = new Page();
        $Page->setEditType(Page::EDIT_TYPE_DEFAULT);
        $Page->setName($name);
        $Page->setUrl($url);
        $Page->setFileName($filename);

        // DB登録
        $em->persist($Page);
        $em->flush($Page);
        $Layout = $em->find(Layout::class, Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);
        $PageLayout = new PageLayout();
        $PageLayout->setPage($Page)
            ->setPageId($Page->getId())
            ->setLayout($Layout)
            ->setLayoutId($Layout->getId())
            ->setSortNo(0);

        $em->persist($PageLayout);
        $em->flush($PageLayout);
    }

    protected function removePage(EntityManagerInterface $em, $url)
    {
        $Page = $em->getRepository(Page::class)->findOneBy(['url' => $url]);

        if (!$Page) {
            return;
        }
        foreach ($Page->getPageLayouts() as $PageLayout) {
            $em->remove($PageLayout);
            $em->flush($PageLayout);
        }

        $em->remove($Page);
        $em->flush($Page);
    }

    protected function createDeliveryType(EntityManagerInterface $em)
    {
        $Type = $em->find(DeliveryType::class, 1);
        if ($Type) {
            return;
        }

        $Type = new DeliveryType();
        $Type->setId(1);
        $Type->setName('おまとめ便');

        $em->persist($Type);
        $em->flush($Type);

        $Type = new DeliveryType();
        $Type->setId(2);
        $Type->setName('お急ぎ便');

        $em->persist($Type);
        $em->flush($Type);
    }

    protected function createMailTemaplate(EntityManagerInterface $em)
    {
        $MailTemplate = $em->getRepository(MailTemplate::class)->findOneBy(['name' => '注文受付メンバーメール']);

        if (!empty($MailTemplate)) return $MailTemplate;

        $MailTemplate = new MailTemplate();
        $MailTemplate->setName('注文受付メンバーメール');
        $MailTemplate->setFileName('MarketPlace4/Resource/template/Mail/order.twig');
        $MailTemplate->setMailSubject('ご注文ありがとうございます');
        $em->persist($MailTemplate);
        $em->flush($MailTemplate);

        return $MailTemplate;
    }

    protected function createCsvType(EntityManagerInterface $em)
    {
        $result = $em->createQueryBuilder('ct')
            ->select('COALESCE(MAX(ct.id), 0) AS id, COALESCE(MAX(ct.sort_no), 0) AS sort_no')
            ->from(CsvType::class, 'ct')
            ->getQuery()
            ->getSingleResult();

        $result['id']++;
        $result['sort_no']++;

        $CsvType = new CsvType();
        $CsvType
            ->setId($result['id'])
            ->setName('商品在庫CSV')
            ->setSortNo($result['sort_no']);
        $em->persist($CsvType);
        $em->flush($CsvType);

        return $CsvType;
    }

    protected function removeCsvData(EntityManagerInterface $em, CsvType $CsvType)
    {
        $CsvData = $em->getRepository(Csv::class)->findBy(['CsvType' => $CsvType]);
        foreach ($CsvData as $Csv) {
            $em->remove($Csv);
            $em->flush($Csv);
        }
    }

    protected function createCsvData(EntityManagerInterface $em, CsvType $CsvType)
    {
        $rank = 1;
        $Csv = new Csv();
        $Csv->setCsvType($CsvType)
            ->setEntityName('Eccube\Entity\ProductClass')
            ->setFieldName('Member')
            ->setReferenceFieldName('name')
            ->setDispName('店舗名')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('Eccube\Entity\ProductClass')
            ->setFieldName('ClassCategory1')
            ->setReferenceFieldName('name')
            ->setDispName('仕入先')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('部門名')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('分類名')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('Eccube\Entity\ProductClass')
            ->setFieldName('code')
            ->setDispName('商品コード')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('メーカー')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('レーベル')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('品番')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('Eccube\Entity\ProductClass')
            ->setFieldName('Product')
            ->setReferenceFieldName('name')
            ->setDispName('商品名')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('発売日')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('Eccube\Entity\ProductClass')
            ->setFieldName('stock')
            ->setDispName('新品(買切分)数量')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('新品(買切分)金額')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('新品(委託分)数量')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('新品(委託分)金額')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('Eccube\Entity\ProductClass')
            ->setFieldName('stock')
            ->setDispName('中古(買切分)数量')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('中古(買切分)金額')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('中古(委託分)数量')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('中古(委託分)金額')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('ISBNｺｰﾄﾞ')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('新品販売価格(税抜)')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('中古販売価格(税抜)')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('最終新品販売日')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('最終中古販売日')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('新品仕入価格(税抜)')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('中古仕入価格(税抜)')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('最終新品入荷日')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('')
            ->setFieldName('')
            ->setReferenceFieldName('')
            ->setDispName('最終中古入荷日')
            ->setSortNo($rank);
        $em->persist($Csv);
        $em->flush();

        return $CsvType;
    }

    protected function copyResourceFiles(ContainerInterface $container)
    {
        $fs = new Filesystem();

        $fs->copy(__DIR__.'/Resource/config/purchaseflow.yaml', __DIR__.'/../../config/eccube/packages/purchaseflow.yaml', true);
    }

}
