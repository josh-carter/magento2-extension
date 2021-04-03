<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended as GridExtended;
use Magento\Backend\Helper\Data;
use Straker\EasyTranslationPlatform\Block\Adminhtml\Job\Edit\Grid\Massaction\Extended;
use Straker\EasyTranslationPlatform\Model\BlockCollection as BlockCollectionFactory;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Model\JobFactory;

class Blocks extends GridExtended
{
    protected $_massactionBlockName = Extended::class;
    protected $_blockCollectionFactory;
    protected $_jobFactory;
    protected $_configHelper;
    protected $targetStoreId;
    protected $sourceStoreId;

    public function __construct(
        Context $context,
        Data $backendHelper,
        JobFactory $jobFactory,
        BlockCollectionFactory $blockCollectionFactory,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        $this->_jobFactory = $jobFactory;
        $this->_blockCollectionFactory = $blockCollectionFactory;
        $this->_configHelper = $configHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * _construct
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('blocksGrid');
        $this->setDefaultSort('block_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->sourceStoreId = $this->getRequest()->getParam('source_store_id');
        $this->targetStoreId = $this->getRequest()->getParam('target_store_id');
    }

    protected function _prepareCollection()
    {
        $collection = $this->_blockCollectionFactory;
        if ($this->sourceStoreId) {
            $collection->addStoreFilter($this->sourceStoreId);
        }
        $collection->isTranslated($this->targetStoreId);
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
//        $this->addColumn(
//            'in_block',
//            [
//                'header_css_class' => 'a-center',
//                'type' => 'checkbox',
//                'name' => 'in_block',
//                'align' => 'center',
//                'index' => 'block_id',
//                'filter_index'=>'block_id',
//                'values' => $this->_getSelectedBlocks()
//            ]
//        );

        $this->addColumn(
            'block_id',
            [
                'header' => __('Block ID'),
                'type' => 'number',
                'index' => 'block_id',
                'filter_index'=>'block_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
        );
        $this->addColumn(
            'title',
            [
                'header' => __('Title'),
                'index' => 'title',
                'filter_index'=>'title',
                'class' => 'xxx',
            ]
        );

        $this->addColumn(
            'is_translated',
            [
                'header'                    => __('Translated'),
                'index'                     => 'is_translated',
                'width'                     => '50px',
                'type'                      =>'options',
                'options'                   =>  [0 => __('No'), 1 => __('Yes')],
                'filter_condition_callback' => [$this, 'filterIsTranslated']
            ]
        );

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('block_id');
        $this->getMassactionBlock()->setTemplate('Straker_EasyTranslationPlatform::job/massaction_extended.phtml');
        $this->getMassactionBlock()->addItem('create', []);

        return $this;
    }

    protected function _getSelectedBlocks()
    {
        $blocks = $this->getRequest()->getPost('job_blocks');
        if (is_array($blocks)) {
            return $blocks;
        }
        return [];
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/blocksgrid', ['_current' => true]);
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

    public function filterIsTranslated($collection, $column)
    {
        $condition = $column->getFilter()->getCondition();
        $collection->getSelect()->having('`is_translated` =  ?', reset($condition));
        return $this;
    }

    public function _getSerializerBlock()
    {
        return $this->getLayout()->getBlock('blocks_grid_serializer');
    }

    public function _getHiddenInputElementName()
    {
        $serializerBlock = $this->_getSerializerBlock();
        return empty($serializerBlock) ? 'blocks' : $serializerBlock->getInputElementName();
    }

    public function _getReloadParamName()
    {
        $serializerBlock = $this->_getSerializerBlock();
        return empty($serializerBlock) ? 'job_blocks' : $serializerBlock->getReloadParamName();
    }
}
