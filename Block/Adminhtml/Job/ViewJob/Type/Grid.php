<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Type;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data as BackendHelperData;
use Magento\Framework\View\Element\Template;
use Straker\EasyTranslationPlatform\Model;
use Straker\EasyTranslationPlatform\Model\ResourceModel\Job\CollectionFactory;

class Grid extends Extended
{
    protected $_jobCollectionFactory;
    /** @var \Straker\EasyTranslationPlatform\Model\Job $_job */
    protected $_job;
    protected $_entityId;
    protected $_jobTypeId = Model\JobType::JOB_TYPE_ATTRIBUTE;
    protected $_jobKey;
    protected $_sourceStoreId;

    public function __construct(
        Context $context,
        BackendHelperData $backendHelper,
        CollectionFactory $jobCollectionFactory,
        array $data = []
    ) {
        $this->_jobCollectionFactory = $jobCollectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        $requestData = $this->getRequest()->getParams();
        $this->_jobKey = $requestData['job_key'];
        $this->_sourceStoreId = $requestData['source_store_id'];
        parent::_construct();
    }

    /**
     * prepare collection
     */
    protected function _prepareCollection()
    {
        $jobCollection = $this->_jobCollectionFactory->create()->addFieldToFilter('job_key', ['eq'=>$this->_jobKey ]);
        $jobCollection->setOrder('job_type_id', 'ASC');
        $this->setCollection($jobCollection);
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->_filterVisibility = false;

        $this->addColumn(
            'job_id',
            [
                'header' => __('Job ID'),
                'type' => 'number',
                'filter' => false,
                'sortable' => false,
                'index' => 'job_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(
            'job_type',
            [
                'header' => __('Content Type'),
                'filter' => false,
                'sortable' => false,
                'index' => 'job_type',
                'type' => 'xxx',
                'width' => '50px',
                'renderer' => \Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Grid\Renderer\JobType::class
            ]
        );
        $this->addColumn(
            'created_at',
            [
                'header' => __('Created At'),
                'filter' => false,
                'sortable' => false,
                'index' => 'created_at',
                'type' => 'datetime',
                'width' => '50px'
            ]
        );
        $this->addColumn(
            'updated_at',
            [
                'header' => __('Updated At'),
                'filter' => false,
                'sortable' => false,
                'type' => 'datetime',
                'index' => 'updated_at',
                'width' => '50px'
            ]
        );

        $this->addColumn(
            'view',
            [
                'header' => __('Action'),
                'type' => 'action',
                'getter' => 'getId',
                'renderer' => \Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Grid\Renderer\JobTypeActions::class,
                'filter' => false,
                'sortable' => false,
                'index' => 'view',
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
                'job_id' => $row['job_id'],
                'job_type_id' => $row['job_type_id'],
                'entity_id' => $row->getEntityId(),
                'job_type_referrer' => 0,
                'job_key' => $this->_jobKey,
                'source_store_id' => $this->_sourceStoreId
            ]
        );
    }
}
