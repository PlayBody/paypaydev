<?php
/**
 * Created by SYSTEM_KD
 * Date: 2019-03-20
 */

namespace Plugin\MarketPlace4\Service;


use Eccube\Event\TemplateEvent;

/**
 * Class TwigRenderService
 *
 * @version 1.0.0
 * @package Plugin\MarketPlace4\Service
 */
class TwigRenderService
{

    /** @var string */
    private $pluginName;

    /** @var string */
    private $supportBlockId;

    /** @var TemplateEvent */
    private $templateEvent;

    /** @var ContentBlockBuilderInterface[] */
    private $contentBlocks;

    public function __construct()
    {
        $this->contentBlocks = [];
    }

    /**
     * 初期化
     *
     * @param TemplateEvent $event
     * @return TwigRenderService
     */
    public function initRenderService(TemplateEvent $event)
    {
        $this->templateEvent = $event;

        // ContentBlock用のKey生成
        $nameSpaces = explode("\\", __NAMESPACE__);
        $this->pluginName = $nameSpaces[1];
        $this->supportBlockId = $this->pluginName . "_root";

        return $this;
    }

    /**
     * テンプレート挿入用Builder返却
     *
     * @return InsertContentBlockBuilder
     */
    public function insertBuilder()
    {
        $builder = new InsertContentBlockBuilder();

        $this->contentBlocks[] = $builder;

        return $builder;
    }

    /**
     * テンプレート拡張用Snippet追加
     *
     * @param null $templateName
     * @param bool $debug
     */
    public function addSupportSnippet($templateName = null, $debug = false)
    {
        $this->templateEvent->setParameter('support_block_id', $this->supportBlockId);

        if (!is_null($templateName)) {
            $this->templateEvent->setParameter('template_name', $templateName);
        }

        $contentBlocks = [];
        /** @var ContentBlockBuilderInterface $contentBlock */
        foreach ($this->contentBlocks as $contentBlock) {
            $contentBlocks[] = $contentBlock->build();
        }

        $this->templateEvent->setParameter('ContentBlocks', $contentBlocks);

        // Template 反映
        $this->templateEvent->addSnippet($this->getRenderSupportTwig($debug), false);
    }

    /**
     * @param bool $debug true:デバッグモード
     * @return string
     */
    private function getRenderSupportTwig($debug)
    {

        $removeCode = '$("#{{ support_block_id }}").remove();';


        if($debug) {
            $removeCode = "";
        }

        $renderSupportTwig = <<< HTML
<div id="{{ support_block_id }}" class="d-none">
    {% if template_name is defined and template_name != "" %}
        {{ include(template_name) }}
    {% endif %}

    {% for ContentBlock in ContentBlocks %}
        {% if ContentBlock.Template != "" %}
            {% if ContentBlock.include %}
                {{ include(ContentBlock.Template) }}
            {% else %}
                {{ ContentBlock.Template|raw }}
            {% endif %}
        {% endif %}
    {% endfor %}
</div>

<script>
    window.addEventListener('DOMContentLoaded', function () {

        // Script
        {% for ContentBlock in ContentBlocks %}
        {{ ContentBlock.RenderScript|raw }}
        {% endfor %}

        {$removeCode}
    })
</script>
HTML;

        return $renderSupportTwig;
    }
}

/**
 * Class ContentBlockBuilderBase
 */
abstract class ContentBlockBuilderBase implements ContentBlockBuilderInterface
{

    /** @var ContentBlockBase */
    protected $contentBlock;

    /**
     * @param string $find
     * @return $this
     */
    public function find($find)
    {
        $this->contentBlock->addFind($find);
        return $this;
    }

    /**
     * @param integer $index
     * @return $this
     */
    public function eq($index)
    {
        $this->contentBlock->addEq($index);
        return $this;
    }

}

/**
 * テンプレート挿入用Builder
 *
 * Class InsertContentBlockBuilder
 */
class InsertContentBlockBuilder extends ContentBlockBuilderBase
{

    public function __construct()
    {
        $this->contentBlock = new InsertContentBlock();
    }

    /**
     * 挿入テンプレート設定
     *
     * @param $templatePath
     * @param bool $include
     * @return $this
     */
    public function setTemplate($templatePath, $include = true)
    {
        $this->contentBlock->setInsertTemplate($templatePath, $include);
        return $this;
    }

