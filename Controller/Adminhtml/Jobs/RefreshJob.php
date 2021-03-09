<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Jobs;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Model\Job;
use Straker\EasyTranslationPlatform\Model\JobFactory;
use Straker\EasyTranslationPlatform\Model\StrakerAPI;
use Straker\EasyTranslationPlatform\Logger\Logger;

class RefreshJob extends Action
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

        return empty($jobKey)
            ? $this->refreshAllJobs($jobId)
            : $this->refreshSingleJob($jobKey, $jobId);
    }

    /**
     * @param $apiJob
     * @param Job|DataObject $localJob
     * @return array
     */
    protected function _compareJobs($apiJob, $localJob): array
    {
        $returnStatus = [];
        if ($localJob->getJobStatusId() < $this->_strakerApi->resolveApiStatus($apiJob)) {
            $returnStatus = $localJob->updateStatus($apiJob);
            if ($returnStatus['isSuccess']==false) {
                $this->messageManager->addErrorMessage($returnStatus['Message']->getText());
            }
        }

        return $returnStatus;
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

    /**
     * @param $jobId
     * @return Redirect
     */
    private function refreshAllJobs($jobId): Redirect
    {
        $result = [ 'status' => true, 'message' => ''];

        try {
            $apiData = $this->_strakerApi->getTranslation();
            $apiJobs = $apiData->job;
            if (!empty($apiData) && count($apiJobs) > 0) {
                $updatedJobs = $this->getUpdateJobs($apiJobs);
                if (count($updatedJobs) > 0) {
                    $this->messageManager->addSuccessMessage(
                        __('The status of the jobs [Id: %1] has been updated.', implode(',', $updatedJobs))
                    );
                } else {
                    $result['status'] = false;
                    $result['message'] = __('The Job is up to date.');
                    $this->_logger->addInfo($result['message'], ['job_id' => $jobId]);
                    $this->messageManager->addSuccessMessage($result['message']);
                }
            } else {
                $result['status'] = false;
                $result['message'] = __('Failed to refresh job - please check log for details');
                $this->messageManager->addWarning($result['message']);
                $this->_logger->addError($result['message']);
            }
        } catch (Exception $e) {
            $result['status'] = false;
            $result['message'] = __('Failed to refresh job - please check log for details');
            $this->messageManager->addWarning($result['message']);
            $this->_logger->addError($result['message'], ['file' => __FILE__, 'line' => __LINE__]);
        }
        $redirect = $this->resultRedirectFactory->create()->setPath('EasyTranslationPlatform/jobs/Index');
        return $redirect;
    }

    /**
     * @param $jobKey
     * @param $jobId
     * @return Json
     */
    private function refreshSingleJob($jobKey, $jobId): Json
    {
        $result = [ 'status' => true, 'message' => ''];

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
                $this->_logger->addError(
                    $result['message'],
                    [
                        'job_id' => $jobId,
                        'file' => __FILE__,
                        'line' => __LINE__
                    ]
                );
            }
        } catch (Exception $e) {
            $result['status'] = false;
            $result['message'] = __('Failed to refresh job - please check log for details');
            $this->_logger->addError($result['message'], ['file' => __FILE__, 'line' => __LINE__]);
        }

        return $this->_resultJsonFactory->create()->setData($result);
    }

    /**
     * @param $apiJobs
     * @return array
     */
    private function getUpdateJobs($apiJobs): array
    {
        $updatedJobs = [];

        foreach ($apiJobs as $apiJob) {
            if ($apiJob->job_key) {
                $localJobData = $this->_jobFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('job_key', ['eq' => $apiJob->job_key])
                    ->getItems();

                if (!empty($localJobData)) {
                    $localJob = reset($localJobData);
                    $isUpdate = $this->_compareJobs($apiJob, $localJob);
                    if ($isUpdate['isSuccess']) {
                        array_push($updatedJobs, $localJob->getId());
                    }
                }
            }
        }
        return $updatedJobs;
    }
}
