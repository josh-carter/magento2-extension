<?php

namespace Straker\EasyTranslationPlatform\Model\ResourceModel\Job\Grid;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Search\AggregationInterface;
use Magento\Framework\Registry;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Helper\JobHelper;
use Straker\EasyTranslationPlatform\Logger\Logger;
use Straker\EasyTranslationPlatform\Model\ResourceModel\Job\Collection as JobCollection;
use Magento\Framework\DB\Select;

/**
 * Class Collection
 * Collection for displaying grid of sales documents
 */
class Collection extends JobCollection implements SearchResultInterface
{
    /**
     * @var AggregationInterface
     */
    protected $aggregations;
    protected $_coreRegistry;

    /**
     * Collection constructor.
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param ConfigHelper $configHelper
     * @param Registry $registry
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $mainTable
     * @param AbstractDb $eventPrefix
     * @param $eventObject
     * @param $resourceModel
     * @param string $model
     * @param null $connection
     * @param AbstractDb|null $resource
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        Logger $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        ConfigHelper $configHelper,
        Registry $registry,
        $mainTable,
        $eventPrefix,
        $eventObject,
        $resourceModel,
        $model = 'Magento\Framework\View\Element\UiComponent\DataProvider\Document'
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $configHelper,
            $registry
        );
        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;
        $this->_coreRegistry = $registry;
        $this->_init($model, $resourceModel);
        $this->setMainTable($mainTable);
    }

    /**
     * @return AggregationInterface
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @param AggregationInterface $aggregations
     * @return $this
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
    }

    /**
     * Retrieve all ids for collection
     * Backward compatibility with EAV collection
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAllIds($limit = null, $offset = null)
    {
        return $this->getConnection()->fetchCol($this->_getAllIdsSelect($limit, $offset), $this->_bindParams);
    }

    /**
     * Get search criteria.
     *
     * @return \Magento\Framework\Api\SearchCriteriaInterface|null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * Set search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount()
    {
        $version = $this->_configHelper->getMagentoVersion();
        if (version_compare($version, '2.1.0', '>=')) {
            return $this->getSize();
        } else {
            return count($this);
        }
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * Set items list.
     *
     * @param \Magento\Framework\Api\ExtensibleDataInterface[] $items
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setItems(array $items = null)
    {
        return $this;
    }

    protected function _beforeLoad()
    {
        parent::_beforeLoad();
        if (!$this->hasFlag('get_data_without_group')) {
            $strakerJobTypeTable = $this->_resource->getTable('straker_job_type');
            $this->getSelect()
                ->reset(Select::COLUMNS)
                ->columns([
                    'job_id',
                    'job_number',
                    'GROUP_CONCAT(summary  SEPARATOR \''. JobHelper::SEPARATOR .'\') AS summary',
                    'created_at',
                    'updated_at',
                    'sl',
                    'tl',
                    'job_status_id',
                    'job_type_id',
                    'job_key',
                    'source_store_id',
                    'target_store_id',
                    'source_file',
                    'translated_file',
                    'download_url',
                    'is_test_job'
                ])->joinLeft(
                    [ 'st_type' => $strakerJobTypeTable ],
                    'st_type.type_id=main_table.job_type_id AND main_table.job_key IS NOT NULL AND main_table.job_key <> \'\'',
                    'GROUP_CONCAT(st_type.type_name SEPARATOR \''. JobHelper::SEPARATOR . '\') AS job_types'
                )
                ->where('is_test_job = ?', $this->_configHelper->isSandboxMode())
                ->group('job_key');
            $this->getSelect()->where('is_test_job = ?', $this->_configHelper->isSandboxMode())->group('job_key');
            $hasUpdatedJob = $this->_coreRegistry->registry('job_updated');
            if ($hasUpdatedJob) {
                $this->getSelect()->order('updated_at DESC');
                $this->_coreRegistry->unregister('job_updated');
            }
        }

        return $this;
    }
}
