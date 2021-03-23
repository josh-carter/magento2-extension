<?php

namespace Straker\EasyTranslationPlatform\Model;

use Exception;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\FileSystemException;
use \Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Straker\EasyTranslationPlatform\Helper\ImportHelper;
use Straker\EasyTranslationPlatform\Logger\Logger;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as MagentoProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection\Factory as CategoryCollectionFactory;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as MagentoPageCollectionFactory;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory as MagentoBlockCollectionFactory;
use Straker\EasyTranslationPlatform\Model\ResourceModel\AttributeTranslation\Collection;
use Straker\EasyTranslationPlatform\Model\ResourceModel\AttributeTranslation\CollectionFactory
    as AttributeTranslationCollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Api\BlockRepositoryInterface;

class Job extends AbstractModel implements JobInterface, IdentityInterface
{
    const ENTITY = 'straker_job';

    /**
     * CMS page cache tag
     */
    const CACHE_TAG = 'st_products_grid';

    const PAGE_ATTRIBUTES = ['title','meta_keywords','meta_description','content_heading','content','meta_title'];

    const BLOCK_ATTRIBUTES = ['title','content'];

    /**
     * @var string
     */
    protected $_cacheTag = 'st_products_grid';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'st_products_grid';

    protected $_productCollectionFactory;
    protected $_categoryCollectionFactory;
    protected $_pageCollectionFactory;
    protected $_blockCollectionFactory;
    protected $_attributeTranslationCollectionFactory;
    protected $_productRepository;
    protected $_categoryRepository;
    protected $_pageRepository;
    protected $_blockRepository;

    protected $_entities = [];
    public $_entityIds = [];
    protected $_entityCount;
    protected $_jobStatusFactory;
    protected $_jobTypeFactory;
    protected $_importHelper;
    protected $_strakerApi;
    protected $_logger;
    /**
     * @var FileDriver
     */
    private $driver;

    public function __construct(
        Context $context,
        Registry $registry,
        MagentoProductCollectionFactory $productCollectionFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        MagentoPageCollectionFactory $pageCollectionFactory,
        MagentoBlockCollectionFactory $blockCollectionFactory,
        AttributeTranslationCollectionFactory $attributeTranslationCollectionFactory,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        PageRepositoryInterface $pageRepository,
        BlockRepositoryInterface $blockRepository,
        JobStatusFactory $jobStatusFactory,
        JobTypeFactory $jobTypeFactory,
        ImportHelper $importHelper,
        StrakerAPI $strakerAPI,
        Logger $logger,
        FileDriver $driver
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_pageCollectionFactory = $pageCollectionFactory;
        $this->_blockCollectionFactory = $blockCollectionFactory;
        $this->_attributeTranslationCollectionFactory = $attributeTranslationCollectionFactory;
        $this->_jobStatusFactory = $jobStatusFactory;
        $this->_jobTypeFactory = $jobTypeFactory;
        $this->_importHelper = $importHelper;
        $this->_strakerApi = $strakerAPI;
        $this->_logger = $logger;
        $this->_productRepository = $productRepository;
        $this->_categoryRepository = $categoryRepository;
        $this->_pageRepository = $pageRepository;
        $this->_blockRepository = $blockRepository;
        $this->driver = $driver;
        parent::__construct($context, $registry);
    }

    /**
     * @param $sourceFilename
     * @return array
     * @throws FileSystemException
     * @internal param $jobData
     */
    public function generateTranslatedFilename($sourceFilename): array
    {
        $filePath = $this->_importHelper->configHelper->getTranslatedXMLFilePath();
        if (!$this->driver->isExists($filePath)) {
            $this->driver->createDirectory($filePath);
        }
        return $this->_renameTranslatedFileName($filePath, $sourceFilename);
    }

