<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Setup\TestingStoreView;

use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreManagerInterface;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use Straker\EasyTranslationPlatform\Api\Data\SetupInterface;
use Straker\EasyTranslationPlatform\Logger\Logger;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Backend\App\Action\Context;

class Save extends \Magento\Backend\App\Action
{

    protected $_config;
    protected $_reinitConfig;
    protected $_strakerAPI;
    protected $_setup;
    protected $_logger;
    protected $_configHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Store\Model\Store
     */
    protected $_storeModel;

    public function __construct(
        Context $context,
        Config $config,
        ReinitableConfigInterface $reinitableConfigInterface,
        StrakerAPIInterface $strakerAPIInterface,
        SetupInterface $setupInterface,
        Logger $logger,
        StoreFactory $storeFactory,
        StoreManagerInterface $storeManager,
        ConfigHelper $configHelper,
        GroupFactory $groupFactory
    ) {

        $this->_config = $config;
        $this->_reinitConfig = $reinitableConfigInterface;
        $this->_strakerAPI = $strakerAPIInterface;
        $this->_setup = $setupInterface;
        $this->_logger = $logger;
        $this->_storeModel = $storeFactory->create();
        $this->_storeManager = $storeManager;
        $this->_configHelper = $configHelper;

        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        //if testing store exists redirect to new job page
        $testingStore = $this->_storeModel->load($this->_configHelper->getTestingStoreViewCode());
        if (!empty($testingStore->getId())) {
            if (!$this->_configHelper->isSandboxMode()) {
                $this->_setup->setSiteMode(SetupInterface::SITE_MODE_SANDBOX);
            }
        } else {
            //create testing store if store name is given
            $data = $this->getRequest()->getParams();
            if ($data) {
                if (isset($data['store_view_name']) && !empty($data['store_view_name'])) {
                    //create a store view
                    $result = $this->_setup->createTestingStoreView($data['store_view_name']);
                    if ($result['Success']) {
                        if (!$this->_configHelper->isSandboxMode()) {
                            $this->_setup->setSiteMode(SetupInterface::SITE_MODE_SANDBOX);
                        }
                    } else {
                        if ($this->_configHelper->isSandboxMode()) {
                            $this->_setup->setSiteMode(SetupInterface::SITE_MODE_LIVE);
                        }
                        $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . '', [$result['Message']]);
                        $this->_strakerAPI->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $result['Message']);
                        $this->messageManager->addErrorMessage($result['Message']);
                    }
                } else {
                    if ($this->_configHelper->isSandboxMode()) {
                        $this->_setup->setSiteMode(SetupInterface::SITE_MODE_LIVE);
                    }
                }
            }
        }
        $fromAction = empty($data['from_action']) ? 'new' : $data['from_action'];
        $resultRedirect->setPath('*/jobs/'. $fromAction . '/');
        return $resultRedirect;
    }
}
