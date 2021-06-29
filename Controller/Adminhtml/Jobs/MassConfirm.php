<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Jobs;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Store\Model\StoreManagerInterface;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Helper\ImportHelper;
use Straker\EasyTranslationPlatform\Model\Job;
use Straker\EasyTranslationPlatform\Model\ResourceModel\Job as JobResource;
use Straker\EasyTranslationPlatform\Model\JobFactory;
use Straker\EasyTranslationPlatform\Logger\Logger;
use Straker\EasyTranslationPlatform\Model\JobStatus;
use Straker\EasyTranslationPlatform\Model\ResourceModel\Job\Grid\Collection;
use Straker\EasyTranslationPlatform\Model\ResourceModel\Job\Grid\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassConfirm extends Action implements HttpPostActionInterface
{
    /**
     * @var Filter
     */
    private $filter;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var JobFactory
     */
    private $jobFactory;
    /**
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var ImportHelper
     */
    private $importHelper;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var StrakerAPIInterface
     */
    private $strakerApi;
    /**
     * @var JobResource
     */
    private $jobResource;

    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        ImportHelper $importHelper,
        JobFactory $jobFactory,
        JobResource $jobResource,
        Logger $logger,
        StoreManagerInterface $storeManager,
        StrakerAPIInterface $strakerApi,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        $this->jobFactory = $jobFactory;
        $this->jobResource = $jobResource;
        $this->configHelper = $configHelper;
        $this->importHelper = $importHelper;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->strakerApi = $strakerApi;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        /** @var Collection $collection */
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $jobKeys = $collection->getColumnValues('job_key');
        $jobIds = $this->getAllJobs($jobKeys);
        $publishedJobs = [];

        foreach ($jobIds as $jobId) {
            try {
                $job = $this->jobFactory->create();
                $this->jobResource->load($job, $jobId);
                $jobNumber = $job->getData('job_number');
                $jobType = $job->getJobType();
                $this->importHelper->create($job->getId())->publishTranslatedData();
                $job->setData('job_status_id', JobStatus::JOB_STATUS_CONFIRMED);
                $this->jobResource->save($job);

                if (!isset($publishedJobs[$jobNumber])) {
                    $publishedJobs[$jobNumber] = [];
                }

                $publishedJobs[$jobNumber][] = $jobType;

            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->logger->error('error'.__FILE__.' '.__LINE__, [$e]);
                $this->strakerApi->_callStrakerBugLog($e->getMessage(), $e->__toString());
                $this->messageManager->addErrorMessage(
                    __(
                        'Translated data has not been published for %1 store',
                        $this->storeManager->getStore($job->getData('target_store_id'))->getName()
                    )
                );
            }
        }

        foreach ($publishedJobs as $jobNumber => $publishedJob) {
            $this->messageManager->addSuccessMessage(
                __(
                    'Translated job %1 (%2) has been published for %3 store',
                    $jobNumber,
                    implode(', ', $publishedJob),
                    $this->storeManager->getStore($job->getData('target_store_id'))->getName()
                )
            );
        }

        if (!count($jobIds)) {
            $this->messageManager->addWarningMessage(__('The selected jobs are not ready to publish.'));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }

    /**
     * @param array $jobKeys
     * @return Job[]
     */
    private function getAllJobs(array $jobKeys): array
    {
        $collection = $this->collectionFactory->create();
        $items = $collection
            ->setFlag('get_data_without_group', true)
            ->addFieldToFilter('job_key', ['in' => $jobKeys])
            ->addFieldToFilter('job_status_id', ['eq' => JobStatus::JOB_STATUS_COMPLETED])
            ->getColumnValues('job_id');
        $collection->setFlag('get_data_without_group', false);
        return $items;
    }
}
