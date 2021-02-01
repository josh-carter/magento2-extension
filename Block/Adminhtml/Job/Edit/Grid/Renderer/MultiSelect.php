<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\Edit\Grid\Renderer;

use Magento\Backend\Block\Context;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\AttributeFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Eav\Model\Entity\StoreFactory;
use Magento\Framework\DataObject;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Straker\EasyTranslationPlatform\Api\JobRepositoryInterface;
use Straker\EasyTranslationPlatform\Model\JobFactory;
use Straker\EasyTranslationPlatform\Model\ResourceModel\AttributeTranslation\CollectionFactory as AttributeTranslationCollection;

class MultiSelect extends AbstractRenderer
{
    function render(DataObject $row)
    {
        $index = $this->getColumn()->getIndex();
        $type = $this->getColumn()->getType();
        $value = $row->getData($index);

        $valueArray = explode(',', $value);
        $renderValue = '';

        $valueArray['render'] = [];
        if ($type === 'options') {
            $options = $this->getColumn()->getOptions();
            if (sizeof($valueArray) > 1) {
                foreach ($valueArray as $v) {
                    if (!empty($v)) {
                        $val = $options[$v];
                        $valueArray['render'][] = $val;
                    }
                }
                $renderValue = implode(',', $valueArray['render']);
            } else {
                if (isset($options[$value])) {
                    $renderValue = $options[$value];
                } else {
                    $renderValue = $value;
                }
            }
        }

        $row->setData($index, $renderValue);

        return parent::render($row);
    }
}
