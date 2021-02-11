<?php
namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Settings\Config;

use Magento\Backend\Block\Template\Context;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Magento\Framework\App\Config;

class MyAccount extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_configHelper;

    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_configHelper = $configHelper;
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->_cache->clean(Config::CACHE_TAG);
        $myAccountUrl = $this->_configHelper->getMyAccountUrl();
        $disabled = empty($this->_configHelper->getAccessToken()) ? 'disabled' : '';
        return '<a class="straker-my-account-anchor action-default" 
                   href="'. $myAccountUrl .'" 
                   target="_blank" 
                   '. $disabled .'    
                >'
                        . __('My Account') .
               '</a>';
    }
}
