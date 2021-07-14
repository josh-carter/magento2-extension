<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Settings;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Straker\EasyTranslationPlatform\Api\Data\SetupInterface;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Logger\Logger;

class CreateTestStoreView extends Action
{
    protected $_resultJsonFactory;
    protected $_strakerSetup;
    protected $_logger;
    protected $_configHelper;
    protected $_strakerApi;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SetupInterface $strakerSetup,
        Logger $logger,
        ConfigHelper $configHelper,
        StrakerAPIInterface $strakerApi
    ) {
        $this->_strakerSetup = $strakerSetup;
        $this->_logger = $logger;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_configHelper = $configHelper;
        $this->_strakerApi = $strakerApi;
        return parent::__construct($context);
    }

    public function execute()
    {
        $result = ['Success' => true];
        $params = $this->getRequest()->getParams();
        $storeName = $params['storeName'];
        $siteMode = $params['siteMode'];
        try {
            $result = $this->_strakerSetup->createTestingStoreView($storeName, $siteMode);
            if ($result['Success']) {
                $this->messageManager->addSuccessMessage(__('Test store view created successfully.'));
                if ($result['SiteMode'] == SetupInterface::SITE_MODE_LIVE) {
                    $this->messageManager->addSuccessMessage(__('Live mode enabled.'));
                } else {
                    $this->messageManager->addSuccessMessage(__('Sandbox mode enabled'));
                }
            } else {
                $this->messageManager->addWarningMessage($result['Message']);
            }
        } catch (Exception $e) {
            $result['Success'] = false;
            $message = __($e->getMessage());
            $result['Message'] = $message;
            $this->_logger->error($message);
            $this->_strakerApi->_callStrakerBugLog(
                __FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(),
                $e->__toString()
            );
        }
        $jsonResult = $this->_resultJsonFactory->create();
        return $jsonResult->setData($result);
    }
}
