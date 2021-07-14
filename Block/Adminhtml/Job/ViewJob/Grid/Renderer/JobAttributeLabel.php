<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Grid\Renderer;

use Magento\Backend\Block\Context;
use Magento\Framework\DataObject;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Straker\EasyTranslationPlatform\Helper\PageHelper;
use Straker\EasyTranslationPlatform\Model\ResourceModel\AttributeTranslation\CollectionFactory
    as AttributeTranslationCollection;
use Magento\Eav\Model\Entity\AttributeFactory;

class JobAttributeLabel extends AbstractRenderer
{
    protected $_attributeTranslationCollectionFactory;
    protected $_attributeFactory;
    protected $_pageHelper;

    public function __construct(
        Context $context,
        AttributeTranslationCollection $attributeTranslationCollection,
        AttributeFactory $attributeFactory,
        PageHelper $pageHelper
    ) {

        $this->_attributeTranslationCollectionFactory = $attributeTranslationCollection;
        $this->_attributeFactory = $attributeFactory;
        $this->_pageHelper = $pageHelper;
        parent::__construct($context);
    }

    public function render(DataObject $row)
    {
        $hasOption = $row->getData('has_option');

        if ($hasOption) {
            $attrLabel = '<a data-attr-id=\''
                . $row->getData('attribute_translation_id')
                . '\' class=\'straker-view-option-anchor\'>'
                . $row->getData('label')
                . '</a>';
        } else {
            $attrLabel = $row->getData('label');
        }

        $row->setData('label', $attrLabel);
        return parent::render($row);
    }
}
