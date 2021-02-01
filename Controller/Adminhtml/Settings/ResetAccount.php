<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Settings;

use \Exception;
use Magento\Framework\App\Action\Action;
use \Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\App\CacheInterface;
use \Magento\Framework\Controller\Result\Json;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use \Straker\EasyTranslationPlatform\Model\Setup;
use \Straker\EasyTranslationPlatform\Logger\Logger;

class ResetAccount extends Action
{

    protected $_storeManager;
    protected $_storeCache;
    protected $_resultJson;
    protected $_strakerSetup;
    protected $_logger;

    public $resultRedirectFactory;
    protected $_strakerApi;

    public function __construct(
        Context $context,
        Json $resultJson,
        StoreManagerInterface $storeManager,
        CacheInterface $storeCache,
        Setup $strakerSetup,
        Logger $logger,
        StrakerAPIInterface $strakerApi
    ) {
        $this->_storeManager = $storeManager;
        $this->_storeCache = $storeCache;
        $this->_resultJson = $resultJson;
        $this->_strakerSetup = $strakerSetup;
        $this->_logger = $logger;
        $this->_strakerApi = $strakerApi;
        return parent::__construct($context);
    }

    public function execute()
    {
        $result['Success'] = true;

        try {
            $this->_strakerSetup->saveAccessToken('');
            $this->_strakerSetup->saveAppKey('');
            $data = ['first_name' => '', 'last_name' => '', 'email' => '', 'url' => ''];
            $this->_strakerSetup->saveClientData($data);
            foreach ($this->_storeManager->getStores() as $store) {
                $this->_strakerSetup->saveStoreSetup($store->getId(), '', '', '');
            }
            $this->_strakerSetup->deleteSandboxSetting();
            //deleting straker_testing_storeview
            $this->_strakerSetup->deleteTestingStoreView();
            //clear translations for all stores
            $this->_strakerSetup->clearTranslations();
            //clear all translation jobs
            $this->_strakerSetup->clearStrakerData();
            $this->_storeCache->clean(Config::CACHE_TAG);
            $this->messageManager->addSuccessMessage(__('Straker Translations settings have been cleared.'));
            $this->_logger->info(__('Straker Translations settings have been cleared.'));
        } catch (Exception $e) {
            $message = __($e->getMessage());
            $this->messageManager->addError($message);
            $this->_logger->error($message);
            $this->_strakerApi->_callStrakerBugLog(
                __FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(),
                $e->__toString()
            );
            $result['Success'] = false;
        }

        return $this->_resultJson->setData($result);
    }
}
