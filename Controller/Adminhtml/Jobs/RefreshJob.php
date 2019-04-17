<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Jobs;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Model\JobFactory;
use Straker\EasyTranslationPlatform\Model\StrakerAPI;
use Straker\EasyTranslationPlatform\Logger\Logger;
use Straker\EasyTranslationPlatform\Model;

class RefreshJob extends \Magento\Backend\App\Action
{

    protected $_coreRegistry;
    protected $_resultJsonFactory;
    protected $_configHelper;
    protected $_strakerApi;
    protected $_jobFactory;
    protected $_logger;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        ConfigHelper $configHelper,
        JsonFactory $resultJsonFactory,
        StrakerAPI $strakerAPI,
        JobFactory $jobFactory,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
        $this->_configHelper = $configHelper;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_strakerApi = $strakerAPI;
        $this->_jobFactory = $jobFactory;
        $this->_logger = $logger;
    }


    public function execute()
    {
        $jobKey = $this->getRequest()->getParam('job_key');
        $jobId = $this->getRequest()->getParam('job_id');
        $result = [ 'status' => true, 'message' => ''];
        $updatedJobs = [];


        if (empty($jobKey)) {
            //refresh all jobs
            try {
                $apiData = $this->_strakerApi->getTranslation();
                $apiJobs = $apiData->job;
                if (!empty($apiData) && count($apiJobs) > 0) {
                    foreach ($apiJobs as $apiJob) {
                        if ($apiJob->job_key) {
                            $localJobData = $this->_jobFactory->create()->getCollection()->addFieldToFilter('job_key', ['eq' => $apiJob->job_key ])->getItems();

                            if (!empty($localJobData)) {
                                $localJob = reset($localJobData);
                                $isUpdate = $this->_compareJobs($apiJob, $localJob);
                                if ($isUpdate['isSuccess']) {
                                    array_push($updatedJobs, $localJob->getId());
                                }
                            }
                        }
                    }
                    if (count($updatedJobs) > 0) {
                        $this->messageManager->addSuccessMessage(__('The status of the jobs [Id: %1] has been updated.', implode(',', $updatedJobs)));
                    } else {
                        $result['status'] = false;
                        $result['message'] = __('The Job is up to date.');
                        $this->_logger->addInfo($result['message'], ['job_id'=> $jobId]);
                        $this->messageManager->addSuccessMessage($result['message']);
                    }
                } else {
                    $result['status'] = false;
                    $result['message'] =  __('Failed to refresh job - please check log for details');
                    $this->messageManager->addWarning($result['message']);
                    $this->_logger->addError($result['message']);
                }
            } catch (\Exception $e) {
                $result['status'] = false;
                $result['message'] =  __('Failed to refresh job - please check log for details');
                $this->messageManager->addWarning($result['message']);
                $this->_logger->addError($result['message'],['file'=>__FILE__,'line'=>__LINE__]);
            }
            $redirect = $this->resultRedirectFactory->create()->setPath('EasyTranslationPlatform/jobs/Index');
            return $redirect;
        } else {
            //refresh single job
            try {
                $apiData = $this->_strakerApi->getTranslation([
                    'job_key' => $jobKey
                ]);

                if (isset($apiData->job) && count($apiData->job) > 0) {
                    $apiJob = reset($apiData->job);
                    if (!empty($apiJob)) {
                        $localJob = $this->_jobFactory->create()->load($jobId);
                        $isUpdate = $this->_compareJobs($apiJob, $localJob);
                        if ($isUpdate['isSuccess']) {
                            $result['message'] = $apiJob->status;
                        } else {
                            $result['status'] = false;
                            $result['message'] = $isUpdate['Message'];
                        }
                    }
                } else {
                    $result['status'] = false;
                    $result['message'] = __('Failed to refresh job - please check log for details');
                    $this->_logger->addError($result['message'], ['job_id'=>$jobId,'file'=>__FILE__,'line'=>__LINE__]);
                }
            } catch (\Exception $e) {
                $result['status'] = false;
                $result['message'] =  __('Failed to refresh job - please check log for details');
                $this->_logger->addError($result['message'],['file'=>__FILE__,'line'=>__LINE__]);
            }

            return $this->_resultJsonFactory->create()->setData($result);
        }
    }

    /**
     * @param $apiJob
     * @param \Straker\EasyTranslationPlatform\Model\Job $localJob
     * @return array
     */
    protected function _compareJobs($apiJob, $localJob)
    {
        $returnStatus = [];
        if ($localJob->getJobStatusId() < $this->resolveApiStatus($apiJob)) {
            $returnStatus = $localJob->updateStatus($apiJob);
            if($returnStatus['isSuccess']==false){
                $this->messageManager->addErrorMessage($returnStatus['Message']->getText());
            }
        }

        return $returnStatus;
    }

    protected function resolveApiStatus($apiJob)
    {
        $status = 0;
        if (!empty($apiJob) && !empty($apiJob->status)) {
            switch (strtolower($apiJob->status)) {
                case 'queued':
                    $status =  strcasecmp($apiJob->quotation, 'ready') == 0  ? 3 : 2;
                    break;
                case 'in_progress':
                    $status = 4;
                    break;
                case 'completed':
                    $status = 5;
                    break;
                default:
                    $status = 0;
                    break;
            }
        }

        return $status;
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
