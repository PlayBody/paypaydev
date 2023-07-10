<?php

namespace Plugin\ProductFilter4\Form\Extension;

use Eccube\Form\Type\SearchProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Eccube\Form\Type\MasterType;

class SearchProductExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('price_min', HiddenType::class, []);
        $builder->add('price_max', HiddenType::class, []);
        $builder->add('class_category_ids', HiddenType::class, []);
        $builder->add('tag_ids', HiddenType::class, []);
        $builder->add('search_mode', HiddenType::class, []);
        $builder->add('member_ids', HiddenType::class, []);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getExtendedType()
    {
        return SearchProductType::class;
    }
}
