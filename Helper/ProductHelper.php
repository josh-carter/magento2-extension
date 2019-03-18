<?php

namespace Straker\EasyTranslationPlatform\Helper;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Ui\Component\Listing\Attribute\RepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Eav\Model\AttributeRepository;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\Framework\App\Helper\Context;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Model\StoreManagerInterface;

use Straker\EasyTranslationPlatform\Model\AttributeTranslationFactory;
use Straker\EasyTranslationPlatform\Model\ResourceModel\AttributeTranslation\Collection as AttributeTranslationCollection;
use Straker\EasyTranslationPlatform\Model\AttributeOptionTranslationFactory;
use Straker\EasyTranslationPlatform\Logger\Logger;

class ProductHelper extends AbstractHelper
{

    protected $_productFactory;
    protected $_collectionFactory;
    protected $_attributeTranslationModel;
    protected $_attributeOptionTranslationModel;
    protected $_storeManager;

    protected $_entityTypeId;
    protected $_productData;
    protected $_xmlData = [];
    protected $_storeId;

    protected $_translatedAttributeOptions = [];

    protected $_translatableBackendType =  [
        'varchar', 'text','int'
    ];

    protected $_translatableFrontendInputType = [
        'select', 'text','multiline', 'textarea', 'multiselect'
    ];

    protected $_translatableAttributeCode = [
        'name', 'description', 'meta_title', 'meta_keywords', 'meta_description', 'short_description', 'color','size'
    ];

    protected $_multiSelectInputTypes = [
        'select', 'multiselect'
    ];

    protected $_productFilterTypes = [
        'text', 'price', 'weight', 'date', 'select', 'multiselect', 'boolean'
    ];

    /**
     * @var array
     */
    protected $filterMap = [
        'default' => 'text',
        'select' => 'options',
        'boolean' => 'options',
        'multiselect' => 'options',
        'date' => 'datetime',
    ];

    protected $_attributeCollectionFactory;
    protected $_productCollectionFactory;
    protected $_attributeTranslationFactory;
    protected $_attributeOptionTranslationFactory;
    protected $_attributeRepository;
    protected $_configHelper;
    protected $_attributeHelper;
    protected $_xmlHelper;
    protected $_attributeTranslationsCollectionFactory;

    //stores attributes that used in grid as filters
    protected $_productFilters;

    /**
     * ProductHelper constructor.
     * @param Context $context
     * @param AttributeRepository $attributeRepository
     * @param AttributeCollection $attributeCollectionFactory
     * @param ProductCollection $productCollectionFactory
     * @param AttributeTranslationFactory $attributeTranslationFactory
     * @param AttributeOptionTranslationFactory $attributeOptionTranslationFactory
     * @param Config $eavConfig
     * @param \Straker\EasyTranslationPlatform\Helper\ConfigHelper $configHelper
     * @param \Straker\EasyTranslationPlatform\Helper\AttributeHelper $attributeHelper
     * @param \Straker\EasyTranslationPlatform\Helper\XmlHelper $xmlHelper
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param RepositoryInterface $productFilters
     * @param AttributeTranslationCollection $attributeTranslationCollectionFactory
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Context $context,
        AttributeRepository $attributeRepository,
        AttributeCollection $attributeCollectionFactory,
        ProductCollection $productCollectionFactory,
        AttributeTranslationFactory $attributeTranslationFactory,
        AttributeOptionTranslationFactory $attributeOptionTranslationFactory,
        Config $eavConfig,
        ConfigHelper $configHelper,
        AttributeHelper $attributeHelper,
        XmlHelper $xmlHelper,
        Logger $logger,
        StoreManagerInterface $storeManager,
        RepositoryInterface $productFilters,
        AttributeTranslationCollection $attributeTranslationCollectionFactory
    ) {
        $this->_attributeCollectionFactory = $attributeCollectionFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_attributeTranslationFactory = $attributeTranslationFactory;
        $this->_attributeOptionTranslationFactory = $attributeOptionTranslationFactory;
        $this->_attributeRepository = $attributeRepository;
        $this->_configHelper = $configHelper;
        $this->_attributeHelper = $attributeHelper;
        $this->_xmlHelper = $xmlHelper;
        $this->_logger = $logger;
        $this->_entityTypeId =  $eavConfig->getEntityType(ProductAttributeInterface::ENTITY_TYPE_CODE)->getEntityTypeId();
        $this->_storeManager = $storeManager;
        $this->_attributeTranslationsCollectionFactory = $attributeTranslationCollectionFactory;
        $this->_productFilters = $productFilters;
        parent::__construct($context);
    }


    /**
     * @return \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection
     */
    public function getDefaultAttributes()
    {
        /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $collection */
        $collection = $this->getAttributes()
            ->addFieldToFilter('attribute_code', [ 'in' => $this->_translatableAttributeCode ]);
        return $collection;
    }

