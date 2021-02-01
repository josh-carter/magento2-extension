<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Jobs;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Helper\ImportHelper;
use Straker\EasyTranslationPlatform\Model\JobFactory;
use Straker\EasyTranslationPlatform\Logger\Logger;
use Straker\EasyTranslationPlatform\Model\JobStatus;

class Confirm extends Action
{

    protected $_jobFactory;
    protected $_logger;
    protected $_storeManager;
    protected $_configHelper;
    protected $_importHelper;
    protected $_strakerApi;

    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        ImportHelper $importHelper,
        JobFactory $jobFactory,
        Logger $logger,
        StoreManagerInterface $storeManager,
        StrakerAPIInterface $strakerApi
    ) {
        $this->_jobFactory = $jobFactory;
        $this->_configHelper = $configHelper;
        $this->_importHelper = $importHelper;
        $this->_logger = $logger;
        $this->_storeManager = $storeManager;
        $this->_strakerApi = $strakerApi;
        parent::__construct($context);
    }

    public function execute()
    {
        $job_id = $this->getRequest()->getParam('job_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        $job = $this->_jobFactory->create()->load($job_id);
        $jobMatches = [];
        preg_match("/job_(.*?)_/", $job->getSourceFile(), $jobMatches);
        $jobIds = explode('&', $jobMatches[1]);

        foreach ($jobIds as $job_id) {

            try {

                $currentJob = $this->_jobFactory->create()->load($job_id);
                $jobType = $currentJob->getJobType();
                $this->_importHelper->create($job_id)->publishTranslatedData();
                $currentJob->addData(['job_status_id' => JobStatus::JOB_STATUS_CONFIRMED]);
                $currentJob->save();
                $this->messageManager->addSuccess('Translated '.$jobType.' data has been published for '.$this->_storeManager->getStore($job->getData('target_store_id'))->getName().' store');

            } catch (LocalizedException $e) {

                $this->messageManager->addError($e->getMessage());
                $this->_logger->error('error'.__FILE__.' '.__LINE__, [$e]);
                $this->_strakerApi->_callStrakerBugLog($e->getMessage(), $e->__toString());
                $this->messageManager->addError('Translated data has not been published for '.$this->_storeManager->getStore($job->getData('target_store_id'))->getName().' store');
                $resultRedirect->setPath('*/*/index');
                return $resultRedirect;
            }
        }

        $resultRedirect->setPath('*/*/index');
        return $resultRedirect;
    }
}
