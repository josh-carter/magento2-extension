<?php
namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Settings\Config;

/*
 * This class preform Magento database backup. Since backup functionality has been
 * change to call portal server to backup, this class is not used but remains here
 * in case requiring the functionality switch back.
 */

class BackupProductData extends \Magento\Config\Block\System\Config\Form\Field
{
    const BUTTON_TEMPLATE = 'settings/config/button/backup_product_data_button.phtml';

    private $_buttonId;
    private $_buttonName;

    /**
     * Set template to itself
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::BUTTON_TEMPLATE);
        }
        return $this;
    }

    /**
     * Render button
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxResetUrl()
    {
        return $this->getUrl('EasyTranslationPlatform/Settings/BackupProductData');
    }

    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->_buttonId = $element->getId();
        $this->_buttonName = $element->getName();

        return $this->_toHtml();
    }

    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->addData([
            'id' => $this->_buttonId,
                'name' => $this->_buttonName,
                'label' => __('Backup Product Data'),
                'type' => 'button',
                'onclick' => 'return backup.backup(\'db\')'
        ]);

        return $button->toHtml();
    }
}
