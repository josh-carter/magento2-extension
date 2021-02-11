<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Jobs;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\RawFactory;
use \Magento\Framework\Filesystem\Driver\File as FileDriver;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Model\JobFactory;

class Export extends Action
{
    /**
     * @var RawFactory
     */
    protected $resultRawFactory;
    protected $jobFactory;
    protected $configHelper;
    protected $fileFactory;
    /**
     * @var FileDriver
     */
    private $driver;

    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        FileFactory $fileFactory,
        JobFactory $jobFactory,
        ConfigHelper $configHelper,
        FileDriver $driver
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->jobFactory = $jobFactory;
        $this->configHelper = $configHelper;
        $this->resultRawFactory = $resultRawFactory;
        $this->driver = $driver;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $jobId          = array_key_exists('job_id', $params) ? $params['job_id'] : 0;
        $jobKey         = array_key_exists('job_id', $params) ? $params['job_key'] : 0;
        $sourceStoreId  = array_key_exists('job_id', $params) ? $params['source_store_id'] : 0;

        if (!empty($jobId)) {
            $jobModel = $this->jobFactory->create()->load($jobId);
            $filePath = $jobModel->getData('source_file');

            $filename = substr($filePath, strrpos($filePath, DIRECTORY_SEPARATOR) + 1);

            if ($this->driver->isExists($filePath)) {
                $sourceFile = $this->driver->fileGetContents($filePath);
                //phpcs:disable
                $contentLength = filesize($filePath);
                //phpcs:enable
                $this->fileFactory->create(
                    $filename,
                    null,
                    DirectoryList::VAR_DIR,
                    'application/octet-stream',
                    $contentLength
                );

                /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
                $resultRaw = $this->resultRawFactory->create();
                $resultRaw->setContents($sourceFile);
                return $resultRaw;
            } else {
                $this->messageManager->addErrorMessage(__('File not found.'));
                $data = [
                    'job_id'            => $jobId,
                    'job_key'           => $jobKey,
                    'source_store_id'   => $sourceStoreId,
                    'job_type_id'       => 0
                ];
                $this->_redirect('*/*/ViewJob', $data);
            }
        } else {
            $this->messageManager->addErrorMessage(__('Job id is required.'));
            $this->_redirect('*/*');
        }
    }
}
