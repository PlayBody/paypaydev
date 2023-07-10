<?php

namespace Plugin\ProductDisplayRank4\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Repository\ProductRepository;
use Plugin\ProductDisplayRank4\Form\Type\Admin\ConfigType;
use Plugin\ProductDisplayRank4\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * ProductController constructor.
     *
     * @param ProductRepository $productRepository
     */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/product/upadate_rank", name="product_display_rank4_admin_product_update_rank")
     */
    public function update(Request $request)
    {
        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
            return $this->json(['status' => 'NG'], 400);
        }


        $id = $request->get('id');
        $rank = $request->get('rank');

        if (!preg_match('/^-?\d+$/', $rank) || !preg_match('/^-?\d+$/', $id)){
            return $this->json(['status' => 'NG'], 400);
        }

        $Product = $this->productRepository->find($id);
        $Product->setDisplayRank($rank);
        $this->entityManager->flush($Product);

        return new JsonResponse([
            'status' => 'OK',
        ]);
    }

}
