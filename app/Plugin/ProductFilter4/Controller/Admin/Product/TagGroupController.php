<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace Plugin\ProductFilter4\Controller\Admin\Product;


use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Eccube\Controller\AbstractController;
use Eccube\Repository\TagRepository;
use Plugin\ProductFilter4\Entity\TagGroup;
use Plugin\ProductFilter4\Entity\TagGroupDetail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Plugin\ProductFilter4\Repository\TagGroupRepository;
use Plugin\ProductFilter4\Repository\TagGroupDetailRepository;
use Plugin\ProductFilter4\Form\Type\Admin\TagGroupType;



class TagGroupController extends AbstractController
{

    /**
     * @var TagGroupRepository
     */
    protected $tagGroupRepository;

    /**
     * @var TagGroupDetailRepository
     */
    protected $tagGroupDetailRepository;

    /**
     * @var TagRepository
     */
    protected $tagRepository;

    /**
     * ProductExController constructor.
     *
     * @param TagGroupRepository $tagGroupRepository
     * @param TagGroupDetailRepository $tagGroupDetailRepository
     * @param TagRepository $tagRepository
     */
    public function __construct(
        TagGroupRepository $tagGroupRepository,
        TagGroupDetailRepository $tagGroupDetailRepository,
        TagRepository $tagRepository
    ) {
        $this->tagGroupRepository = $tagGroupRepository;
        $this->tagGroupDetailRepository = $tagGroupDetailRepository;
        $this->tagRepository = $tagRepository;
    }

