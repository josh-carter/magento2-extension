<?php

namespace Straker\EasyTranslationPlatform\Helper;

use Exception;
use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Eav\Model\AttributeRepository;
use Magento\Catalog\Model\ResourceModel\Category\Attribute\Collection as AttributeCollection;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollection;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use Straker\EasyTranslationPlatform\Model\AttributeTranslationFactory;
use Straker\EasyTranslationPlatform\Model\AttributeOptionTranslationFactory;
use Straker\EasyTranslationPlatform\Logger\Logger;
use Straker\EasyTranslationPlatform\Model\JobType;

class PageHelper extends AbstractHelper
{

    protected $_productFactory;
    protected $_pageCollectionFactory;
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
    protected $_pageData;
    protected $_storeId;

    protected $_attributes = ['title','meta_keywords','meta_description','content_heading','content','meta_title'];
    protected $_strakerApi;

    public function __construct(
        Context $context,
        AttributeRepository $attributeRepository,
        AttributeCollection $attributeCollectionFactory,
        PageCollection $pageCollectionFactory,
        AttributeTranslationFactory $attributeTranslationFactory,
        AttributeOptionTranslationFactory $attributeOptionTranslationFactory,
        Config $eavConfig,
        ConfigHelper $configHelper,
        AttributeHelper $attributeHelper,
        XmlHelper $xmlHelper,
        Logger $logger,
        StoreManagerInterface $storeManager,
        StrakerAPIInterface $strakerAPI
    ) {
        $this->_attributeCollectionFactory = $attributeCollectionFactory;
        $this->_pageCollectionFactory = $pageCollectionFactory;
        $this->_attributeTranslationFactory = $attributeTranslationFactory;
        $this->_attributeOptionTranslationFactory = $attributeOptionTranslationFactory;
        $this->_attributeRepository = $attributeRepository;
        $this->_configHelper = $configHelper;
        $this->_attributeHelper = $attributeHelper;
        $this->_xmlHelper = $xmlHelper;
        $this->_logger = $logger;
        $this->_entityTypeId =  $eavConfig->getEntityType(CategoryAttributeInterface::ENTITY_TYPE_CODE)->getEntityTypeId();
        $this->_storeManager = $storeManager;
        $this->_strakerApi = $strakerAPI;
        parent::__construct($context);
    }


    /**
     * @param $page_ids
     * @param $source_store_id
     * @return $this Todo: Add store id to filter products by store
     * Todo: Add store id to filter products by store
     * @internal param $product_ids
     * @internal param $store_id
     */
    public function getPages(
        $page_ids,
        $source_store_id
    ) {
    
        if (strpos($page_ids, '&') !== false) {
            $page_ids = explode('&', $page_ids);
        }

        $this->_storeId = $source_store_id;

        $pages = $this->_pageCollectionFactory->create()
            ->addStoreFilter($this->_storeId)
            ->addFieldToFilter('page_id', [ 'in' => $page_ids ]);

        $this->_pageData = $pages->getItems();

        return $this;
    }

    /**
     * @return $this
     */
    public function getSelectedPageAttributes()
    {

        $pageData = [];

        foreach ($this->_pageData as $data) {

            $attributeData = [];

            foreach ($this->_attributes as $attribute) {

                if (in_array($attribute, $this->_attributes) && (!empty($data->getData($attribute)))) {
                    array_push($attributeData, [
                        'attribute_code'=>$attribute,
                        'label'=>$attribute,
                        'value'=>$data->getData($attribute)
                    ]);
                }
            }

            $pageData[] = [
                'page_id'=>$data->getId(),
                'page_title'=>$data->getTitle(),
                'page_url'=>$this->_storeManager->getStore($this->_storeId)->getBaseUrl().$data->getIdentifier().'.html',//check
                'attributes'=>$attributeData
            ];
        }

        $this->_pageData = $pageData;

        return $this;
    }

    /**
     * @param $jobModel
     * @return string
     */
    public function generatePageXML($jobModel)
    {
        $this->_xmlHelper->create('_'.$jobModel->getId().'_'.time());

        $this->addSummaryNode();

        $this->appendPageAttributes(
            $this->_pageData,
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
    protected function appendPageAttributes(
        $pageData,
        $job_id,
        $jobType_id,
        $source_store_id,
        $target_store_id,
        $xmlHelper
    ) {
    
        if ($pageData) {

            foreach ($pageData as $data) {

                foreach ($data['attributes'] as $attribute) {

                        $job_name = $job_id.'_'.$jobType_id.'_'.$target_store_id.'_'.$data['page_id'].'_'.$attribute['attribute_code'];

                        $xmlHelper->appendDataToRoot([
                            'name' => $job_name,
                            'content_context' => 'page_attribute_value',
                            'content_context_url' => $data['page_url'],
                            'attribute_translation_id'=>$attribute['value_translation_id'],
                            'source_store_id'=> $source_store_id,
                            'page_id' => $data['page_id'],
                            //'attribute_id'=>$attribute['attribute_id'],
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
    public function savePageData($job_id)
    {

        foreach ($this->_pageData as $pageKey => $data) {

            foreach ($data['attributes'] as $attKey => $attribute) {

                $attributeTranslationModel = $this->_attributeTranslationFactory->create();

                try {
                    $attributeTranslationModel->setData(
                        [
                            'job_id' => $job_id,
                            'entity_id' => $data['page_id'],
                            'attribute_code' => $attribute['attribute_code'],
                            'original_value' => $attribute['value'],
                            'is_label' => (bool)0,
                            'label' => $attribute['label']
                        ]
                    )->save();


                    $this->_pageData[$pageKey]['attributes'][$attKey]['value_translation_id'] = $attributeTranslationModel->getId();


                } catch (Exception $e) {
                    $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                    $this->_logger->error('error '.__FILE__.' '.__LINE__.''.$e->getMessage(), [$e]);
                }
            }
        }

        return $this;
    }

    private function addSummaryNode()
    {
        $summaryArray = $this->getSummary();
        $this->_xmlHelper->addContentSummary($summaryArray);
    }

    public function getSummary()
    {
        return ['cms_page' => count($this->_pageData)];
    }
}
