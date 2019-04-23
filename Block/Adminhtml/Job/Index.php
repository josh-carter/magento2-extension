<?php
namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job;

use Magento\Backend\Block\Widget\Context;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Magento\Framework\App\Config;

class Index extends \Magento\Backend\Block\Widget\Container
{
    protected $_configHelper;

    function __construct(
        Context $context,
        ConfigHelper $configHelper,
        array $data
    )
    {
        parent::__construct($context, $data);
        $this->_configHelper = $configHelper;
    }

    /**
     * Prepare button and grid
     *
     * @return \Magento\Catalog\Block\Adminhtml\Product
     */
    protected function _prepareLayout()
    {
        $addNewJobButton = [
            'id' => 'add_new_job',
            'label' => __('Add New Translation Job'),
            'class' => 'primary',
            'onclick' => "setLocation('" . $this->_getAddNewJobUrl() . "')"
        ];
        $myAccountButton = [
            'label' => __('My Account'),
            'class' => 'straker-my-account-button',
            'title' => __('Click here to see your extended profile, active jobs and billing information.'),
            'target' => '_blank',
            'disabled' => $this->shouldDisable()
        ];
        $this->buttonList->add('add_new', $addNewJobButton, 0, 50);
        $this->buttonList->add('my_account', $myAccountButton, 10, 10);

        return parent::_prepareLayout();
    }


    public function shouldDisable(){
        $this->_cache->clean(Config::CACHE_TAG);
        $result = empty($this->_configHelper->getAccessToken());
        return $result;
    }

    private function _getAddNewJobUrl()
    {
        return $this->getUrl('*/*/new');
    }

    public function _getMyAccountUrl(){
        return $this->_configHelper->getMyAccountUrl();
    }
}