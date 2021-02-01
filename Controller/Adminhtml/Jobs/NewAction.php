<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Jobs;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Straker\EasyTranslationPlatform\Api\Data\SetupInterface;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;

class NewAction extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Backend\Model\View\Result\Forward
     */
    protected $resultForwardFactory;
    protected $_configHelper;
    protected $_setupApi;
//    public $resultRedirectFactory;
    /**
     * @param Context $context
     * @param ForwardFactory $resultForwardFactory
     * @param ConfigHelper $configHelper
     * @param SetupInterface $setupApi
     */
    public function __construct(
        Context $context,
        ForwardFactory $resultForwardFactory,
        ConfigHelper $configHelper,
        SetupInterface $setupApi
    ) {

        $this->resultForwardFactory = $resultForwardFactory;
        $this->_configHelper = $configHelper;
        $this->_setupApi = $setupApi;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return true;
        // return $this->_authorization->isAllowed('Straker_Job::attachment_save');
    }

    /**
     * Forward to edit
     *
     * @return \Magento\Backend\Model\View\Result\Forward
     */
    public function execute()
    {
        if ($this->_configHelper->isSandboxMode()) {
            $this->messageManager->addNotice($this->_configHelper->getSandboxMessage());
        }

        if ($this->_configHelper->isSandboxMode() && !$this->_setupApi->isTestingStoreViewExist()->getId()) {
            $this->messageManager->addError($this->_configHelper->getCreateTestStoreViewMessage());
        }
        /** @var \Magento\Backend\Model\View\Result\Forward $resultForward */
        $resultForward = $this->resultForwardFactory->create();
        return $resultForward->forward('edit');
    }
}
