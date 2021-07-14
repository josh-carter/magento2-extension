<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Jobs;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Model\JobFactory;

class ViewQuote extends \Magento\Backend\App\Action
{

    protected $_coreRegistry;
    protected $_resultJsonFactory;
    protected $_resultPageFactory;
    protected $_configHelper;
    protected $_jobFactory;

    /**
     * ViewQuote constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param ConfigHelper $configHelper
     * @param PageFactory $resultPageFactory
     * @param JobFactory $jobFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        ConfigHelper $configHelper,
        PageFactory $resultPageFactory,
        JobFactory $jobFactory
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
        $this->_configHelper = $configHelper;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_jobFactory = $jobFactory;
    }

    public function execute()
    {
        $jobKey = $this->getRequest()->getParam('job_key');
        $quoteUrl = $this->_configHelper->getPaymentPageUrl().'&job_key='.$jobKey;
        $this->_coreRegistry->register('quote_url', $quoteUrl);
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('Straker_EasyTranslationPlatform::managejobs');
        $resultPage->getConfig()->getTitle()->prepend(__('Straker Translations'));

        return $resultPage;
    }

    /**
     * Is the user allowed to view the attachment grid.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Straker_EasyTranslationPlatform::jobs');
    }
}
