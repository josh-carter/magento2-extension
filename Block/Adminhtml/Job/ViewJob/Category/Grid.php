<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Category;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data as BackendHelperData;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Grid\Renderer\MultiAction;
use Straker\EasyTranslationPlatform\Model;
use Straker\EasyTranslationPlatform\Model\Job;
use Straker\EasyTranslationPlatform\Model\JobFactory;

class Grid extends Extended
{
    protected $_jobFactory;
    /** @var Job $_job */
    protected $_job;
    protected $_jobTypeId = Model\JobType::JOB_TYPE_ATTRIBUTE;
    protected $_jobKey;
    protected $_jobId;
    protected $_sourceStoreId;
    protected $_attributeRepository;

    public function __construct(
        Context $context,
        BackendHelperData $backendHelper,
        JobFactory $jobFactory,
        array $data = []
    ) {
        $this->_jobFactory = $jobFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        $requestData = $this->getRequest()->getParams();
        $this->_jobId = $requestData['job_id'];
        $this->_jobKey = $requestData['job_key'];
        $this->_sourceStoreId = $this->getRequest()->getParam('source_store_id');
        $this->_job = $this->_jobFactory->create()->load($this->_jobId);
        parent::_construct();
    }

    /**
     * prepare collection
     */
    protected function _prepareCollection()
    {
        /** @var \Straker\EasyTranslationPlatform\Model\ResourceModel\AttributeTranslation\Collection $strakerCategoryCollection */
        $strakerCategoryCollection = $this->_job->getCategoryCollection();
        if (!empty($this->_sourceStoreId) && is_numeric($this->_sourceStoreId)) {
            $this->setCollection($strakerCategoryCollection->addCategoryName($this->_sourceStoreId));
        } else {
            $this->setCollection($strakerCategoryCollection->addCategoryName());
        }
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
//        $this->addColumn(
//            'in_category',
//            [
//                'type' => 'checkbox',
//                'name' => 'in_category',
//                'align' => 'center',
//                'index' => 'entity_id'
//            ]
//        );

        $this->addColumn(
            'entity_id',
            [
                'header' => __('Category ID'),
                'type' => 'number',
                'index' => 'entity_id',
                'filter_index' => 'main_table.entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
        );
        $this->addColumn(
            'name',
            [
                'header' => __('Name'),
                'type'  => 'text',
                'index' => 'name',
                'width' => '50px',
                'filter_condition_callback' => [$this, 'filterName']
            ]
        );

        $this->addColumn(
            'view',
            [
                'header' => __('Action'),
                'type' => 'action',
                'getter' => 'getEntityId',
                'actions' => [
                    [
                        'caption' => __('View Details'),
                        'url' => $this->getUrl(
                            '*/*/viewJob',
                            [
                                'job_id' => $this->_job->getJobId(),
                                'job_type_id' => $this->_jobTypeId,
                                'job_type_referrer' => Model\JobType::JOB_TYPE_CATEGORY,
                                'job_key' => $this->_jobKey,
                                'source_store_id' => $this->_sourceStoreId
                            ]
                        ),
                        'field' => 'entity_id'
                    ],
                    [
                        'caption' => __('View in the Frontend'),
                        'url' => $this->getUrl(
                            '*/*/viewJob',
                            [
                                'job_id' => $this->_job->getJobId(),
                                'job_type_id' => $this->_jobTypeId,
                                'job_type_referrer' => Model\JobType::JOB_TYPE_CATEGORY,
                                'job_key' => $this->_jobKey,
                                'source_store_id' => $this->_sourceStoreId,
                                'target_store_id'=>$this->_job->getTargetStoreId()
                            ]
                        ),
                        'field' => 'entity_id'
                    ],
                    [
                        'caption' => __('View in the Backend'),
                        'url' => $this->getUrl(
                            '*/*/viewJob',
                            [
                                'job_id' => $this->_job->getJobId(),
                                'job_type_id' => $this->_jobTypeId,
                                'job_type_referrer' => Model\JobType::JOB_TYPE_CATEGORY,
                                'job_key' => $this->_jobKey,
                                'source_store_id' => $this->_sourceStoreId,
                                'target_store_id'=>$this->_job->getTargetStoreId()
                            ]
                        ),
                        'field' => 'entity_id'
                    ]
                ],
                'filter' => false,
                'sortable' => false,
                'renderer' => MultiAction::class,
                'header_css_class' => 'col-action',
                'column_css_class' => 'col-action'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @param \Magento\Catalog\Model\Product|\Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl(
            '*/*/ViewJob',
            [
                'job_id' => $this->_job->getJobId(),
                'job_type_id' => $this->_jobTypeId,
                'entity_id' => $row->getEntityId(),
                'job_type_referrer' => Model\JobType::JOB_TYPE_CATEGORY,
                'job_key' => $this->_jobKey,
                'source_store_id' => $this->_sourceStoreId
            ]
        );
    }

    public function filterName($collection, $column)
    {
        $condition = $column->getFilter()->getCondition();
        $collection->getSelect()->having('`name` LIKE ' . reset($condition));
        return $this;
    }
}