    /**
     * @param $testJobNumber
     * @param $jobData
     * @param $isSandbox
     * @param $jobKey
     * @throws Exception
     */
    public function updateTJNumber($testJobNumber, $jobData, $isSandbox, $jobKey)
    {
        if (empty($this->getData('job_number')) && !empty($jobData->tj_number)) {
            if ($isSandbox) {
                if (!empty($jobKey)) {
                    $testJobNumber = $this->getTestJobNumberByJobKey($jobKey);
                }
                $this->setData('job_number', 'Test Job ' . $testJobNumber);
            } else {
                $this->setData('job_number', $jobData->tj_number);
            }

            $this->save();
        }
    }

    /**
     * Initialize resource model
     *
     * @return void
     */

    protected function _construct()
    {
        $this->_init(ResourceModel\Job::class);
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return array
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @param int $type , either JobType::JOB_TYPE_PRODUCT (default) or (JobType::JOB_TYPE_CATEGORY)
     * @return array
     */
    public function getEntities($type = JobType::JOB_TYPE_PRODUCT): array
    {
        $this->_loadEntities($type);
        return $this->_entities;
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProductCollection(): \Magento\Catalog\Model\ResourceModel\Product\Collection
    {
        $this->getAttributeTranslationEntityArray();
        return $this->_productCollectionFactory->create()
            ->addFieldToFilter('entity_id', ['in' => $this->_entityIds]);
    }

    /**
     * @return \Magento\Cms\Model\ResourceModel\Page\Collection
     */
    public function getPageCollection(): \Magento\Cms\Model\ResourceModel\Page\Collection
    {
        $this->getAttributeTranslationEntityArray();
        return $this->_pageCollectionFactory->create()
            ->addFieldToFilter('page_id', ['in' => $this->_entityIds]);
    }

    /**
     * @return \Magento\Cms\Model\ResourceModel\Block\Collection
     */
    public function getBlockCollection(): \Magento\Cms\Model\ResourceModel\Block\Collection
    {
        $this->getAttributeTranslationEntityArray();
        $collection = $this->_blockCollectionFactory->create()
            ->addFieldToFilter('main_table.block_id', ['in' => $this->_entityIds]);
        return $collection;
    }

    /**
     * @return Collection $collection
     */
    public function getCategoryCollection(): Collection
    {
        return $this->_getAttributeTranslationEntityCollection();
    }

    public function getAttributeTranslationEntityArray(): array
    {
        $collection = $this->_getAttributeTranslationEntityCollection();
        foreach ($collection->getData() as $item) {
            array_push($this->_entityIds, $item['entity_id']);
        }
        return $this->_entityIds;
    }

    private function _getAttributeTranslationEntityCollection(): Collection
    {
        return $this->_attributeTranslationCollectionFactory->create()
            ->distinct(true)
            ->addFieldToSelect('entity_id')
            ->addFieldToFilter('job_id', ['eq' => $this->getId()]);
    }

    protected function _loadEntities($type = JobType::JOB_TYPE_PRODUCT)
    {
        $this->_entities = [];
        $this->_entityCount = 0;

        if ($type == JobType::JOB_TYPE_CATEGORY) {
            foreach ($this->getCategoryCollection() as $category) {
                $this->_entities[$category->getEntityId()] = $category;
                $this->_entityCount++;
            }
        } else {
            foreach ($this->getProductCollection() as $product) {
                $this->_entities[$product->getEntityId()] = $product;
                $this->_entityCount++;
            }
        }
    }

    public function updateStatus($jobData): array
    {
        $return = ['isSuccess' => true, 'Message' => ''];
        $isSandbox = $this->_importHelper->configHelper->isSandboxMode();
        $jobKey = $this->getJobKey();
        $testJobNumber = $this->getId();

        try {
            switch (strtolower($jobData->status)) {
                case 'queued':
                    $this->updateTJNumber($testJobNumber, $jobData, $isSandbox, $jobKey);

                    if (empty($this->getData('job_number'))) {
                        $return['isSuccess'] = false;
                        $return['emptyTJ'] = true;
                        return $return;
                    }

                    $this->updateQueuedJobStatus($jobData);
                    break;
                case 'in_progress':
                    $this->updateTJNumber($testJobNumber, $jobData, $isSandbox, $jobKey);
                    $this->setData('job_status_id', JobStatus::JOB_STATUS_INPROGRESS)->save($this);
                    break;
                case 'completed':
                    $this->updateTJNumber($testJobNumber, $jobData, $isSandbox, $jobKey);
                    $return = $this->updateCompletedJobStatus($jobData, $return);
                    break;
                default:
                    $return['isSuccess'] = false;
                    $return['Message'] = __('Unknown status is found for the job (job_key: 1%)', $jobData->job_key);
                    $this->_logger->addError($return['Message']);
                    break;
            }
        } catch (Exception $e) {
            $return['isSuccess'] = false;
            $return['Message'] = __(
                'An Error with the message "1%" occurs while processing the job with the key - 2%',
                $e->getMessage(),
                $jobData->job_key
            );
            $this->_logger->addError($return['Message']);
        }

        return $return;
    }

    public function getJobStatus()
    {
        return $this->_jobStatusFactory->create()->load($this->getJobStatusId())->getStatusName();
    }

    public function getJobType()
    {
        return $this->_jobTypeFactory->create()->load($this->getJobTypeId())->getTypeName();
    }

    private function _renameTranslatedFileName($filePath, $originalFileName): array
    {
        $pos = stripos($originalFileName, '.xml');
        $pos = $pos !== false ? $pos : strlen($originalFileName);
        $fileName = substr_replace($originalFileName, '_translated', $pos);
//        $suffix = date('Y-m-d_H_i',time());
        return ['path' => $filePath, 'name' => $fileName  . '.xml'];
    }

    public function getEntityName($entityId = '1'): string
    {
        $title = '';
        switch ($this->getJobTypeId()) {
            case JobType::JOB_TYPE_PRODUCT:
                $title = $this->_productRepository->getById($entityId, false, $this->getSourceStoreId())->getName();
                break;
            case JobType::JOB_TYPE_CATEGORY:
                $title = $this->_categoryRepository->get($entityId, $this->getSourceStoreId())->getName();
                break;
            case JobType::JOB_TYPE_PAGE:
                $title = $this->_pageRepository->getById($entityId)->getTitle();
                break;
            case JobType::JOB_TYPE_BLOCK:
                $title = $this->_blockRepository->getById($entityId)->getTitle();
                break;
        }
        return $title;
    }

    public function getTestJobNumberByJobKey($jobKey): int
    {
        $data = $this->getResourceCollection()
            ->distinct(true)
            ->addFieldToSelect('job_number')
            ->addFieldToFilter('job_number', ['neq' => null])
            ->addFieldToFilter('is_test_job', ['eq' => 1])
            ->addFieldToFilter('job_key', ['neq' => $jobKey])
            ->count();
        return $data + 1;
    }

    public function getTranslatedPageId($sourcePageId)
    {
        $pageId = null;
        if ($this->getJobTypeId() <> JobType::JOB_TYPE_PAGE) {
            return null;
        }
        $targetStoreId = $this->getTargetStoreId();
        $sourcePage = $this->_pageRepository->getById($sourcePageId);
        $identifier = $sourcePage->getIdentifier();
        $collection = $this->_pageCollectionFactory->create()->addFieldToFilter('identifier', ['eq' => $identifier]);
        foreach ($collection->getItems() as $page) {
            $stores = $page->getStores();
            if (is_array($stores) && in_array($targetStoreId, $stores)) {
                $pageId = $page->getId();
                break;
            }
        }
        return $pageId;
    }

    public function getTranslatedBlockId($sourceBlockId)
    {
        $blockId = null;
        if ($this->getJobTypeId() <> JobType::JOB_TYPE_BLOCK) {
            return null;
        }
        $targetStoreId = $this->getTargetStoreId();
        $sourceBlock = $this->_blockRepository->getById($sourceBlockId);
        $identifier = $sourceBlock->getIdentifier();
        $collection = $this->_blockCollectionFactory->create()->addFieldToFilter('identifier', ['eq' => $identifier]);
        foreach ($collection->getItems() as $block) {
            $stores = $block->getStores();
            if (is_array($stores) && in_array($targetStoreId, $stores)) {
                $blockId = $block->getId();
                break;
            }
        }
        return $blockId;
    }

    public function _getLowestJobStatusId()
    {
        $jobStatus = $this->_getAllRelatedJobsCollection()
            ->addFieldToSelect('job_status_id')
            ->getData();

        $statusId = $this->getJobStatusId();

        foreach ($jobStatus as $status) {
            if ($statusId > $status['job_status_id']) {
                $statusId = $status['job_status_id'];
            }
        }

        return $statusId;
    }

    public function _setStatusForAllJobs($statusId)
    {
        $jobsCollection = $this->_getAllRelatedJobsCollection();

        foreach ($jobsCollection as $job) {
            $job->setData('job_status_id', $statusId)->save();
        }
    }

    private function _getAllRelatedJobIds()
    {
        $jobMatches = [];
        preg_match("/job_(.*?)_/", $this->getSourceFile(), $jobMatches);
        $separator = stripos($jobMatches[1], '&') === false ? '-' : '&';
        return explode($separator, $jobMatches[1]);
    }

    private function _getAllRelatedJobsCollection()
    {
        $jobIds = $this->_getAllRelatedJobIds();
        return $this->getCollection()
            ->addFieldToFilter('job_id', ['in' => $jobIds]);
    }

    /**
     * @param $jobData
     * @throws Exception
     */
    private function updateQueuedJobStatus($jobData): void
    {
        if (!empty($jobData->quotation) && strcasecmp($jobData->quotation, 'ready') === 0) {
            $this->setData('job_status_id', JobStatus::JOB_STATUS_READY)
                ->save($this);
        } else {
            $this->setData('job_status_id', JobStatus::JOB_STATUS_QUEUED)
                ->save($this);
        }
    }

    /**
     * @param $jobData
     * @param array $return
     * @return array
     * @throws FileSystemException
     */
    private function updateCompletedJobStatus(
        $jobData,
        array $return
    ): array {
        $isEmptyFile = false;

        if (!empty($jobData->translated_file) && count($jobData->translated_file)) {
            $downloadUrl = reset($jobData->translated_file)->download_url;
            if (!empty($downloadUrl)) {
                $fileContent = $this->_strakerApi->getTranslatedFile($downloadUrl);
                $fileNameArray = $this->generateTranslatedFilename($jobData->source_file);
                $fileFullName = implode(DIRECTORY_SEPARATOR, $fileNameArray);
                $result = true;

                if (!$this->driver->isExists($fileFullName)) {
                    $result = $this->driver->filePutContents($fileFullName, $fileContent);
                }

                $res = $this->driver->fileOpen($fileFullName, 'r');
                $firstLine = $this->driver->fileReadLine($res, 1024);

                if (preg_match('/^[<?xml]+/', $firstLine) == 0) {
                    $result = false;
                    $isEmptyFile = true;
                }

                if ($result == false && $isEmptyFile == true) {
                    $return['isSuccess'] = false;
                    $return['empty_file'] = false;
                    $return['Message'] = __(
                        '%1 - Failed to write content to 2%',
                        $this->getData('job_number'),
                        $fileFullName
                    );
                    $this->_logger->addError($return['Message']);
                } else {
                    $this->setData('download_url', $downloadUrl)
                        ->setData('translated_file', $fileNameArray['name'])->save($this);
                    $this->_importHelper->create($this->getId())
                        ->parseTranslatedFile()
                        ->saveData();

                    $this->setData('job_status_id', JobStatus::JOB_STATUS_COMPLETED)->save($this);
                }
            } else {
                $return['isSuccess'] = false;
                $return['Message'] = __(
                    'Download url is not found for the job (job_key: 1%)',
                    $jobData->job_key
                );
                $this->_logger->addError($return['Message']);
            }
        } else {
            $return['isSuccess'] = false;
            $return['Message'] = __(
                'Download file is not found for the job (job_key: 1%)',
                $jobData->job_key
            );
            $this->_logger->addError($return['Message']);
        }

        return $return;
    }
}
