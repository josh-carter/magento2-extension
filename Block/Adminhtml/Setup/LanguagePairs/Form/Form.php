<?php
namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Setup\LanguagePairs\Form;

use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use Magento\Framework\View\Element\Template;
use Magento\Backend\Block\Template\Context;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Magento\Framework\Locale\ListsInterface;

class Form extends Template
{
    protected $_storeManager;
    protected $_strakerAPIInterface;
    protected $configHelper;
    protected $localeList;
    protected $_storeInfoData;

    public function __construct(
        Context $context,
        StrakerAPIInterface $strakerAPIInterface,
        ConfigHelper $configHelper,
        ListsInterface $localeList,
        array $data = []
    ) {
        $this->_strakerAPIInterface = $strakerAPIInterface;
        $this->configHelper = $configHelper;
        $this->localeList = $localeList;
        parent::__construct($context);
    }

    public function getWebsites()
    {
        return $this->_storeManager->getWebsites();
    }

    public function _getOptions()
    {
        return $this->_strakerAPIInterface->getLanguages();
    }

    public function _formData()
    {
        if ($this->_session->getData('form_data')) {
            return $this->_session->getData('form_data');
        }

        return false;
    }

    public function getStoreInfo($storeId)
    {
        $storeData = $this->configHelper->getStoreInfo($storeId);
        $this->_storeInfoData = $storeData;
        return $storeData;
    }

    public function getTranslationLanguage()
    {
        return (!empty($this->_storeInfoData)) ? $this->_storeInfoData['straker/general/destination_language'] : false ;
    }

    public function getSourceLanguage()
    {
        return (!empty($this->_storeInfoData)) ? $this->_storeInfoData['straker/general/destination_language'] : false ;
    }

    public function getSourceStore()
    {
        return (! empty($this->_storeInfoData)) ? $this->_storeInfoData['straker/general/source_store'] : false ;
    }

    public function getMessage($store_id)
    {
        if ($this->getRequest()->getParam('target_store_id')
            && $this->getRequest()->getParam('target_store_id') == $store_id
        ) {
            return true;
        };
        return false;
    }
}
