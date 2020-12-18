<?php

namespace Straker\EasyTranslationPlatform\Helper;

use Exception;
use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Config;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Eav\Model\AttributeRepository;
use Magento\Catalog\Model\ResourceModel\Category\Attribute\Collection as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollection;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use Straker\EasyTranslationPlatform\Model\AttributeTranslationFactory;
use Straker\EasyTranslationPlatform\Model\AttributeOptionTranslationFactory;
use Straker\EasyTranslationPlatform\Logger\Logger;

class CategoryHelper extends AbstractHelper
{

    protected $_productFactory;
    protected $_categoryCollectionFactory;
    protected $_attributeCollectionFactory;
    protected $_storeManager;

    protected $_entityTypeId;
    protected $_categoryData;
    protected $_storeId;

    protected $_translatableAttributeCode = [
        'name','description','meta_title','meta_keywords','meta_description'
    ];

    protected $_attributeTranslationFactory;
    protected $_attributeOptionTranslationFactory;
    protected $_attributeRepository;
    protected $_configHelper;
    protected $_attributeHelper;
    protected $_xmlHelper;
    protected $_strakerApi;
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * CategoryHelper constructor.
     * @param Context $context
     * @param AttributeRepository $attributeRepository
     * @param AttributeCollection $attributeCollectionFactory
     * @param CategoryCollection $categoryCollectionFactory
     * @param AttributeTranslationFactory $attributeTranslationFactory
     * @param AttributeOptionTranslationFactory $attributeOptionTranslationFactory
     * @param Config $eavConfig
     * @param \Straker\EasyTranslationPlatform\Helper\ConfigHelper $configHelper
     * @param \Straker\EasyTranslationPlatform\Helper\AttributeHelper $attributeHelper
     * @param \Straker\EasyTranslationPlatform\Helper\XmlHelper $xmlHelper
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param StrakerAPIInterface $strakerAPI
     */
    public function __construct(
        Context $context,
        AttributeRepository $attributeRepository,
        AttributeCollection $attributeCollectionFactory,
        CategoryCollection $categoryCollectionFactory,
        AttributeTranslationFactory $attributeTranslationFactory,
        AttributeOptionTranslationFactory $attributeOptionTranslationFactory,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        Config $eavConfig,
        ConfigHelper $configHelper,
        AttributeHelper $attributeHelper,
        XmlHelper $xmlHelper,
        Logger $logger,
        StoreManagerInterface $storeManager,
        StrakerAPIInterface $strakerAPI
    ) {
        $this->_attributeCollectionFactory = $attributeCollectionFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_attributeTranslationFactory = $attributeTranslationFactory;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->_attributeOptionTranslationFactory = $attributeOptionTranslationFactory;
        $this->_attributeRepository = $attributeRepository;
        $this->_configHelper = $configHelper;
        $this->_attributeHelper = $attributeHelper;
        $this->_xmlHelper = $xmlHelper;
        $this->_logger = $logger;
        $this->_entityTypeId = $eavConfig->getEntityType(CategoryAttributeInterface::ENTITY_TYPE_CODE)
            ->getEntityTypeId();
        $this->_storeManager = $storeManager;
        $this->_strakerApi = $strakerAPI;
        parent::__construct($context);
    }

    public function getAttributes()
    {
        return $this->_attributeCollectionFactory->setEntityTypeFilter($this->_entityTypeId)
            ->addFieldToFilter('attribute_code', [ 'in' => $this->_translatableAttributeCode]);
    }

    public function getCategorySetting()
    {
        $selectedAttributeIds = $this->_configHelper->getCategoryAttributes();
        $selectedCategoryAttributes = [];

        if (count($selectedAttributeIds) > 0) {
            $searchCriteria = $this->searchCriteriaBuilderFactory->create()
                ->addFilter('attribute_id', $selectedAttributeIds, 'in')
                ->create();
            $selectedAttributes = $this->_attributeRepository->getList(Category::ENTITY, $searchCriteria)->getItems();
            $selectedCategoryAttributes = [];
            foreach ($selectedAttributes as $attribute) {
                $selectedCategoryAttributes[] = $attribute->getAttributeCode();
            }

            $selectedCategoryAttributes = array_intersect(
                $selectedCategoryAttributes,
                $this->_translatableAttributeCode
            );
        }

        return $selectedCategoryAttributes;
    }

