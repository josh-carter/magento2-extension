<?php
namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Settings\Config;

class LabelValue extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        return '<div class="straker-value">' . $element->getEscapedValue() . '</div>';
    }
}
