<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Product;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data as BackendHelperData;
use Straker\EasyTranslationPlatform\Model;
use Straker\EasyTranslationPlatform\Model\JobFactory;

use Magento\Catalog\Model\ProductFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as SetFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\TypeFactory;

use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteFactory;

class Grid extends Extended
{
    protected $_jobFactory;
    protected $_job;
    protected $_entityId;
    protected $_jobTypeId = Model\JobType::JOB_TYPE_ATTRIBUTE;
    protected $_jobKey;
    protected $_jobId;
    protected $_sourceStoreId;
    protected $_productModel;
    protected $_productSetModel;
    protected $_productTypeModel;
    protected $_productStatusModel;
    protected $_websitesModel;

    public function __construct(
        Context $context,
        BackendHelperData $backendHelper,
        JobFactory $jobFactory,
        ProductFactory $productFactory,
        SetFactory $productSetFactory,
        Status $productStatus,
        TypeFactory $productTypeFactory,
        WebsiteFactory $websitesFactory,
        array $data = []
    ) {
        $this->_jobFactory = $jobFactory;
        $this->_productTypeModel = $productTypeFactory->create();
        $this->_productModel = $productFactory->create();
        $this->_productSetModel = $productSetFactory->create();
        $this->_websitesModel = $websitesFactory->create();
        $this->_productStatusModel = $productStatus;
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
        $productCollection = $this->_job->getProductCollection()
            ->addAttributeToSelect(
                'name'
            )->addAttributeToSelect(
                'price'
            )->addAttributeToSelect(
                'status'
            )->addWebsiteNamesToResult();

        if (!empty($this->_sourceStoreId) && is_numeric($this->_sourceStoreId)) {
            $productCollection->addStoreFilter($this->_sourceStoreId);
        }
        $this->setCollection($productCollection);
        return parent::_prepareCollection();
    }


    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
//        $this->addColumn(
//            'in_product',
//            [
//                'type' => 'checkbox',
//                'name' => 'in_product',
//                'align' => 'center',
//                'index' => 'entity_id'
//            ]
//        );

        $this->addColumn(
            'entity_id',
            [
                'header' => __('Product ID'),
                'type' => 'number',
                'index' => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
        );
        $this->addColumn(
            'name',
            [
                'header' => __('Name'),
                'index' => 'name',
                'class' => 'xxx',
                'width' => '50px',
            ]
        );
        $this->addColumn(
            'type',
            [
                'header' => __('Type'),
                'index' => 'type_id',
                'type' => 'options',
                'options' => $this->_productTypeModel->getOptionArray()
            ]
        );
        $this->addColumn(
            'sku',
            [
                'header' => __('Sku'),
                'index' => 'sku',
                'class' => 'xxx',
                'width' => '50px',
            ]
        );
        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'type' => 'currency',
                'index' => 'price',
                'width' => '50px',
            ]
        );

        $sets = $this->_productSetModel->setEntityTypeFilter(
            $this->_productModel->getResource()->getTypeId()
        )->load()->toOptionHash();

        $this->addColumn(
            'attribute_set',
            [
                'header' => __('Attribute Set'),
                'index' => 'attribute_set_id',
                'type' => 'options',
                'options' => $sets
            ]
        );

        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => $this->_productStatusModel->getOptionArray(),
            ]
        );

        if (!$this->_storeManager->isSingleStoreMode()) {
            $this->addColumn(
                'websites',
                [
                    'header' => __('Websites'),
                    'sortable' => false,
                    'index' => 'websites',
                    'type' => 'options',
                    'options' => $this->_websitesModel->toOptionHash()
                ]
            );
        }

        $this->addColumn(
            'view',
            [
                'header' => __('Action'),
                'type' => 'action',
                'getter' => 'getEntityId',
                'actions' => [
                    [
                        'caption' => __('View Details'),
                        'url' => [
                            'base' => '*/*/ViewJob',
                            'params' => [
                                'job_id' => $this->_job->getJobId(),
                                'job_type_id' => $this->_jobTypeId,
                                'job_type_referrer' => Model\JobType::JOB_TYPE_PRODUCT,
                                'job_key' => $this->_jobKey,
                                'source_store_id' => $this->_sourceStoreId
                            ]
                        ],
                        'field' => 'entity_id'
                    ],
                    [
                        'caption' => __('View in the Frontend'),
                        'url' => [
                            'base' => '*',
                            'params' => [
                                'job_id' => $this->_job->getJobId(),
                                'job_type_id' => $this->_jobTypeId,
                                'job_type_referrer' => Model\JobType::JOB_TYPE_PRODUCT,
                                'job_key' => $this->_jobKey,
                                'source_store_id' => $this->_sourceStoreId,
                                'target_store_id'=>$this->_job->getTargetStoreId()
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
                                'job_type_referrer' => Model\JobType::JOB_TYPE_PRODUCT,
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
                'renderer' => 'Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Grid\Renderer\MultiAction',
                'header_css_class' => 'col-action',
                'column_css_class' => 'col-action'
            ]
        );

        return parent::_prepareColumns();
    }

    protected function _addColumnFilterToCollection($column)
    {
        if ($this->getCollection()) {
            if ($column->getId() == 'websites') {
                $this->getCollection()->joinField(
                    'websites',
                    'catalog_product_website',
                    'website_id',
                    'product_id=entity_id',
                    null,
                    'left'
                );
            }
        }
        return parent::_addColumnFilterToCollection($column);
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
                'job_type_referrer' => Model\JobType::JOB_TYPE_PRODUCT,
                'job_key' => $this->_jobKey,
                'source_store_id' => $this->_sourceStoreId
            ]
        );
    }
}
