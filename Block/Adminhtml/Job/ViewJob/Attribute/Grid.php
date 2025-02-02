<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Attribute;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data as BackendHelperData;
use Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Grid\Filter\JobAttributeIsLabel
    as FilterJobAttributeIsLabel;
use Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Grid\Renderer\JobAttributeIsLabel
    as RendererJobAttributeIsLabel;
use Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Grid\Renderer\JobAttributeLabel;
use Straker\EasyTranslationPlatform\Model\AttributeTranslationFactory;
use Straker\EasyTranslationPlatform\Model\ResourceModel\AttributeTranslation\Collection;

class Grid extends Extended
{
    protected $_attributeTranslationFactory;
    protected $_jobId;
    protected $_entityId;

    public function __construct(
        Context $context,
        BackendHelperData $backendHelper,
        AttributeTranslationFactory $attributeTranslationFactory,
        array $data = []
    ) {
        $this->_attributeTranslationFactory = $attributeTranslationFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        $this->_jobId = $this->getRequest()->getParam('job_id');
        $this->_entityId = $this->getRequest()->getParam('entity_id');
        parent::_construct();
    }

    /**
     * prepare collection
     */
    protected function _prepareCollection()
    {
        /** @var Collection $attrConnection */
        $attrConnection = $this->_attributeTranslationFactory->create()
            ->getCollection()
            ->addFieldToFilter('main_table.job_id', ['eq' => $this->_jobId ])
            ->addFieldToFilter('main_table.entity_id', ['eq' => $this->_entityId ]);

        $this->setCollection($attrConnection);
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
//        $this->addColumn(
//            'in_attribute',
//            [
//                'type' => 'checkbox',
//                'name' => 'in_attribute',
//                'align' => 'center',
//                'index' => 'attribute_id',
//                'width' => '100px'
//            ]
//        );

        $this->addColumn(
            'attribute_translation_id',
            [
                'header' => __('ID'),
                'type' => 'html',
                'name' => 'attribute_translation_id',
                'align' => 'left',
                'index' => 'attribute_translation_id',
                'column_css_class'=>'no-display',
                'header_css_class'=>'no-display',
            ]
        );

        $this->addColumn(
            'is_label',
            [
                'header' => __('Is Label'),
                'type' => 'text',
                'filter' => FilterJobAttributeIsLabel::class,
                'name' => 'label',
                'align' => 'center',
                'index' => 'is_label',
                'renderer' => RendererJobAttributeIsLabel::class
            ]
        );

        $this->addColumn(
            'label',
            [
                'header' => __('Label'),
                'type' => 'text',
                'name' => 'label',
                'align' => 'left',
                'index' => 'label',
                'width' => '200px',
                'renderer' => JobAttributeLabel::class
            ]
        );

        $this->addColumn(
            'original_value',
            [
                'header' => __('Source Text'),
                'type' => 'text',
                'name' => 'original_value',
                'align' => 'left',
                'index' => 'original_value',
                'header_css_class' => 'col-text',
                'column_css_class' => 'col-text'
            ]
        );

        $this->addColumn(
            'translated_value',
            [
                'header' => __('Target Text'),
                'type' => 'text',
                'index' => 'translated_value',
                'header_css_class' => 'col-text',
                'column_css_class' => 'col-text'
            ]
        );

        return parent::_prepareColumns();
    }

    public function getRowUrl($item)
    {
        return false;
    }
}
