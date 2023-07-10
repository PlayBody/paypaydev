<?php

namespace Plugin\MarketPlace4\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\MarketPlace4\Form\Type\Admin\MarketPlace4ConfigType;
use Plugin\MarketPlace4\Repository\MarketPlace4ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MarketPlace4ConfigController extends AbstractController
{
    /**
     * @var MarketPlace4ConfigRepository
     */
    protected $configRepository;

    /**
     * ConfigController constructor.
     *
     * @param MarketPlace4ConfigRepository $configRepository
     */
    public function __construct(MarketPlace4ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/market_place4/config", name="market_place4_admin_config")
     * @Template("@MarketPlace4/admin/config.twig")
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        $form = $this->createForm(MarketPlace4ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);
            $this->addSuccess('登録しました。', 'admin');

            return $this->redirectToRoute('market_place4_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
