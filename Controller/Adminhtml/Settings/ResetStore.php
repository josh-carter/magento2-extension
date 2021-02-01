<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Settings;

use Magento\Framework\App\Action\Action;
use \Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config;
use \Magento\Framework\App\CacheInterface;
use \Magento\Framework\Controller\Result\Json;
use Magento\Store\Model\StoreManagerInterface;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use \Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use \Straker\EasyTranslationPlatform\Model\Setup;
use \Straker\EasyTranslationPlatform\Logger\Logger;

class ResetStore extends Action
{
    protected $_storeCache;
    protected $_resultJson;
    protected $_configHelper;
    protected $_strakerSetup;
    protected $_logger;
    protected $_storeManager;

    public $resultRedirectFactory;
    protected $_strakerApi;

    public function __construct(
        Context $context,
        Json $resultJson,
        CacheInterface $storeCache,
        ConfigHelper $configHelper,
        Setup $strakerSetup,
        Logger $logger,
        StoreManagerInterface $storeManager,
        StrakerAPIInterface $strakerApi
    ) {
        $this->_storeCache = $storeCache;
        $this->_resultJson = $resultJson;
        $this->_configHelper = $configHelper;
        $this->_strakerSetup = $strakerSetup;
        $this->_logger = $logger;
        $this->_storeManager = $storeManager;
        $this->_strakerApi = $strakerApi;
        return parent::__construct($context);
    }


    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store');

        if (isset($storeId) && is_numeric($storeId)) {
            if ($this->_configHelper->getStoreSetup($storeId)) {
                //remove all applied translations from database
                //$this->_strakerSetup->clearTranslations( $storeId );
                $this->_strakerSetup->saveStoreSetup($storeId, '', '', '');
                $message = __('Language settings has been reset.');
                $this->messageManager->addSuccessMessage($message);
                $this->_logger->info($message);
                $this->_storeCache->clean(Config::CACHE_TAG);
            } else {
                $message = __('There is a error in store configuration.');
                $this->messageManager->addError($message);
                $this->_logger->error($message);
                $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $message);
            }
        } elseif (!isset($storeId)) {
            $stores = $this->_storeManager->getStores();
            foreach ($stores as $store) {
                $this->_strakerSetup->saveStoreSetup($store->getId());
            }
            $message = __('Language settings has been reset.');
            $this->messageManager->addSuccessMessage($message);
            $this->_logger->info($message);
            $this->_storeCache->clean(Config::CACHE_TAG);
        } else {
            $message = __('Store code is not valid.');
            $this->messageManager->addErrorMessage($message);
            $this->_logger->error($message);
            $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $message);
        }

        return;
    }
}