    /**
     * @param string $targetId
     * @return $this
     */
    public function setTargetId($targetId)
    {
        $this->contentBlock->setTargetId($targetId);
        return $this;
    }

    /**
     * @return $this jQuery after()
     */
    public function setInsertModeAfter()
    {
        $this->contentBlock->setInsertMode(InsertContentBlock::INSERT_AFTER);
        return $this;
    }

    /**
     * @return $this jQuery append()
     */
    public function setInsertModeAppend()
    {
        $this->contentBlock->setInsertMode(InsertContentBlock::INSERT_APPEND);
        return $this;
    }

    /**
     * @return $this jQuery wrap()
     */
    public function setInsertModeWrap()
    {
        $this->contentBlock->setInsertMode(InsertContentBlock::INSERT_WRAP);
        return $this;
    }

    /**
     * @return $this jQuery replaceWith()
     */
    public function setInsertModeReplaceWith()
    {
        $this->contentBlock->setInsertMode(InsertContentBlock::INSERT_REPLACE);
        return $this;
    }

    public function build()
    {
        return $this->contentBlock;
    }
}

/**
 * Class ContentBlockBase
 */
abstract class ContentBlockBase
{
    private $contentSearches;

    public function __construct()
    {
        $this->contentSearches = [];
    }

    public function addFind($find)
    {
        $this->contentSearches[]['find'] = $find;
    }

    public function addEq($index)
    {
        $this->contentSearches[]['index'] = $index;
    }

    protected function getSearchRender()
    {
        $render = "";

        foreach ($this->contentSearches as $key => $contentSearch) {

            if ($key == 0) {
                $render .= sprintf("$('%s')", $contentSearch['find']);
                continue;
            }

            if(isset($contentSearch['find'])) {
                // find
                $render .= sprintf(".find('%s')", $contentSearch['find']);
            } else {
                // eq
                $render .= sprintf(".eq(%d)", $contentSearch['index']);
            }

        }

        return $render;
    }

}

/**
 * Class InsertContentBlock
 */
class InsertContentBlock extends ContentBlockBase implements ContentBlockInterface
{

    /** @var string */
    private $insertTemplate;

    /** @var bool  */
    private $insertInclude = false;

    /** @var string */
    private $targetId;

    /** @var integer */
    private $insertMode = 1;

    /** @var int after() */
    const INSERT_AFTER = 1;

    /** @var int append() */
    const INSERT_APPEND = 2;

    /** @var int wrap() */
    const INSERT_WRAP = 3;

    /** @var int replaceWith() */
    const INSERT_REPLACE = 4;

    private $INSERT_VALUE = [
        self::INSERT_AFTER => 'after',
        self::INSERT_APPEND => 'append',
        self::INSERT_WRAP => 'wrap',
        self::INSERT_REPLACE => 'replaceWith',
    ];

    /**
     * @return mixed
     */
    public function getInsertTemplate()
    {
        return $this->insertTemplate;
    }

    /**
     * @param mixed $insertTemplatePath
     * @param bool $include
     * @return InsertContentBlock
     */
    public function setInsertTemplate($insertTemplatePath, $include)
    {
        $this->insertTemplate = $insertTemplatePath;
        $this->insertInclude = $include;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTargetId()
    {
        return $this->targetId;
    }

    /**
     * @param mixed $targetId
     * @return InsertContentBlock
     */
    public function setTargetId($targetId)
    {
        $this->targetId = $targetId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getInsertMode()
    {
        return $this->insertMode;
    }

    /**
     * @param mixed $insertMode
     * @return InsertContentBlock
     */
    public function setInsertMode($insertMode)
    {
        $this->insertMode = $insertMode;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTemplate()
    {
        return $this->getInsertTemplate();
    }

    /**
     * @return bool
     */
    public function isInclude()
    {
        return $this->insertInclude;
    }

    public function renderScript()
    {

        $render = $this->getSearchRender();

        $render .= sprintf(".%s($('#%s'));",
            $this->INSERT_VALUE[$this->getInsertMode()], $this->getTargetId());

        return $render;
    }

}

/**
 * Interface ContentBlockBuilderInterface
 */
interface ContentBlockBuilderInterface
{
    public function build();
}

/**
 * Interface ContentBlockInterface
 */
interface ContentBlockInterface
{
    public function renderScript();

    public function getTemplate();

    public function isInclude();
}
