<?php

namespace Plugin\LuckyBag4\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\LuckyBag4\Form\Type\Admin\LuckyBag4ConfigType;
use Plugin\LuckyBag4\Repository\LuckyBag4ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LuckyBag4ConfigController extends AbstractController
{
    /**
     * @var LuckyBag4ConfigRepository
     */
    protected $configRepository;

    /**
     * ConfigController constructor.
     *
     * @param LuckyBag4ConfigRepository $configRepository
     */
    public function __construct(LuckyBag4ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/lucky_bag4/config", name="lucky_bag4_admin_config")
     * @Template("@LuckyBag4/admin/lucky_bag4_config.twig")
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        $form = $this->createForm(LuckyBag4ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);
            $this->addSuccess('登録しました。', 'admin');

            return $this->redirectToRoute('lucky_bag4_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
