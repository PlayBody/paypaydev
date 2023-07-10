<?php

namespace Plugin\ProductDisplayRank4;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Csv;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Master\ProductListOrderBy;
use Eccube\Entity\Product;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\ProductDisplayRank4\Entity\Config;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PluginManager.
 */
class PluginManager extends AbstractPluginManager
{
    const VERSION = '1.0.2';
    const PLUGIN_CODE = 'ProductDisplayRank4';

    /**
     * Install the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function install(array $meta, ContainerInterface $container)
    {
    }

    /**
     * Update the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function update(array $meta, ContainerInterface $container)
    {
        $this->createCsvData($container, true);
    }

    /**
     * Enable the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        $this->createCsvData($container, false);
        $this->createMasterData($container);
    }

    /**
     * Disable the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        $this->deleteCsvData($container);
        $this->deleteMasterData($container);
    }

    /**
     * Uninstall the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        $this->deleteCsvData($container);
        $this->deleteMasterData($container);
    }

    /**
     * @param ContainerInterface $container
     */
    private function createMasterData(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();

        $Configs = $entityManager->getRepository('Plugin\ProductDisplayRank4\Entity\Config')->findAll();

        $maxRank = 1;

        if (!count($Configs)) {
            $qb = $entityManager->createQueryBuilder();
            $maxId = $qb->select('MAX(m.id)')
                ->from(ProductListOrderBy::class, 'm')
                ->getQuery()
                ->getSingleScalarResult();
            $data = [
                [
                    'className' => 'Eccube\Entity\Master\ProductListOrderBy',
                    'id' => ++$maxId,
                    'sort_no' => ++$maxRank,
                    'name' => 'おすすめ順',
                ],
            ];
        } else {
            /* @var $Configs Config[] */
            foreach ($Configs as $Config) {
                $data[] =
                    [
                        'className' => 'Eccube\Entity\Master\ProductListOrderBy',
                        'id' => $Config->getProductListOrderById(),
                        'sort_no' => ++$maxRank,
                        'name' => $Config->getName(),
                    ]
                ;
            }
        }

        foreach ($data as $row) {
            $Entity = $entityManager->getRepository($row['className'])
                ->find($row['id']);
            if (!$Entity) {
                // 先頭に持ってくる処理を入れる
                $OtherEntities = $entityManager->getRepository($row['className'])->findBy([], ['sort_no' => 'asc']);

                $Entity = new $row['className']();
                $Entity
                    ->setName($row['name'])
                    ->setId($row['id'])
                    ->setSortNo($row['sort_no'])
                ;

                $entityManager->persist($Entity);
                $entityManager->flush($Entity);

                $i = 0;
                foreach ($OtherEntities as $OtherEntity) {
                    $OtherEntity->setSortNo($row['sort_no'] + (++$i));
                    $entityManager->flush($OtherEntity);
                }

                $Config = new Config();
                $Config
                    ->setName($row['name'])
                    ->setProductListOrderById($Entity->getId())
                    ->setType(1)
                    ->setCsvImportDefaultRank(0)
                ;

                $entityManager->persist($Config);
                $entityManager->flush($Config);
            }
        }
    }

    /**
     * @param ContainerInterface $container
     */
    private function deleteMasterData(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();

        $Configs = $entityManager->getRepository('Plugin\ProductDisplayRank4\Entity\Config')->findAll();

        /* @var $Configs Config[] */
        foreach ($Configs as $Config) {
            $ProductListOrderBy = $entityManager->getRepository('Eccube\Entity\Master\ProductListOrderBy')
                ->find($Config->getProductListOrderById());

            if ($ProductListOrderBy) {
                $entityManager->remove($ProductListOrderBy);
            }
        }
    }


    /**
     * @param ContainerInterface $container
     */
    private function createCsvData(ContainerInterface $container, $checkEnabled)
    {

        $entityManager = $container->get('doctrine')->getManager();
        $entityName = 'Eccube\\\\Entity\\\\Product';
        $fieldName  = 'display_rank';
        $dispName   = '表示順';

        $Plugin = $entityManager->getRepository('Eccube\Entity\Plugin')->findByCode(self::PLUGIN_CODE);

        if ($checkEnabled && !$Plugin->isEnabled()) {
            return;
        }

        $Csv = $entityManager->getRepository('Eccube\Entity\Csv')->findOneBy([
            'CsvType' => CsvType::CSV_TYPE_PRODUCT,
            'entity_name' => $entityName,
            'field_name' => $fieldName,
            'reference_field_name' => null,
        ]);

        $sortNoMax = $entityManager->getRepository('Eccube\Entity\Csv')->findOneBy(['CsvType' => CsvType::CSV_TYPE_PRODUCT,], ['sort_no' => 'DESC']);
        $sortNo = 0;
        if (!is_null($sortNoMax)) {
            $sortNo = $sortNoMax->getSortNo();
        }

        if (!$Csv) {
            $CsvType = $entityManager->getRepository('Eccube\Entity\Master\CsvType')
                ->find(CsvType::CSV_TYPE_PRODUCT);
            $Csv = new Csv();
            $Csv
                ->setCsvType($CsvType)
                ->setCreator(null)
                ->setEntityName($entityName)
                ->setFieldName($fieldName)
                ->setReferenceFieldName(null)
                ->setDispName($dispName)
                ->setEnabled(true)
                ->setSortNo($sortNo + 1);

            $entityManager->persist($Csv);
            $entityManager->flush();
        }
    }

    /**
     * @param ContainerInterface $container
     */
    private function deleteCsvData(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $entityName = 'Eccube\\\\Entity\\\\Product';
        $fieldName  = 'display_rank';

        $Csv = $entityManager->getRepository('Eccube\Entity\Csv')->findOneBy([
            'CsvType' => CsvType::CSV_TYPE_PRODUCT,
            'entity_name' => $entityName,
            'field_name' => $fieldName,
            'reference_field_name' => null,
        ]);


        if ($Csv) {
            $entityManager->remove($Csv);
            $entityManager->flush($Csv);
        }
    }

}
