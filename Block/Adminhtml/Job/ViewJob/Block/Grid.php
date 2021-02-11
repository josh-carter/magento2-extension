<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Block;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data as BackendHelperData;
use Magento\Framework\View\Element\Template;
use Straker\EasyTranslationPlatform\Model;
use Straker\EasyTranslationPlatform\Model\JobFactory;

class Grid extends Extended
{
    protected $_jobFactory;
    /** @var \Straker\EasyTranslationPlatform\Model\Job $_job */
    protected $_job;
    protected $_entityId;
    protected $_jobTypeId = Model\JobType::JOB_TYPE_ATTRIBUTE;
    protected $_jobKey;
    protected $_jobId;
    protected $_sourceStoreId;

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
        $blockCollection = $this->_job->getBlockCollection();
        if (!empty($this->_sourceStoreId) && is_numeric($this->_sourceStoreId)) {
            $blockCollection->addStoreFilter($this->_sourceStoreId);
        }
        $this->setCollection($blockCollection);
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
                'header' => __('Block ID'),
                'type' => 'number',
                'index' => 'block_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
        );
        $this->addColumn(
            'title',
            [
                'header' => __('Title'),
                'index' => 'title',
                'class' => 'xxx',
                'width' => '50px',
            ]
        );
        //phpcs:disable
        $this->addColumn(
            'view',
            [
                'header' => __('Action'),
                'type' => 'action',
                'getter' => 'getBlockId',
                'actions' => [
                    [
                        'caption' => __('View Details'),
                        'url' => [
                            'base' => '*/*/ViewJob',
                            'params' => [
                                'job_id' => $this->_job->getJobId(),
                                'job_type_id' => $this->_jobTypeId,
                                'job_type_referrer' => Model\JobType::JOB_TYPE_BLOCK,
                                'job_key' => $this->_jobKey,
                                'source_store_id' => $this->_sourceStoreId
                            ]
                        ],
                        'field' => 'entity_id'
                    ],
                    [
                        'caption' => __('View in the Backend'),
                        'url' => [
                            'base' => '*',
                            'params' => [
                                'job_id' => $this->_job->getJobId(),
                                'job_type_id' => $this->_jobTypeId,
                                'job_type_referrer' => Model\JobType::JOB_TYPE_BLOCK,
                                'job_key' => $this->_jobKey,
                                'source_store_id' => $this->_sourceStoreId,
                                'target_store_id'=>$this->_job->getTargetStoreId()
                            ]
                        ],
                        'field' => 'entity_id'
                    ]
                ],
                'filter' => false,
                'sortable' => false,
                'renderer' => \Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Grid\Renderer\MultiAction::class,
                'header_css_class' => 'col-action',
                'column_css_class' => 'col-action'
            ]
        );
        //phpcs:enable
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
                'entity_id' => $row->getBlockId(),
                'job_type_referrer' => Model\JobType::JOB_TYPE_BLOCK,
                'job_key' => $this->_jobKey,
                'source_store_id' => $this->_sourceStoreId,
            ]
        );
    }
}
