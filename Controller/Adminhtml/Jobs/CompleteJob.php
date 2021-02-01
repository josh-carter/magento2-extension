<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Jobs;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Registry;
use Straker\EasyTranslationPlatform\Model\StrakerAPI;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Model\JobFactory;

class CompleteJob extends Action
{

    protected $_coreRegistry;
    protected $_resultJsonFactory;
    protected $_configHelper;
    protected $_jobFactory;
    protected $_strakerAPI;

    /**
     * ViewQuote constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param ConfigHelper $configHelper
     * @param JsonFactory $resultJsonFactory
     * @param JobFactory $jobFactory
     * @param StrakerAPI $strakerAPI
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        ConfigHelper $configHelper,
        JsonFactory $resultJsonFactory,
        JobFactory $jobFactory,
        StrakerAPI $strakerAPI
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
        $this->_configHelper = $configHelper;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_jobFactory = $jobFactory;
        $this->_strakerAPI = $strakerAPI;
    }

    public function execute()
    {
        $url = 'https://uat-app.strakertranslations.com/v1/ta2wo/test/complete';
        $tjNumber = $this->getRequest()->getParam('job_id');
        $result = $this->_strakerAPI->completeJob($tjNumber, $url);
        return $this->_resultJsonFactory->create()->setData($result);
    }

    protected function _isAllowed()
    {
        return true;
    }
}
