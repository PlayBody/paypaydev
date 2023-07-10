<?php

namespace Plugin\ProductDisplayRank4\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Repository\Master\ProductListOrderByRepository;
use Plugin\ProductDisplayRank4\Form\Type\Admin\ConfigType;
use Plugin\ProductDisplayRank4\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var ProductListOrderByRepository
     */
    protected $productListOrderByRepository;

    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        ProductListOrderByRepository $productListOrderByRepository,
        ConfigRepository $configRepository
    )
    {
        $this->configRepository = $configRepository;
        $this->productListOrderByRepository = $productListOrderByRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/product_display_rank4/config", name="product_display_rank4_admin_config")
     * @Template("@ProductDisplayRank4/admin/config.twig")
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            if ($Config->getProductListOrderById()) {
                $ProductListOrderBy = $this->productListOrderByRepository->find($Config->getProductListOrderById());
                $ProductListOrderBy->setName($Config->getName());
                $this->entityManager->flush($ProductListOrderBy);
            }

            $this->addSuccess('登録しました。', 'admin');

            return $this->redirectToRoute('product_display_rank4_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
