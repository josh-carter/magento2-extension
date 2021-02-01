<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Jobs;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Xml\Parser;

use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Helper\ImportHelper;
use Straker\EasyTranslationPlatform\Model\JobFactory;
use Straker\EasyTranslationPlatform\Logger\Logger;
use Straker\EasyTranslationPlatform\Model\JobStatus;

use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;

class Import extends Action
{

    protected $_jobFactory;
    protected $_logger;
    protected $_storeManager;
    protected $_configHelper;
    protected $_importHelper;
    protected $_strakerApi;
    protected $_xmlParser;

    /** @var  \Straker\EasyTranslationPlatform\Model\Job */
    protected $_jobModel;

    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        ImportHelper $importHelper,
        JobFactory $jobFactory,
        Logger $logger,
        StoreManagerInterface $storeManager,
        StrakerAPIInterface $strakerAPI,
        Parser $xmlParser
    ) {
        $this->_jobFactory      = $jobFactory;
        $this->_configHelper    = $configHelper;
        $this->_importHelper    = $importHelper;
        $this->_logger          = $logger;
        $this->_storeManager    = $storeManager;
        $this->_strakerApi      = $strakerAPI;
        $this->_xmlParser       = $xmlParser;
        parent::__construct($context);
    }

    public function execute()
    {
        //      'name' => string '-straker_job_18_1509478347.xml' (length=30)
        //      'type' => string 'text/xml' (length=8)
        //      'tmp_name' => string '/tmp/phpHiJUxh' (length=14)
        //      'error' => int 0
        //      'size' => int 22668
        $file               = $this->getRequest()->getFiles('translated_file');
        $params             = $this->getRequest()->getParams();
        $jobId              = array_key_exists('job_id', $params) ? $params['job_id'] : 0;
        $jobKey             = array_key_exists('job_id', $params) ? $params['job_key'] : 0;
        $sourceStoreId      = array_key_exists('job_id', $params) ? $params['source_store_id'] : 0;

        $this->createJobModel($jobId);

        $redirectParams     = [
            'job_id'            => $jobId,
            'job_key'           => $jobKey,
            'source_store_id'   => $sourceStoreId,
            'job_type_id'       => 0
        ];

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/ViewJob', $redirectParams);

        if ($this->getRequest()->isPost()) {

            //check uploaded file
            $checkResult = $this->checkUploadedFile($file);
            if ($checkResult['success']) {
                try {
                    //todo: save new file with the same name stored in db
                    $renameResult = $this->renameExistingTranslatedFile($jobId);

                    if ($renameResult['success']) {
                        if ($renameResult['old_name']) {
                            $translatedFullFilename = $renameResult['old_name'];
                            $translatedFilename = $this->_getFilenameFromFullName($translatedFullFilename);

                            if ($translatedFullFilename === '') {
                                $fileNameArray = $this->generateTranslatedFilename($jobId);
                                $translatedFilename = $fileNameArray['name'];
                                $translatedFullFilename = implode(DIRECTORY_SEPARATOR, $fileNameArray);
                            }

                            if (move_uploaded_file($file['tmp_name'], $translatedFullFilename)) {
                                //todo: import
                                if (file_exists($translatedFullFilename)) {
                                    $this->_importHelper->create($jobId)
                                        ->parseTranslatedFile()
                                        ->saveData();
                                    $this->_jobModel->_setStatusForAllJobs(JobStatus::JOB_STATUS_COMPLETED);
                                    $this->messageManager->addSuccessMessage(
                                        'Translated '.
                                        $this->_jobModel->getData('job_number')
                                        . ' data has been imported for '
                                        . $this->_storeManager->getStore(
                                            $this->_jobModel->getData('target_store_id')
                                        )->getName()
                                        .' store'
                                    );
                                } else {
                                    $this->processErrorMessage('Save upload failed.', __FILE__, __METHOD__);
                                }
                            } else {
                                $this->processErrorMessage('File upload failed.', __FILE__, __METHOD__);
                            }
                        }
                    } else {
                        $this->processErrorMessage('Cannot generate translated file.', __FILE__, __METHOD__);
                    }
                } catch (LocalizedException $e) {
                    $this->processErrorMessage('File upload failed.', __FILE__, __METHOD__, $e);
                } catch (Exception $e) {
                    $this->processErrorMessage('Invalid file upload attempt', __FILE__, __METHOD__, $e);
                }
            } else {
                $this->processErrorMessage($checkResult['message'], __FILE__, __METHOD__);
            }
        } else {
            $this->processErrorMessage('Invalid file upload attempt', __FILE__, __METHOD__);
        }

        return $resultRedirect;
    }

    /**
     * @param $jobId
     * @return array
     */
    private function renameExistingTranslatedFile($jobId)
    {
        $this->createJobModel($jobId);
        $oldName = $this->_jobModel->getData('translated_file');
        $success = true;
        $oldNameWithFullPath = '';
        $newNameWithFullPath = '';

        if ($oldName) {
            $oldNameWithFullPath = $this->_configHelper->getTranslatedXMLFilePath() . DIRECTORY_SEPARATOR . $oldName;
            if (file_exists($oldNameWithFullPath)) {
                $newNameWithFullPath  = $this->_configHelper->getTranslatedXMLFilePath() . DIRECTORY_SEPARATOR . 'old_' . time() . '_' . $oldName;
                $success = rename($oldNameWithFullPath, $newNameWithFullPath);
            }
        }

        return ['success' => $success, 'old_name' => $oldNameWithFullPath, 'new_name' => $newNameWithFullPath];
    }

    /**
     * @param $file
     * @return array
     */
    private function checkUploadedFile($file)
    {
        $result = ['success'   => true, 'message'   => ''];

        if (!is_uploaded_file($file['tmp_name'])) {
            $result['success'] = false;
            $result['message'] = 'File must upload via http post.';
        } elseif ($file['error'] !== 0) {
            $result['success'] = false;
            $result['message'] = 'An error found while uploading (' . $file['error'] . ').';
        } elseif (empty($file['tmp_name'])) {
            $result['success'] = false;
            $result['message'] = 'An error found while uploading.';
        } elseif ($file['type'] !== 'text/xml') {
            $result['success'] = false;
            $result['message'] = 'File type invalid.';
        } else {
            $result['success'] = true;
        }

        $result['message'] = __($result['message']);

        return $result;
    }

    /**
     * @param $jobId
     * @return array
     */
    private function generateTranslatedFilename($jobId)
    {
        $nameArray = [];

        $this->createJobModel($jobId);

        if (key_exists('source_file', $this->_jobModel->getData())) {
            //full path of filename
            $sourceFileName = $this->_jobModel->getData('source_file');
            //remove path and '.xml' at the end of filename
            $sourceFileName = $this->_getFilenameFromFullName($sourceFileName, false);
            //returns ['path' => $filePath, 'name' => $fileName]
            $nameArray = $this->_jobModel->generateTranslatedFilename($sourceFileName);
        }

        return $nameArray;
    }

    private function _getFilenameFromFullName($fullName, $suffix = true)
    {
        return $suffix
            ?
            substr($fullName, strrpos($fullName, DIRECTORY_SEPARATOR) + 1)
            :
            substr($fullName, strrpos($fullName, DIRECTORY_SEPARATOR) + 1, -4);
    }

    private function createJobModel($jobId)
    {
        if ($this->_jobModel === null) {
            $this->_jobModel = $this->_jobFactory->create()->load($jobId);
        }
    }

    private function processErrorMessage($message, $file, $method, $e = null)
    {
        if ($e === null) {
            $this->_logger->error($message, ['file' => $file, 'method' => $method]);
            $this->_strakerApi->_callStrakerBugLog($file . ' ' . $method . ' ' . $message);
        } else {
            $this->_logger->error($message, ['file' => $file, 'method' => $method, 'e' => $e->__toString()]);
            $this->_strakerApi->_callStrakerBugLog($file . ' ' . $method . ' ' . $e->getMessage(), $e->__toString());
        }
        $this->messageManager->addErrorMessage(__($message));
    }
}
