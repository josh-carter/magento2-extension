<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\Edit\Grid\Renderer;

use Magento\Framework\DataObject;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;

class MultiSelect extends AbstractRenderer
{
    public function render(DataObject $row)
    {
        $index = $this->getColumn()->getIndex();
        $type = $this->getColumn()->getType();
        $value = $row->getData($index);

        $valueArray = explode(',', $value);
        $renderValue = '';

        $valueArray['render'] = [];
        if ($type === 'options') {
            $options = $this->getColumn()->getOptions();
            if (count($valueArray) > 1) {
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
