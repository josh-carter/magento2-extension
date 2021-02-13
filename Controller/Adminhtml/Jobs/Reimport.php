<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Jobs;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Store\Model\StoreManagerInterface;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Helper\ImportHelper;
use Straker\EasyTranslationPlatform\Model\JobFactory;
use Straker\EasyTranslationPlatform\Logger\Logger;
use Straker\EasyTranslationPlatform\Model\JobStatus;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use Straker\EasyTranslationPlatform\Model\ResourceModel\Job\Grid\CollectionFactory;

class Reimport extends Action
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var JobFactory
     */
    private $_jobFactory;
    /**
     * @var ConfigHelper
     */
    private $_configHelper;
    /**
     * @var ImportHelper
     */
    private $_importHelper;
    /**
     * @var Logger
     */
    private $_logger;
    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;
    /**
     * @var StrakerAPIInterface
     */
    private $_strakerApi;
    /**
     * @var FileDriver
     */
    private $driver;

    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        ImportHelper $importHelper,
        JobFactory $jobFactory,
        Logger $logger,
        StoreManagerInterface $storeManager,
        StrakerAPIInterface $strakerAPI,
        CollectionFactory $collectionFactory,
        FileDriver $driver
    ) {
        $this->_jobFactory = $jobFactory;
        $this->_configHelper = $configHelper;
        $this->_importHelper = $importHelper;
        $this->_logger = $logger;
        $this->_storeManager = $storeManager;
        $this->_strakerApi = $strakerAPI;
        $this->collectionFactory = $collectionFactory;
        $this->driver = $driver;
        parent::__construct($context);
    }

    public function execute()
    {
        $jobData = $this->_jobFactory->create()->load($this->getRequest()->getParam('job_id'));
        $originalTranslatedFile = $this->_configHelper
                ->getTranslatedXMLFilePath().'/'.$jobData->getData('translated_file');
        $newTranslatedFile = $this->_configHelper
                ->getTranslatedXMLFilePath().'/old_'.time().'_'.$jobData->getData('translated_file');
        
        $this->driver->rename($originalTranslatedFile, $newTranslatedFile);
        $file_content = $this->_strakerApi->getTranslatedFile($jobData->getData('download_url'));
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            //phpcs:disable
            file_put_contents(
                $this->_configHelper
                    ->getTranslatedXMLFilePath() .'/' . $jobData->getData('translated_file'),
                $file_content
            );
            //phpcs:enable
            $this->_importHelper->create($jobData->getData('job_id'))
                ->parseTranslatedFile()
                ->saveData();
            $this->updateJobStatus($jobData);
            $resultRedirect->setPath('*/*/index');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_logger->error('error'.__FILE__.' '.__LINE__, [$e]);
            $this->_strakerApi->_callStrakerBugLog(
                __FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(),
                $e->__toString()
            );
            $this->messageManager->addErrorMessage(
                __(
                    'Translated data has not been re-imported for %1',
                    $this->_storeManager->getStore($jobData->getData('target_store_id'))->getName().' store'
                )
            );
            $resultRedirect->setPath('*/*/index');
            return $resultRedirect;
        }
        return $resultRedirect;
    }

    private function updateJobStatus($jobData): void
    {
        $jobKey = $jobData->getData('job_key');
        $jobNumber = $jobData->getData('job_number');
        $jobIds = $this->getAllJobIds($jobKey);
        foreach ($jobIds as $jobId) {
            $job = $this->_jobFactory->create()->load($jobId);
            $jobNumber = $job->getData('job_number');
            $job->setData('job_status_id', JobStatus::JOB_STATUS_COMPLETED)->save();
        }
        $this->messageManager->addSuccessMessage(
            __(
                'Translated %1 data has been re-imported for %2 store',
                $jobNumber,
                $this->_storeManager->getStore($job->getData('target_store_id'))->getName()
            )
        );
    }

    /**
     * @param string $jobKey
     * @return int[]
     */
    private function getAllJobIds(string $jobKey): array
    {
        $collection = $this->collectionFactory->create();
        $items = $collection
            ->setFlag('get_data_without_group', true)
            ->addFieldToFilter('job_key', ['eq' => $jobKey])
            ->getColumnValues('job_id');
        $collection->setFlag('get_data_without_group', false);
        return $items;
    }
}