    /**
     *
     * @Route("/%eccube_admin_route%/product/tag_group_index", name="plg_product_filter4_admin_tag_group_index")
     * @Template("@ProductFilter4/admin/Product/tag_group_index.twig")
     *
     */
    public function index(Request $request)
    {
        $TagGroups = $this->tagGroupRepository
            ->findBy([], ['sort_no' => 'DESC']);
        $descriptions = [];
        foreach ($TagGroups as $TagGroup){
            $TagGroupDetails = $this->tagGroupDetailRepository->findBy(['TagGroup' => $TagGroup]);
            $description = '';
            $ii=0;
            foreach ($TagGroupDetails as $item){
                $ii++;
                if ($ii<8){
                    if ($description != '') $description = $description . "、";
                    $description = $description . $item->getTag()->getName();
                }elseif($ii==8){
                    $description = $description . ' ...';
                }else{
                    continue;
                }
            }

            if ($description != '') $description = " [ " . $description . " ]";

            $descriptions[$TagGroup->getId()] = $description;
        }

        return [
            'TagGroups' => $TagGroups,
            'descriptions' => $descriptions
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/product/tag_group/new", name="plg_product_filter4_admin_tag_group_new")
     * @Route("/%eccube_admin_route%/setting/tag_group/{id}/edit", requirements={"id" = "\d+"}, name="plg_product_filter4_admin_tag_group_edit")
     * @Template("@ProductFilter4/admin/Product/tag_group_edit.twig")
     */
    public function edit(Request $request, $id = null)
    {
        if (is_null($id)) {
            $TagGroup = $this->tagGroupRepository->findOneBy([], ['sort_no' => 'DESC']);

            $sortNo = 1;
            if ($TagGroup) {
                $sortNo = $TagGroup->getSortNo() + 1;
            }

            $TagGroup = new TagGroup();
            $TagGroup
                ->setSortNo($sortNo)
                ->setVisible(true);
        } else {
            $TagGroup = $this->tagGroupRepository->find($id);
        }

        $builder = $this->formFactory
            ->createBuilder(TagGroupType::class, $TagGroup);

        $form = $builder->getForm();

        $TagGroupDetails = $this->tagGroupDetailRepository->findBy(['TagGroup' => $TagGroup]);
        $GroupTags = [];
        foreach ($TagGroupDetails as $TagGroupDetail){
            $GroupTags[] = $TagGroupDetail->getTag();
        }
        $form['Tags']->setData($GroupTags);

        // 登録ボタン押下
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                foreach ($TagGroupDetails as $TagGroupDetail) {
                    $this->entityManager->remove($TagGroupDetail);
                }

                $this->entityManager->persist($TagGroup);
                $this->entityManager->flush();

                // いれる
                $Tags = $form->get('Tags')->getData();
                foreach ($Tags as $Tag) {
                    $TagGroupDetail = new TagGroupDetail();
                    $TagGroupDetail->setTagGroup($TagGroup);
                    $TagGroupDetail->setTag($Tag);
                    $this->entityManager->persist($TagGroupDetail);
                }
                $this->entityManager->persist($TagGroup);

                $this->entityManager->flush();

                $this->addSuccess('admin.common.save_complete', 'admin');

                return $this->redirectToRoute('plg_product_filter4_admin_tag_group_edit', ['id' => $TagGroup->getId()]);
            }
        }

        return [
            'form' => $form->createView(),
            'tag_group_id' => $TagGroup->getId(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/product/tag_group/{id}/delete", requirements={"id" = "\d+"}, name="plg_product_filter4_admin_tag_group_delete", methods={"DELETE"})
     */
    public function delete(Request $request, TagGroup $TagGroup)
    {
        $this->isTokenValid();

        try {

            $TagGroupDetails = $this->tagGroupDetailRepository->findBy(['TagGroup' => $TagGroup]);
            foreach ($TagGroupDetails as $TagGroupDetail) {
                $this->entityManager->remove($TagGroupDetail);
            }

            $this->entityManager->remove($TagGroup);
            $this->entityManager->flush();
        } catch (ForeignKeyConstraintViolationException $e) {
            $this->addError(trans('admin.common.delete_error_foreign_key', ['%name%' => $TagGroup->getName()]), 'admin');

            return $this->redirectToRoute('plg_product_filter4_admin_tag_group_index');
        }

        $sortNo = 1;
        $TagGroups = $this->tagGroupRepository
            ->findBy([], ['sort_no' => 'ASC']);

        foreach ($TagGroups as $GroupItem) {
            if ($GroupItem->getId() != $TagGroup->getId()) {
                $GroupItem->setSortNo($sortNo);
                $sortNo++;
            }
        }

        $this->entityManager->flush();

        $this->addSuccess('admin.common.delete_complete', 'admin');

        return $this->redirectToRoute('plg_product_filter4_admin_tag_group_index');
    }

    /**
     * @Route("/%eccube_admin_route%/product/tag_group/{id}/visibility", requirements={"id" = "\d+"}, name="plg_product_filter4_admin_tag_group_visibilty", methods={"PUT"})
     */
    public function visibility(Request $request, TagGroup $TagGroup)
    {
        $this->isTokenValid();

        // 表示・非表示を切り替える
        if ($TagGroup->isVisible()) {
            $message = trans('admin.common.to_hide_complete', ['%name%' => $TagGroup->getName()]);
            $TagGroup->setVisible(false);
        } else {
            $message = trans('admin.common.to_show_complete', ['%name%' => $TagGroup->getName()]);
            $TagGroup->setVisible(true);
        }
        $this->entityManager->persist($TagGroup);

        $this->entityManager->flush();

        $this->addSuccess($message, 'admin');

        return $this->redirectToRoute('plg_product_filter4_admin_tag_group_index');
    }

    /**
     * @Route("/%eccube_admin_route%/product/tag_group/sort_no/move", name="plg_product_filter4_admin_tag_group_sort_no_move", methods={"POST"})
     */
    public function moveSortNo(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        if ($this->isTokenValid()) {
            $sortNos = $request->request->all();
            foreach ($sortNos as $tagGroupId => $sortNo) {
                $Delivery = $this->tagGroupRepository->find($tagGroupId);
                $Delivery->setSortNo($sortNo);
                $this->entityManager->persist($Delivery);
            }
            $this->entityManager->flush();
        }

        return $this->json('OK', 200);
    }
}
