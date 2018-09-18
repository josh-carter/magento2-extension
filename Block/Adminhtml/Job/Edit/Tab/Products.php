<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Framework\App\ResourceConnection;

use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteFactory;

use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product\VisibilityFactory;
use Magento\Catalog\Model\Product\TypeFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as SetFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Model\JobFactory;
use Straker\EasyTranslationPlatform\Model\ProductCollectionFactory;
use Straker\EasyTranslationPlatform\Model\ResourceModel\Job\CollectionFactory as JobCollectionFactory;

class Products extends \Magento\Backend\Block\Widget\Grid\Extended
{
    protected $_massactionBlockName = \Straker\EasyTranslationPlatform\Block\Adminhtml\Job\Edit\Grid\Massaction\Extended::class;
    protected $productCollectionFactory;
    protected $jobFactory;
    protected $sourceStoreId;
    protected $_configHelper;
    protected $jobCollectionFactory;
    protected $resourceConnection;
    protected $targetStoreId;
    protected $productVisibilityModel;
    protected $productTypeModel;
    protected $productSetModel;
    protected $productStatusModel;
    protected $productModel;
    protected $websitesModel;

    public function __construct(
        Context $context,
        Data $backendHelper,
        JobFactory $jobFactory,
        ProductFactory $productFactory,
        ProductCollectionFactory $productCollectionFactory,
        ConfigHelper $configHelper,
        JobCollectionFactory $jobCollectionFactory,
        ResourceConnection $resourceConnection,
        VisibilityFactory $productVisibilityFactory,
        TypeFactory $productTypeFactory,
        SetFactory $productSetFactory,
        Status $productStatus,
        WebsiteFactory $websiteFactory,
        array $data = []
    ) {
        $this->jobFactory = $jobFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->_configHelper = $configHelper;
        $this->jobCollectionFactory = $jobCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->productVisibilityModel = $productVisibilityFactory->create();
        $this->productTypeModel = $productTypeFactory->create();
        $this->productSetModel = $productSetFactory->create();
        $this->productModel = $productFactory->create();
        $this->productStatusModel = $productStatus;
        $this->websitesModel = $websiteFactory->create();
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * _construct
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('productsGrid');
        $this->setDefaultSort('entity_id');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->sourceStoreId = $this->getRequest()->getParam('source_store_id');
        $this->targetStoreId = $this->getRequest()->getParam('target_store_id');
    }

    protected function _prepareCollection()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(
            'name'
        )->addAttributeToSelect(
            'price'
        )->addAttributeToSelect(
            'visibility'
        )->addAttributeToSelect(
            'status'
        )->setStore(
            $this->sourceStoreId
        )->is_translated(
            $this->targetStoreId
        )->addWebsiteNamesToResult();

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
//
//        $this->addColumn(
//            'in_product',
//            [
//                'type' => 'massaction',
////                'reander' => 'Magento\Backend\Block\Widget\Grid\Column\Renderer\Massaction',
//                'name' => 'in_product',
//                'align' => 'center',
//                'index' => 'entity_id',
//                'is_system' => true,
//                'header_css_class' => 'col-select',
//                'column_css_class' => 'col-select',
//                'values' => $this->_getSelectedProducts()
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
            ]
        );
        $this->addColumn(
            'type',
            [
                'header' => __('Type'),
                'index' => 'type_id',
                'type' => 'options',
                'options' => $this->productTypeModel->getOptionArray()
            ]
        );

        $sets = $this->productSetModel->setEntityTypeFilter(
            $this->productModel->getResource()->getTypeId()
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
            'sku',
            [
                'header' => __('Sku'),
                'index' => 'sku',
                'class' => 'xxx',
            ]
        );
        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'type' => 'currency',
                'index' => 'price',
            ]
        );

        $this->addColumn(
            'visibility',
            [
                'header' => __('Visibility'),
                'type' => 'options',
                'options' => $this->productVisibilityModel->getOptionArray(),
                'index' => 'visibility',
            ]
        );

        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => $this->productStatusModel->getOptionArray(),
            ]
        );

        if (!$this->_storeManager->isSingleStoreMode()){
            $this->addColumn(
                'websites',
                [
                    'header' => __('Websites'),
                    'sortable' => false,
                    'index' => 'websites',
                    'type' => 'options',
                    'options' => $this->websitesModel->toOptionHash()
                ]
            );
        }

        $this->addColumn(
            'is_translated',
            [
                'header' => __('Translated'),
                'index' => 'is_translated',
                'width' => '50px',
                'type'=>'options',
                'options'=>['1'=>'Yes','0'=>'No'],
//                'filter_index'=>'stTrans.translated_value',
                'renderer' => 'Straker\EasyTranslationPlatform\Block\Adminhtml\Job\Edit\Grid\Renderer\TranslatedValue',
                'filter_condition_callback' => array($this, '_filterCallback'),
                'order_callback' => [$this, '_orderIsTranslated']
            ]
        );

        return parent::_prepareColumns();
    }

    protected function _getSelectedProducts()
    {
        $products = $this->getRequest()->getPost('job_products');

        if ($products) {
            return $products;
        }

        return [];
    }

    function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->setFormFieldName('products');
        $this->getMassactionBlock()->setTemplate('Straker_EasyTranslationPlatform::job/massaction_extended.phtml');
        $this->getMassactionBlock()->addItem('create', []);

        return $this;
    }

    protected function _addColumnFilterToCollection($column){
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
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/productsgrid', ['_current' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return true;
    }

    protected function _filterCallback($collection, $column)
    {
        $condition = $column->getFilter()->getCondition();
        $collection->getSelect()->having('`is_translated` = ?', reset($condition));
        return $this;
    }

    //in order to sort and filter on computed columns,
    //1, rewrite _setCollectionOrder.
    //2. implement callback defined in column data, like 'order_callback' => [$this, '_orderIsTranslated'].
    protected function _orderIsTranslated($collection, $column){
        $collection->getSelect()->order($column->getIndex() . ' ' . strtoupper($column->getDir()));
    }

    protected function _setCollectionOrder($column)
    {
        if ($column->getOrderCallback()) {
            call_user_func($column->getOrderCallback(), $this->getCollection(), $column);
            return $this;
        }
        return parent::_setCollectionOrder($column);
    }

    public function _getSerializerBlock()
    {
        return $this->getLayout()->getBlock('products_grid_serializer');
    }

    public function _getHiddenInputElementName()
    {
        $serializerBlock = $this->_getSerializerBlock();
        return empty($serializerBlock) ? 'products' : $serializerBlock->getInputElementName();
    }

    public function _getReloadParamName()
    {
        $serializerBlock = $this->_getSerializerBlock();
        return empty($serializerBlock) ? 'job_products' : $serializerBlock->getReloadParamName();
    }
}
