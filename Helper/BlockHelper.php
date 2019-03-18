<?php

namespace Straker\EasyTranslationPlatform\Helper;

use Exception;
use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Eav\Model\AttributeRepository;
use Magento\Catalog\Model\ResourceModel\Category\Attribute\Collection as AttributeCollection;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory as BlockCollection;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use Straker\EasyTranslationPlatform\Model\AttributeTranslationFactory;
use Straker\EasyTranslationPlatform\Model\AttributeOptionTranslationFactory;
use Straker\EasyTranslationPlatform\Logger\Logger;
use Straker\EasyTranslationPlatform\Model\JobType;

class BlockHelper extends AbstractHelper
{

    protected $_blockCollectionFactory;
    protected $_attributeTranslationModel;
    protected $_attributeOptionTranslationModel;
    protected $_attributeCollectionFactory;
    protected $_storeManager;
    protected $_attributeTranslationFactory;
    protected $_attributeOptionTranslationFactory;
    protected $_attributeRepository;
    protected $_configHelper;
    protected $_attributeHelper;
    protected $_xmlHelper;

    protected $_entityTypeId;
    protected $_blockData;
    protected $_storeId;

    protected $_attributes = ['title','content'];
    protected $_strakerApi;

    public function __construct(
        Context $context,
        AttributeRepository $attributeRepository,
        AttributeCollection $attributeCollectionFactory,
        BlockCollection $blockCollectionFactory,
        AttributeTranslationFactory $attributeTranslationFactory,
        AttributeOptionTranslationFactory $attributeOptionTranslationFactory,
        Config $eavConfig,
        ConfigHelper $configHelper,
        AttributeHelper $attributeHelper,
        XmlHelper $xmlHelper,
        Logger $logger,
        StoreManagerInterface $storeManager,
        StrakerAPIInterface $strakerApi
    ) {
        $this->_attributeCollectionFactory = $attributeCollectionFactory;
        $this->_blockCollectionFactory = $blockCollectionFactory;
        $this->_attributeTranslationFactory = $attributeTranslationFactory;
        $this->_attributeOptionTranslationFactory = $attributeOptionTranslationFactory;
        $this->_attributeRepository = $attributeRepository;
        $this->_configHelper = $configHelper;
        $this->_attributeHelper = $attributeHelper;
        $this->_xmlHelper = $xmlHelper;
        $this->_logger = $logger;
        $this->_entityTypeId =  $eavConfig->getEntityType(CategoryAttributeInterface::ENTITY_TYPE_CODE)->getEntityTypeId();
        $this->_storeManager = $storeManager;
        $this->_strakerApi = $strakerApi;

        parent::__construct($context);
    }


    /**
     * @param $block_ids
     * @param $source_store_id
     * @return $this
     */
    public function getBlocks(
        $block_ids,
        $source_store_id
    ) {
    
        if (strpos($block_ids, '&') !== false) {
            $block_ids = explode('&', $block_ids);
        }

        $this->_storeId = $source_store_id;


        $blocks = $this->_blockCollectionFactory->create()
            ->addStoreFilter($source_store_id)
            ->addFieldToFilter('main_table.block_id', [ 'in' => $block_ids ]);

        $this->_blockData = $blocks->getItems();

        return $this;
    }

    /**
     * @return $this
     */
    public function getSelectedBlockAttributes()
    {

        $blockData = [];

        foreach ($this->_blockData as $data) {

            $attributeData = [];

            foreach ($this->_attributes as $attribute){

                if(in_array($attribute,$this->_attributes))
                {
                    array_push($attributeData, [
                        'attribute_code'=>$attribute,
                        'label'=>$attribute,
                        'value'=>$data->getData($attribute)
                    ]);
                }
            }

            $blockData[] = [
                'block_id'=>$data->getId(),
                'page_title'=>$data->getTitle(),
                'page_url'=>$this->_storeManager->getStore($this->_storeId)->getBaseUrl().$data->getIdentifier().'.html',//check
                'attributes'=>$attributeData
            ];
        }

        $this->_blockData = $blockData;

        return $this;
    }

    /**
     * @param $jobModel
     * @return string
     */
    public function generateBlockXML($jobModel)
    {
        $this->_xmlHelper->create('_'.$jobModel->getId().'_'.time());

        $this->addSummaryNode();

        $this->appendBlockAttributes(
            $this->_blockData,
            $jobModel->getId(),
            $jobModel->getData('job_type_id'),
            $jobModel->getData('source_store_id'),
            $jobModel->getData('target_store_id'),
            $this->_xmlHelper
        );

        $this->_xmlHelper->saveXmlFile();

        return $this->_xmlHelper->getXmlFileName();
    }


    /**
     * @param $pageData
     * @param $job_id
     * @param $jobType_id
     * @param $source_store_id
     * @param $target_store_id
     * @param $xmlHelper
     * @return $this|bool
     */
    protected function appendBlockAttributes(
        $blockData,
        $job_id,
        $jobType_id,
        $source_store_id,
        $target_store_id,
        $xmlHelper
    ) {

    
        if ($blockData) {

            foreach ($blockData as $data) {

                foreach ($data['attributes'] as $attribute) {

                        $job_name = $job_id.'_'.$jobType_id.'_'.$target_store_id.'_'.$data['block_id'].'_'.$attribute['attribute_code'];

                        $xmlHelper->appendDataToRoot([
                            'name' => $job_name,
                            'content_context' => 'block_attribute_value',
                            'content_context_url' => $data['page_url'],
                            'source_store_id'=> $source_store_id,
                            'block_id' => $data['block_id'],
//                            'attribute_id'=>$attribute['attribute_id'],
                            'attribute_translation_id'=>$attribute['value_translation_id'],
                            'attribute_code'=>$attribute['attribute_code'],
                            'attribute_label'=>$attribute['label'],
                            'value' => $attribute['value']
                        ]);
                }
            }

            return $this;
        }

        return false;
    }

    /**
     * @param $job_id
     * @return $this
     */
    public function saveBlockData($job_id)
    {

        foreach ($this->_blockData as $blockKey => $data) {

            foreach ($data['attributes'] as $attKey => $attribute) {

                $attributeTranslationModel = $this->_attributeTranslationFactory->create();


                try {
                    $attributeTranslationModel->setData(
                        [
                            'job_id' => $job_id,
                            'entity_id' => $data['block_id'],
                            'attribute_code' => $attribute['attribute_code'],
                            'original_value' => $attribute['value'],
                            'is_label' => (bool)0,
                            'label' => $attribute['label'],
                        ]
                    )->save();

                    $this->_blockData[$blockKey]['attributes'][$attKey]['value_translation_id'] = $attributeTranslationModel->getId();


                } catch (Exception $e) {
                    $this->_logger->error('error '.__FILE__.' '.__LINE__.''.$e->getMessage(), [$e]);
                    $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                }
            }
        }

        return $this;
    }

    public function addSummaryNode()
    {
        $summaryArray['cms_block'] = count($this->_blockData);
        $this->_xmlHelper->addContentSummary($summaryArray);
    }

    public function getSummary(){
        return ['cms_block' => count($this->_blockData)];
    }
}
