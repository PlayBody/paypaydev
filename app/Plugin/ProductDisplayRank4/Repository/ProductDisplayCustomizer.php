<?php

namespace Plugin\ProductDisplayRank4\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Eccube\Doctrine\Query\QueryCustomizer;
use Eccube\Repository\QueryKey;
use Plugin\ProductDisplayRank4\Entity\Config;

/**
 * おすすめ順
 */
class ProductDisplayCustomizer implements QueryCustomizer
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * ProductDisplayCustomizer constructor.
     */
    public function __construct(
        ConfigRepository $configRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->configRepository = $configRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @param array $params
     * @param string $queryKey
     */
    public function customize(QueryBuilder $builder, $params, $queryKey)
    {
        $ProductListOrderBy = $params['orderby'];

        if ($ProductListOrderBy) {
            /* @var $Config Config */
            $Config = $this->configRepository->findOneBy([
                'product_list_order_by_id' => $ProductListOrderBy->getId(),
            ]);

            if ($Config) {
                // @see https://github.com/EC-CUBE/ec-cube/issues/1998
                if ($this->entityManager->getFilters()->isEnabled('option_nostock_hidden') == true
                && !in_array('pc', $builder->getAllAliases())
                ) {
                    $builder->innerJoin('p.ProductClasses', 'pc');
                    $builder->andWhere('pc.visible = true');
                }

                if ($Config->getType() == Config::ORDER_BY_DESCENDING) {
                    $builder->orderBy('p.display_rank', 'desc');
                } elseif ($Config->getType() == Config::ORDER_BY_ASCENDING) {
                    $builder->orderBy('p.display_rank', 'asc');
                }

                if (!is_null($Config->getSecondSortType())) {
                    if ($Config->getSecondSortType() == Config::SECOND_SORT_UPDATE_DESC) {
                        $builder->addOrderBy('p.update_date', 'desc');
                    } else if ($Config->getSecondSortType() == Config::SECOND_SORT_UPDATE_ASC) {
                        $builder->addOrderBy('p.update_date', 'asc');
                    }
                }

                if (!is_null($Config->getThirdSortType())) {
                    if ($Config->getThirdSortType() == Config::THIRD_SORT_ID_DESC) {
                        $builder->addOrderBy('p.id', 'desc');
                    } else if ($Config->getThirdSortType() == Config::THIRD_SORT_ID_ASC) {
                        $builder->addOrderBy('p.id', 'asc');
                    }
                }
            }
        }
    }

    /**
     * カスタマイズ対象のキーを返します。
     *
     * @return string
     */
    public function getQueryKey()
    {
        return QueryKey::PRODUCT_SEARCH;
    }
}