    public function getCustomAttributes()
    {
        /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $collection */
        $collection = $this->getAttributes()
            ->addFieldToFilter('is_user_defined', [ 'eq' => 1 ])
            ->addFieldToFilter('attribute_code', ['nin'=>$this->_translatableAttributeCode]);
        return $collection;
    }

    public function getAttributes()
    {
        /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $collection */
        $collection = $this->_attributeCollectionFactory->create();
        $collection->setEntityTypeFilter($this->_entityTypeId)
            ->setFrontendInputTypeFilter([ 'in' => $this->_translatableFrontendInputType ])
            ->addFieldToFilter('backend_type', [ 'in' => $this->_translatableBackendType ])
            ->setOrder('attribute_id', 'asc');
        return $collection;
    }

    /**
     * Retrieve the attributes which used in grid for Product type
     *
     * @return ProductAttributeInterface[]
     */
    public function getProductGridAttributeList(){
        $attributes = $this->_productFilters->getList();
        if (!empty($attributes)){
            return array_filter($attributes, function($attrCode){
                $attr = $this->getProductAttribute($attrCode);
                return !is_null($attr) && in_array($attr->getFrontendInput(), $this->_productFilterTypes);
            });
        }
        return [];
    }

    /**
     * Retrieve the attributes which used in grid for Product type
     *
     * @return ProductAttributeInterface[]
     */
    public function getSelectedProductFilters(){
        $productFilters = $this->_configHelper->getProductFilters();
        /** @var ProductAttributeInterface[] $attributes */
        $attributes = [];
        if (!empty($productFilters)){
            $filterCollection = $this->_attributeCollectionFactory->create();
            $filterCollection->setEntityTypeFilter($this->_entityTypeId)
                ->addFieldToFilter('attribute_code', ['in' => $productFilters]);
            /** @var ProductAttributeInterface $attribute */
            foreach($filterCollection->getItems() as $attribute){
                $frontendInput = $attribute->getFrontendInput();
                if (in_array($frontendInput, $this->_productFilterTypes)){
                    $data = [];
                    $data['header'] = $attribute->getDefaultFrontendLabel();
                    $data['code'] = $attribute->getAttributeCode();
                    $data['frontendInput'] = $frontendInput;
                    $data['backendModel'] = $attribute->getBackendModel();
                    $data['type'] = $this->getFilterType($frontendInput);

                    if($data['frontendInput'] === 'boolean'){
                        $data['options'] = [0 => __('No'), 1 => 'Yes'];
                    }else{
                        $data['options'] = $this->toGridOptionArray($attribute->getSource()->getAllOptions());
                    }

                    $attributes[$attribute->getAttributeCode()] = $data;
                }
            }
        }
        return $attributes;
    }


    /**
     * @param $product_ids
     * @param $source_store_id
     * @param bool $includeChildren
     * @return $this
     */
    public function getProducts(
        $product_ids,
        $source_store_id,
        $includeChildren = true
    ) {
        if (strpos($product_ids, '&') !== false) {
            $product_ids = explode('&', $product_ids);
        }

        $this->_storeManager->setCurrentStore($source_store_id);

        if ($includeChildren) {
            $childrenIds = $this->_getChildrenProducts($product_ids);
            if (is_array($product_ids)) {
                $product_ids = array_merge($product_ids, $childrenIds);
            } else {
                $product_ids = array_merge([$product_ids], $childrenIds);
            }
        }

        $products = $this->_productCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addIdFilter($product_ids)
            ->load();

        $this->_storeId = $source_store_id;

        $this->_productData = $products;

        return $this;
    }

    /**
     * @return $this
     */
    public function getSelectedProductAttributes()
    {
        $attributes = array_merge($this->_configHelper->getDefaultAttributes(), $this->_configHelper->getCustomAttributes());

        $productAttributeData = [];

        foreach ($this->_productData as $product) {

            $attributeData = [];

            if ($product->getData('type_id') =='configurable') {

                $attributeData = $this->_attributeHelper->getConfigurableAttributes($product);
            }

            foreach ($attributes as $attribute_id) {

                if (in_array($this->_attributeRepository->get(\Magento\Catalog\Model\Product::ENTITY, $attribute_id)->getFrontendInput(), $this->_multiSelectInputTypes)) {

                    if ($this->_attributeHelper->findMultiOptionAttributes($attribute_id, $product, $this->_storeId)) {

                        array_push($attributeData, $this->_attributeHelper->findMultiOptionAttributes($attribute_id, $product, $this->_storeId));
                    }
                } else {

                    if ($product->getResource()->getAttributeRawValue($product->getId(), $attribute_id, $this->_storeId)) {

                        array_push($attributeData,
                            [
                                'attribute_id'=>$attribute_id,
                                'attribute_code'=> $product->getResource()->getAttribute($attribute_id)->getAttributeCode(),
                                'label'=>$product->getResource()->getAttribute($attribute_id)->getStoreLabel($this->_storeId),
                                'value'=>$product->getResource()->getAttributeRawValue($product->getId(), $attribute_id, $this->_storeId),

                            ]
                        );
                    }
                }
            }

            //Sort Attribute Data by Id Asc
            usort($attributeData, function ($a, $b) {

                return $a['attribute_id'] - $b['attribute_id'];
            });

            $productAttributeData[] = [
                'product_id'    =>  $product->getId(),
                'product_name'  =>  $product->getName(),
                'product_url'   =>  $product->setStoreId($this->_storeId)->getUrlInStore(),
                'product_type'  =>  $product->getTypeId(),
                'attributes'    =>  $attributeData
            ];
        }

        $this->_productData = $productAttributeData;

        return $this;
    }

