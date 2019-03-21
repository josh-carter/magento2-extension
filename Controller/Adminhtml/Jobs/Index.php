<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Jobs;

use Braintree\Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Model\JobFactory;
use Straker\EasyTranslationPlatform\Model\StrakerAPI;
use Straker\EasyTranslationPlatform\Logger\Logger;
use Magento\Framework\Registry;

class Index extends Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    protected $_configHelper;
    protected $_strakerApi;
    protected $_jobFactory;
    protected $_logger;
    protected $_coreRegistry;

    const NO_TJ_MSG = 'TJ Number is not currently available. Please refresh page to update job information.';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Logger $logger
     * @param StrakerAPI $strakerAPI
     * @param JobFactory $jobFactory
     * @param ConfigHelper $configHelper
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Logger $logger,
        StrakerAPI $strakerAPI,
        JobFactory $jobFactory,
        ConfigHelper $configHelper,
        Registry $registry
    ) {

        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_strakerApi = $strakerAPI;
        $this->_logger = $logger;
        $this->_jobFactory = $jobFactory;
        $this->_configHelper = $configHelper;
        $this->_coreRegistry = $registry;
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        if ($this->_configHelper->isSandboxMode()) {
            $this->messageManager->addNotice($this->_configHelper->getSandboxMessage());
        }
        $this->refreshJobs();
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Straker_EasyTranslationPlatform::managejobs');
        $resultPage->getConfig()->getTitle()->prepend(__('Straker Translations'));
        return $resultPage;
    }

    protected function refreshJobs()
    {
        $updatedJobs = [];
        $localJobIds = [];
        $result = ['status' => true, 'message' => ''];
        $emptyTj = 0;
        //refresh all jobs
        try {
            $apiData = $this->_strakerApi->getTranslation();
            if (isset($apiData->job)) {
                $apiJobs = $apiData->job;
                if (!empty($apiData) && count($apiJobs) > 0) {
                    foreach ($apiJobs as $apiJob) {
                        if ($apiJob->job_key) {
                            $localJobData = $this->_jobFactory->create()->getCollection()->addFieldToFilter('job_key', ['eq' => $apiJob->job_key ])->getItems();
                            if (!empty($localJobData)) {
                                foreach ($localJobData as $key => $localJob) {
                                    array_push($localJobIds, $localJob->getId());
                                    $isUpdate = $this->_compareJobs($apiJob, $localJob);
                                    if (isset($isUpdate['isSuccess'])&&$isUpdate['isSuccess']==true) {
                                        $tjNumber = $localJob->getJobNumber();
                                        if (!empty($tjNumber) && !in_array($tjNumber, $updatedJobs)) {
                                            array_push($updatedJobs, $tjNumber);
                                        }
                                    }

                                    if(isset($isUpdate['isSuccess'])&&$isUpdate['isSuccess']==false && isset($isUpdate['emptyTJ']) && $isUpdate['emptyTJ']==true){
                                        $emptyTj++;
                                    }

                                    if(isset($isUpdate['isSuccess'])&&$isUpdate['isSuccess']==false && isset($isUpdate['empty_file']) && $isUpdate['empty_file']==true){

                                        $this->messageManager->addErrorMessage($isUpdate['Message']->getText());
                                    }
                                }
                            }
                        }
                    }

                    if($emptyTj>0){

                        $this->messageManager->addNoticeMessage(self::NO_TJ_MSG);
                    }

                    if (count($updatedJobs) > 0) {
                        $this->_coreRegistry->register('job_updated', true );
                        $this->messageManager->addSuccessMessage(__('%1 has been updated.', implode(', ', $updatedJobs)));
                    } elseif (count($localJobIds) <= 0) {
                        $result['status'] = false;
                        $result['message'] = __('You have not created any job.');
                        $this->_logger->addInfo($result['message']);
                        $this->messageManager->addNoticeMessage($result['message']);
                    } else {
                        $result['status'] = false;
                        if(!$this->messageManager->getMessages()->getErrors()){
                            $result['message'] = __('All jobs are up to date.');
                            $this->messageManager->addSuccessMessage( $result['message'] );
                            $this->_logger->addInfo($result['message']);
                        }
                    }
                } else {
                    $result['status'] = false;
                    $result['message'] =  __('No jobs have been found or failed to connect server.');
                    $this->messageManager->addErrorMessage($result['message']);
                    $this->_logger->addError($result['message']);
                }
            } else {
                $dataArray = (array)$apiData;
                $result['status'] = false;
                if(key_exists('message', $dataArray)){
                    $result['message'] =  __( 'Server: ' . $dataArray['message'] );
                }
//                $this->messageManager->addErrorMessage( $result['message'] );
                $this->_logger->addError($result['message'], $dataArray);
            }
        } catch (\Exception $e) {
            $result['status'] = false;
            $result['message'] = $e->getMessage();
            $this->messageManager->addErrorMessage($result['message']);
            $this->_logger->addError($result['message'], [ 'exception' => $e->getMessage() ]);
        }
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
     * @param $apiJob
     * @param $localJob
     * @return array
     */
    protected function _compareJobs($apiJob, $localJob)
    {
        $returnStatus = [];

        if ($localJob->getJobStatusId() < $this->resolveApiStatus($apiJob)) {
            $returnStatus = $localJob->updateStatus($apiJob);
        }

        return $returnStatus;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}