    /**
     * @param $category_ids
     * @param $source_store_id
     * @return $this
     */
    public function getCategories(
        $category_ids,
        $source_store_id
    ) {
    
        if (strpos($category_ids, ',') !== false) {
            $category_ids = explode(',', $category_ids);
        }

        $this->_storeManager->setCurrentStore($source_store_id);

        $categories = $this->_categoryCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addIdFilter($category_ids)
            ->load();

        $this->_storeId = $source_store_id;

        $this->_categoryData = $categories;

        return $this;
    }

    /**
     * @return $this
     */
    public function getSelectedCategoryAttributes()
    {
        $categoryData = [];
        $selectedCategories = $this->getCategorySetting();

        foreach ($this->_categoryData as $category) {
            $attributeData = [];

            foreach ($this->getAttributes() as $attribute) {
                if (in_array($attribute->getAttributeCode(), $selectedCategories)) {
                    array_push(
                        $attributeData,
                        [
                            'attribute_id' => $attribute->getId(),
                            'label' => $category
                                ->getResource()
                                ->getAttribute($attribute->getId())->getStoreLabel($this->_storeId),
                            'value' =>
                                $category
                                    ->getResource()
                                    ->getAttributeRawValue($category->getId(), $attribute->getId(), $this->_storeId)
                        ]
                    );
                }
            }

            $categoryData[] = [
                'category_id' => $category->getId(),
                'category_name' => $category->getName(),
                'category_url' => $this->_storeManager->getStore($this->_storeId)->getBaseUrl()
                    . $category->getUrlKey()
                    . '.html',
                'attributes' => $attributeData
            ];
        }

        $this->_categoryData = $categoryData;

        return $this;
    }

    /**
     * @param $jobModel
     * @return string
     */
    public function generateCategoryXML($jobModel)
    {
        $this->_xmlHelper->create('_'.$jobModel->getId().'_'.time());
        $this->addSummaryNode();

        $this->appendCategoryAttributes(
            $this->_categoryData,
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
     * @param $categoryData
     * @param $job_id
     * @param $jobtype_id
     * @param $source_store_id
     * @param $target_store_id
     * @param $xmlHelper
     * @return $this|bool
     */
    protected function appendCategoryAttributes(
        $categoryData,
        $job_id,
        $jobtype_id,
        $source_store_id,
        $target_store_id,
        $xmlHelper
    ) {
        if ($categoryData) {
            foreach ($categoryData as $data) {
                foreach ($data['attributes'] as $attribute) {
                    if ($attribute['value']) {
                        $job_name = $job_id
                            . '_' . $jobtype_id
                            . '_' . $target_store_id
                            . '_' . $data['category_id']
                            . '_' . $attribute['attribute_id'];

                        $xmlHelper->appendDataToRoot([
                            'name' => $job_name,
                            'content_context' => 'category_attribute_value',
                            'content_context_url' => $data['category_url'],
                            'attribute_translation_id'=>$attribute['value_translation_id'],
                            'source_store_id'=> $source_store_id,
                            'category_id' => $data['category_id'],
                            'attribute_id'=>$attribute['attribute_id'],
                            'attribute_label'=>$attribute['label'],
                            'value' => $attribute['value']
                        ]);
                    }
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
    public function saveCategoryData($job_id)
    {

        foreach ($this->_categoryData as $cat_key => $data) {
            foreach ($data['attributes'] as $att_key => $attribute) {
                $attributeTranslationModel = $this->_attributeTranslationFactory->create();

                if ($attribute['value']) {
                    try {
                        $attributeTranslationModel->setData(
                            [
                                'job_id' => $job_id,
                                'entity_id' => $data['category_id'],
                                'attribute_id' => $attribute['attribute_id'],
                                'original_value' => $attribute['value'],
                                'is_label' => (bool)0,
                                'label' => $attribute['label']
                            ]
                        )->save();

                        $this->_categoryData[$cat_key]['attributes'][$att_key]['value_translation_id'] = $attributeTranslationModel->getId();
                    } catch (Exception $e) {
                        $this->_logger->error('error '.__FILE__.' '.__LINE__.''.$e->getMessage(), [$e]);
                        $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                    }
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
        return ['category' => count($this->_categoryData)];
    }
}