    /**
     * @param $jobModel
     * @return string
     */
    public function generateProductXML($jobModel)
    {

        $this->_xmlHelper->create('_'.$jobModel->getId().'_'.time());
        $this->addSummaryNode();

        $collection = $this->_attributeTranslationsCollectionFactory;
        $attribute_option_table = $collection->getTable('straker_attribute_option_translation');
        $collection
            ->getSelect()
            ->joinLeft(
                ['att_option'=>$attribute_option_table],
                'main_table.attribute_translation_id=att_option.attribute_translation_id',
                [
                    'att_option.attribute_option_translation_id as optionTranslationId',
                    'att_option.option_id as mgOptionId',
                    'att_option.original_value as optionValue'
                ]
            )->where(
                'main_table.job_id='.$jobModel->getId().''
            );

        $this->appendProductAttributes(
            $collection->getData(),
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
     * @param $xmlData
     * @param $job_id
     * @param $jobType_id
     * @param $source_store_id
     * @param $target_store_id
     * @param $xmlHelper
     * @return $this
     */
    protected function appendProductAttributes(
        $xmlData,
        $job_id,
        $jobType_id,
        $source_store_id,
        $target_store_id,
        $xmlHelper
    ) {

        $appendedAttributes = [];

        $job_name = $job_id.'_'.$jobType_id.'_'.$target_store_id.'_'.$source_store_id;

        foreach ($xmlData as $data){

            if($data['is_label']=='1'){

                if(!in_array($data['attribute_code'],$appendedAttributes)){

                    $xmlHelper->appendDataToRoot([
                        'name' => $job_name,
                        'content_context' => 'product_attribute_label_value',
                        'attribute_translation_id'=>$data['attribute_translation_id'],
                        'attribute_code' => $data['attribute_code'],
                        'value' => $data['label'],
                        'entity_id'=> $data['entity_id'],
                        'is_label'=>$data['is_label']
                    ]);

                    array_push($appendedAttributes,$data['attribute_code']);
                }

            }

            if(!is_null($data['optionTranslationId'])){

                $xmlHelper->appendDataToRoot([
                    'name' => $job_name,
                    'content_context' => 'product_attribute_option_value',
                    'option_translation_id'=>$data['optionTranslationId'],
                    'option_id'=> $data['mgOptionId'],
                    'value' => $data['optionValue'],
                    'is_option'=> (bool)1
                ]);
            }

            if($data['is_label']=='0'){

                $xmlHelper->appendDataToRoot([
                    'name' => $job_name,
                    'content_context' => 'product_attribute_value',
                    'attribute_translation_id'=>$data['attribute_translation_id'],
                    'attribute_code' => $data['attribute_code'],
                    'value' => $data['original_value'],
                    'entity_id'=> $data['entity_id'],
                    'is_label'=>$data['is_label']
                ]);
            }
        }

        return $this;
    }

    /**
     * @param $job_id
     * @return $this
     */
    public function saveProductData($job_id)
    {

        $optionData = [];

        $insertData = [];

        foreach ($this->_productData as $key => $data){

            foreach ($data['attributes'] as $attribute){

                if(is_array($attribute['value'])){

                    if(isset($optionData[$attribute['attribute_code']])){

                        $newValueArray = array_merge($optionData[$attribute['attribute_code']]['value'], $attribute['value']);

                        $optionData[$attribute['attribute_code']]['value'] = $newValueArray;

                    }else{

                        $optionData[$attribute['attribute_code']] = $attribute;
                        $optionData[$attribute['attribute_code']]['product_id'] = $data['product_id'];
                    }

                }else{

                    $labelData = [
                        'job_id' => $job_id,
                        'entity_id' => $data['product_id'],
                        'attribute_id' => $attribute['attribute_id'],
                        'attribute_code' => $attribute['attribute_code'],
                        'original_value' => $attribute['label'],
                        'is_label' => (bool)1,
                        'label' => $attribute['label']
                    ];

                    $insertData[] = $labelData;

                    $valueData = [
                        'job_id' => $job_id,
                        'entity_id' => $data['product_id'],
                        'attribute_id' => $attribute['attribute_id'],
                        'attribute_code' => $attribute['attribute_code'],
                        'original_value' => $attribute['value'],
                        'is_label' => (bool)0,
                        'label' => $attribute['label']
                    ];

                    $insertData[] = $valueData;

                }
            }
        }


        $attributeModel = $this->_attributeTranslationFactory->create();

        $table = $attributeModel->getResource()->getTable('straker_attribute_translation');

        $attributeModel->getResource()->getConnection()->insertMultiple($table,$insertData);

        if($optionData){

            $this->saveOptionValues($optionData,$job_id);
        }

        return $this;

    }

    /**
     * @param $optionData
     * @param $job_id
     * @return $this
     */
    protected function saveOptionValues(
        $optionData,
        $job_id
    ) {

        $insertData = [];

        foreach ($optionData as $key => $data) {

            $optionData[$key]['value'] = array_unique($data['value'], SORT_REGULAR);

        }

        foreach ($optionData as $data){

            $attributeValue = $this->_attributeTranslationFactory->create();

            $attributeValue->setData(
                [
                    'job_id' => $job_id,
                    'entity_id' => $data['product_id'],
                    'attribute_id' => $data['attribute_id'],
                    'attribute_code' => $data['attribute_code'],
                    'original_value' => $data['label'],
                    'is_label' => (bool)1,
                    'label' => $data['label'],
                    'has_option'=>(bool)1
                ]
            )->save();

            foreach ($data['value'] as $option){

                $insertData[] = [
                    'attribute_translation_id' => $attributeValue->getId(),
                    'option_id' => $option['option_id'],
                    'original_value' => $option['value']
                ];

            }

        }

        $attributeTranslationOptionModel = $this->_attributeOptionTranslationFactory->create();

        $table = $attributeTranslationOptionModel->getResource()->getTable('straker_attribute_option_translation');

        $attributeTranslationOptionModel->getResource()->getConnection()->insertMultiple($table,$insertData);

        return $this;

    }

    private function _getChildrenProducts($parentIds = [])
    {
        $children = [];
        $types = [
            Type::TYPE_BUNDLE,
            Grouped::TYPE_CODE,
            Configurable::TYPE_CODE
        ];

        if (!is_array($parentIds)) {
            $parentIds = [ $parentIds ];
        }

        if (count($parentIds) > 0) {
            $parentProducts = $this->_productCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addIdFilter($parentIds)
                ->load();
            /** @var \Magento\Catalog\Model\Product $product */
            foreach ($parentProducts->getItems() as $product) {
                $productTypeInstance = $product->getTypeInstance();
                $productTypeId = $product->getTypeId();
                if (in_array($productTypeId, $types)) {
                    $childrenArray = $productTypeInstance->getChildrenIds($product->getId());
                    foreach ($childrenArray as $childrenItem) {
                        foreach ($childrenItem as $k => $v) {
                            if (!in_array($v, $children)) {
                                array_push($children, $v);
                            }
                        }
                    }
                }
            }
        }
        return $children;
    }

    public function addSummaryNode()
    {
        $summaryArray['product'] = $this->getSummary();
        $this->_xmlHelper->addContentSummary($summaryArray);
    }

    public function getSummary(){
        $productArray = [];
        foreach($this->_productData as $productData){
            if(key_exists($productData['product_type'], $productArray)){
                $productArray[$productData['product_type']] += 1;
            }else{
                $productArray[$productData['product_type']] = 1;
            }
        }
        return $productArray;
    }

    public function getConfigHelper(){
        return $this->_configHelper;
    }

    /**
     * Retrieve filter type by $frontendInput
     *
     * @param string $frontendInput
     * @return string
     */
    protected function getFilterType($frontendInput)
    {
        return isset($this->filterMap[$frontendInput]) ? $this->filterMap[$frontendInput] : $this->filterMap['default'];
    }

    /**
     * get a product's attribute by attribute code
     *
     * @param $attributeCode
     * @return \Magento\Eav\Api\Data\AttributeInterface|null
     */
    public function getProductAttribute($attributeCode){
        try{
            $attribute = $this->_attributeRepository->get(ProductAttributeInterface::ENTITY_TYPE_CODE, $attributeCode);
            return $attribute;
        }catch(\Exception $e){
            return null;
        }
    }

    private function toGridOptionArray($array){
        $newArray = [];
        if(is_array($array) && sizeof($array) > 0){
            foreach($array as $a){
                if(isset($a['value']) && isset($a['label']) && !empty($a['value'])) {
                    $newArray[$a['value']] = $a['label'];
                }
            }
        }
        return $newArray;
    }

}
