<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Grid\Renderer;

use Magento\Framework\DataObject;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;

class JobAttributeIsLabel extends AbstractRenderer
{
    public function render(DataObject $row)
    {
        $row->setData('is_label', $row->getData('is_label') ? __('Yes') : __('No'));
        return parent::render($row);
    }
}
