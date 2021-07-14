<?php
namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Settings\Config;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;

class ResetStore extends \Magento\Config\Block\System\Config\Form\Field
{
    const BUTTON_TEMPLATE = 'settings/config/button/reset_store_button.phtml';
    protected $_configHelper;
    protected $_strakerApi;

    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        StrakerAPIInterface $strakerAPI,
        array $data = []
    ) {
        $this->_configHelper = $configHelper;
        $this->_strakerApi = $strakerAPI;
        parent::__construct($context, $data);
    }

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
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxResetUrl()
    {
        return $this->getUrl('EasyTranslationPlatform/Settings/ResetStore');
    }

    /**
     * Get the button and scripts contents
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * @return \Magento\Store\Api\Data\WebsiteInterface[]
     */
    public function getWebsites()
    {
        return $this->_storeManager->getWebsites();
    }

    public function _getOptions()
    {
        return $this->_strakerApi->getLanguages();
    }

    public function getStoreLanguageSetting($storeId)
    {
        $storeInfo = $this->_configHelper->getStoreInfo($storeId);
        $source_store = $storeInfo['straker/general/source_store'] ?? false;
        $source_language = $storeInfo['straker/general/source_language'] ?? false;
        $destination_language = $storeInfo['straker/general/destination_language'] ??false;
        $sourceStore = $source_store ? $this->_storeManager->getStore($source_store) : $source_store;
        $storeInfoArray = [
            'source' => $sourceStore,
            'source_language' => $source_language,
            'target' => $storeId,
            'target_language' => $destination_language
        ];

        $flag = true;

        foreach ($storeInfoArray as $item) {
            if (!$item) {
                $flag = false;
                break;
            }
        }
        return $flag ? $storeInfoArray : $flag;
    }

    /**
     * @param $store
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRemoveTranslationButtonHtml($store)
    {
        if ($store->getId() && $this->_configHelper->getStoreSetup($store->getId())) {
            $button = $this
                ->getLayout()
                ->createBlock(\Magento\Backend\Block\Widget\Button::class)
                ->setData([
                    'id' => 'straker_reset_store_button_' . $store->getCode(),
                    'label' => __('Clear'),
                    'class' => 'straker-reset-store-button',
                    'title' => __('Clear the language setting only.')
                ]);
            return $button->toHtml();
        }
    }

    /**
     * @return string
     * @internal param $store
     */
    public function getRemoveAllTranslationButtonHtml()
    {
        $button = $this->getLayout()
            ->createBlock(\Magento\Backend\Block\Widget\Button::class)
            ->setData([
                'id' => 'straker_reset_all_store_button',
                'label' => __('Clear All'),
                'title' => __('Clear language settings for all store views.')
            ]);
        return $button->toHtml();
    }
}
