<?php
/**
 * Created by PhpStorm.
 * User: Paul
 * Date: 5/07/16
 * Time: 15:31
 */

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Settings\Config;

use Magento\Backend\Block\Template\Context;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Magento\Framework\App\Config;

class ModuleVersion extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_configHelper;

    function __construct(
        Context $context,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_configHelper = $configHelper;
    }

    function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->_cache->clean(Config::CACHE_TAG);
        $myAccountUrl = $this->_configHelper->getMyAccountUrl();
        $disabled = empty($this->_configHelper->getAccessToken()) ? 'disabled' : '';
        return '<div class="straker-value">'. $this->_configHelper->getModuleVersion().'</div>';
    }
}
