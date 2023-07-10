<?php

namespace Plugin\ProductFilter4\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\ProductFilter4\Form\Type\Admin\ProductFilter4ConfigType;
use Plugin\ProductFilter4\Repository\ProductFilter4ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProductFilter4ConfigController extends AbstractController
{
    /**
     * @var ProductFilter4ConfigRepository
     */
    protected $configRepository;

    /**
     * ConfigController constructor.
     *
     * @param ProductFilter4ConfigRepository $configRepository
     */
    public function __construct(ProductFilter4ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/product_filter4/config", name="product_filter4_admin_config")
     * @Template("@ProductFilter4/admin/config.twig")
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        $form = $this->createForm(ProductFilter4ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);
            $this->addSuccess('登録しました。', 'admin');

            return $this->redirectToRoute('product_filter4_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
