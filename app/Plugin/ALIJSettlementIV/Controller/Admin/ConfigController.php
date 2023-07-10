<?php

namespace Plugin\ALIJSettlementIV\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\ALIJSettlementIV\Form\Type\Admin\ConfigType;
use Plugin\ALIJSettlementIV\Repository\ConfigRepository;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/ALIJSettlementIV/config", name="alij_settlement_iv_admin_config")
     * @Template("@ALIJSettlementIV/admin/config.twig")
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

            $this->addSuccess('ALIJ.admin.save.success', 'admin');

            return $this->redirectToRoute('alij_settlement_iv_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}