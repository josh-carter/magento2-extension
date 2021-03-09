<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Jobs;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\Filesystem\Driver\File as FileDriver;
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
    /**
     * @var FileDriver
     */
    private $driver;
    /**
     * @var \Magento\Framework\File\UploaderFactory
     */
    private $uploaderFactory;

    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        ImportHelper $importHelper,
        JobFactory $jobFactory,
        Logger $logger,
        StoreManagerInterface $storeManager,
        StrakerAPIInterface $strakerAPI,
        Parser $xmlParser,
        FileDriver $driver,
        \Magento\Framework\File\UploaderFactory $uploaderFactory
    ) {
        parent::__construct($context);

        $this->_jobFactory      = $jobFactory;
        $this->_configHelper    = $configHelper;
        $this->_importHelper    = $importHelper;
        $this->_logger          = $logger;
        $this->_storeManager    = $storeManager;
        $this->_strakerApi      = $strakerAPI;
        $this->_xmlParser       = $xmlParser;
        $this->driver           = $driver;
        $this->uploaderFactory  = $uploaderFactory;
    }

    public function execute()
    {
        // 'name' => string '-straker_job_18_1509478347.xml' (length=30)
        // 'type' => string 'text/xml' (length=8)
        // 'tmp_name' => string '/tmp/phpHiJUxh' (length=14)
        // 'error' => int 0
        // 'size' => int 22668
        $file               = $this->getRequest()->getFiles('translated_file');
        $params             = $this->getRequest()->getParams();
        $jobId              = array_key_exists('job_id', $params) ? $params['job_id'] : 0;
        $jobKey             = array_key_exists('job_id', $params) ? $params['job_key'] : 0;
        $sourceStoreId      = array_key_exists('job_id', $params) ? $params['source_store_id'] : 0;

        $this->getJobModel($jobId);

        $redirectParams     = [
            'job_id'            => $jobId,
            'job_key'           => $jobKey,
            'source_store_id'   => $sourceStoreId,
            'job_type_id'       => 0
        ];

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/ViewJob', $redirectParams);

        if ($this->getRequest()->isPost()) {
            $this->import($file, $jobId);
        } else {
            $this->processErrorMessage('Invalid file upload attempt', __FILE__, __METHOD__);
        }

        return $resultRedirect;
    }

    /**
     * @return array
     */
    private function backupExistingTranslatedFile(): array
    {
        $result = [
            'success' => true,
            'message' => '',
            'old_name' => '',
            'new_name' => ''
        ];

        try {
            $oldName = $this->_jobModel->getData('translated_file');

            if ($oldName) {
                $oldNameWithFullPath = $this->_configHelper->getTranslatedXMLFilePath()
                    . DIRECTORY_SEPARATOR
                    . $oldName;
                $result['old_name'] = $oldName;

                if ($this->driver->isExists($oldNameWithFullPath)) {
                    $newNameWithFullPath = $this->_configHelper->getTranslatedXMLFilePath()
                        . DIRECTORY_SEPARATOR
                        . 'backup_'
                        . time()
                        . '_'
                        . $oldName;
                    $result['success'] = $this->driver->rename($oldNameWithFullPath, $newNameWithFullPath);
                    $result['new_name'] = $newNameWithFullPath;
                }
            } else {
                $result['success'] = false;
                $result['message'] = 'Translated file cannot be found.';
            }
        } catch (FileSystemException $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * @param $file
     * @param $newFilename
     * @return array
     */
    private function saveFile($file, $newFilename): array
    {
        $validFilename = $this->validFilename($newFilename);
        $result = ['success' => true, 'message' => '', 'filename' => '', 'full_path' => ''];
        $uploader = $this->uploaderFactory->create(['fileId' => $file]);
        $translatedFileFolder = $this->_configHelper->getTranslatedXMLFilePath();
        $uploader->setAllowCreateFolders(true)
            ->setAllowedExtensions(['xml'])
            ->setAllowRenameFiles(true)
            ->setFilenamesCaseSensitivity(true)
            ->setFilesDispersion(false);

        try {
            $saveResult = $uploader->save($translatedFileFolder, $validFilename);
            $result['filename'] = $saveResult['file'];
            $result['full_path'] = $saveResult['path'] . DIRECTORY_SEPARATOR . $result['filename'];
            $result['success'] = $saveResult['error'] === 0;
        } catch (Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    private function getJobModel($jobId)
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

    /**
     * @param $file
     * @param int $jobId
     */
    protected function import($file, int $jobId): void
    {
        $this->getJobModel($jobId);
        //save new file with the same name stored in db
        $backupResult = $this->backupExistingTranslatedFile();
        if ($backupResult['success']) {
            $saveResult = $this->saveFile($file, $backupResult['old_name']);

            if ($saveResult['success']) {
                if ($saveResult['filename'] !== $backupResult['old_name']) {
                    $this->_jobModel->setData('translated_file', $saveResult['filename']);
                    $this->_jobModel->save();
                }

                try {
                    if ($this->driver->isExists($saveResult['full_path'])) {
                        $this->_importHelper->create($jobId)
                            ->parseTranslatedFile()
                            ->saveData();
                        $this->_jobModel->_setStatusForAllJobs(JobStatus::JOB_STATUS_COMPLETED);
                        $this->messageManager->addSuccessMessage(
                            'Translated ' .
                            $this->_jobModel->getData('job_number')
                            . ' data has been imported for '
                            . $this->_storeManager->getStore(
                                $this->_jobModel->getData('target_store_id')
                            )->getName()
                            . ' store'
                        );
                    } else {
                        $this->processErrorMessage('Save upload failed.', __FILE__, __METHOD__);
                    }
                } catch (LocalizedException $e) {
                    $this->processErrorMessage('File upload failed.', __FILE__, __METHOD__, $e);
                } catch (Exception $e) {
                    $this->processErrorMessage('Invalid file upload attempt', __FILE__, __METHOD__, $e);
                }
            } else {
                $this->processErrorMessage($saveResult['message'], __FILE__, __METHOD__);
            }
        } else {
            $this->processErrorMessage($backupResult['message'], __FILE__, __METHOD__);
        }
    }

    private function validFilename($newFilename)
    {
        return str_replace('&', '-', $newFilename);
    }
}
