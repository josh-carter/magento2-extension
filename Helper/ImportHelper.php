<?php

namespace Straker\EasyTranslationPlatform\Helper;

use Magento\Cms\Model\Block;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Xml\Parser;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

use Magento\Framework\Message\ManagerInterface;

use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory as OptionCollection;
use Magento\Cms\Model\ResourceModel\Block\Collection;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Cms\Model\PageFactory as PageFactory;
use Magento\Cms\Model\BlockFactory as BlockFactory;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Attribute;
use Straker\EasyTranslationPlatform\Logger\Logger;
use Straker\EasyTranslationPlatform\Helper\XmlHelper;

use Straker\EasyTranslationPlatform\Model\AttributeTranslation;
use Straker\EasyTranslationPlatform\Model\BlockCollection;
use Straker\EasyTranslationPlatform\Model\JobStatus;
use Straker\EasyTranslationPlatform\Model\ResourceModel\AttributeTranslation as AttributeTranslationResourceModel;
use Straker\EasyTranslationPlatform\Model\JobFactory;
use Straker\EasyTranslationPlatform\Model\AttributeOptionTranslation;
use Straker\EasyTranslationPlatform\Model\AttributeTranslationFactory;
use Straker\EasyTranslationPlatform\Model\AttributeOptionTranslationFactory;
use Straker\EasyTranslationPlatform\Model\ResourceModel\AttributeTranslation\CollectionFactory as AttributeTranslationCollection;
use Straker\EasyTranslationPlatform\Model\ResourceModel\AttributeOptionTranslation\CollectionFactory as AttributeOptionTranslationCollection;

class ImportHelper extends AbstractHelper
{
    /** @var $this ->configHelper \Straker\EasyTranslationPlatform\Helper\ConfigHelper */
    public $configHelper;

    protected $_logger;
    protected $_xmlParser;
    protected $_xmlHelper;
    protected $_attributeTranslationFactory;
    protected $_attributeOptionTranslationFactory;
    protected $_attributeTranslationCollection;
    protected $_attributeOptionTranslationCollection;
    protected $_categoryCollection;
    protected $_jobFactory;
    protected $_attributeRepository;
    protected $_productAction;
    protected $_resourceConnection;
    protected $_attributeCollection;
    protected $_optionCollection;
    protected $_storeManager;
    protected $_pageFactory;
    protected $_blockFactory;
    protected $_urlFinder;
    protected $_jobModel;
    protected $_parsedFileData = [];
    protected $_translatedLabels = [];
    protected $_attributeTranslationIds;
    protected $_saveOptionIds = [];

    protected $_productData;
    protected $_categoryData;
    protected $_timezoneInterface;

    protected $_selectQuery = 'select option_id from %1$s where option_id = %2$s and store_id = %3$s';
    protected $_updateQuery = 'update %1$s set value = "%2$s" where option_id = %3$s and store_id = %4$s';
    protected $_labelTable = 'catalog_product_super_attribute_label';
    protected $_categoryFactory;
    protected $_pageData;
    protected $_blockData;
    protected $_block;

    protected $_messageManager;
    protected $_blockCollection;
    protected $_strakerApi;

    public function __construct(
        Context $context,
        Logger $logger,
        Parser $xmlParser,
        XmlHelper $xmlHelper,
        ConfigHelper $configHelper,
        JobFactory $jobFactory,
        AttributeTranslationFactory $attributeTranslationFactory,
        AttributeOptionTranslationFactory $attributeOptionTranslationFactory,
        AttributeTranslationCollection $attributeTranslationCollection,
        AttributeOptionTranslationCollection $attributeOptionTranslationCollection,
        AttributeRepositoryInterface $attributeRepository,
        ProductAction $productAction,
        ResourceConnection $resourceConnection,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryFactory $categoryFactory,
        AttributeCollection $attributeCollection,
        OptionCollection $optionCollection,
        PageFactory $pageFactory,
        BlockFactory $blockFactory,
        StoreManagerInterface $storeManager,
        UrlFinderInterface $urlFinder,
        TimezoneInterface $timezone,
        BlockCollection $block,
        ManagerInterface $messageManager,
        StrakerAPIInterface $strakerApi
    )
    {
        $this->_logger = $logger;
        $this->_xmlParser = $xmlParser;
        $this->_xmlHelper = $xmlHelper;
        $this->configHelper = $configHelper;
        $this->_jobFactory = $jobFactory;
        $this->_attributeTranslationFactory = $attributeTranslationFactory;
        $this->_attributeOptionTranslationFactory = $attributeOptionTranslationFactory;
        $this->_attributeTranslationCollection = $attributeTranslationCollection;
        $this->_attributeOptionTranslationCollection = $attributeOptionTranslationCollection;
        $this->_categoryCollection = $categoryCollectionFactory;
        $this->_categoryFactory = $categoryFactory;
        $this->_attributeRepository = $attributeRepository;
        $this->_productAction = $productAction;
        $this->_resourceConnection = $resourceConnection;
        $this->_attributeCollection = $attributeCollection;
        $this->_optionCollection = $optionCollection;
        $this->_pageFactory = $pageFactory;
        $this->_blockFactory = $blockFactory;
        $this->_storeManager = $storeManager;
        $this->_urlFinder = $urlFinder;
        $this->_timezoneInterface = $timezone;
        $this->_blockCollection = $block;
        $this->_messageManager = $messageManager;
        $this->_strakerApi = $strakerApi;
        parent::__construct($context);
    }

    public function create($job_id)
    {
        $this->_jobModel = $this->_jobFactory->create()->load($job_id);

        return $this;

    }

    public function parseTranslatedFile()
    {

        try{

            $filePath = $this->configHelper->getTranslatedXMLFilePath() . DIRECTORY_SEPARATOR . $this->_jobModel->getData('translated_file');

            $parsedData = $this->_xmlParser->load($filePath)->xmlToArray();

            $dataArray = key_exists('data', $parsedData['root']) ? $parsedData['root']['data'] : $parsedData['root']['_value']['data'];

            if(key_exists('_value', $dataArray)){

                $this->_parsedFileData[0] = $dataArray;

            }else{

                $this->_parsedFileData = $dataArray;
            }

            $this->_categoryData = array_filter($this->_parsedFileData, function ($v) {

                return preg_match('/category/', $v['_attribute']['content_context']);

            });

            $this->_productData = array_filter($this->_parsedFileData, function ($v) {

                return preg_match('/product/', $v['_attribute']['content_context']);

            });

            $this->_pageData = array_filter($this->_parsedFileData, function ($v) {

                return preg_match('/page/', $v['_attribute']['content_context']);

            });

            $this->_blockData = array_filter($this->_parsedFileData, function ($v) {

                return preg_match('/block/', $v['_attribute']['content_context']);

            });

            return $this;

        }catch (\Exception $e){

            $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
            $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
            $this->_messageManager->addError($e->getMessage());

        }

    }

    public function saveData()
    {
        if (!empty($this->_productData)) {
            $this->saveTranslatedProductData();
        }
        if (!empty($this->_categoryData)) {
            $this->saveTranslatedCategoryData();
        }

        if (!empty($this->_pageData)) {
            $this->saveTranslatedPageData();
        }

        if (!empty($this->_blockData)) {
            $this->saveTranslatedBlockData();
        }

        return $this;
    }

    public function publishTranslatedData()
    {
        if ($this->_jobModel->getJobType() == 'product') {
            $this->publishTranslatedProductData();
        }

        if ($this->_jobModel->getJobType() == 'category') {
            $this->publishTranslatedCategoryData();
        }

        if ($this->_jobModel->getJobType() == 'page') {
            $this->publishTranslatedPageData();
        }

        if ($this->_jobModel->getJobType() == 'block') {
            $this->publishTranslatedBlockData();
        }

        return $this;
    }


    public function saveTranslatedProductData()
    {
        $this->getOptionIds($this->_jobModel->getId());

        foreach ($this->_productData as $data) {

            if (array_key_exists('attribute_translation_id', $data['_attribute'])) {

                try {

                    $att_trans_model = $this->_attributeTranslationFactory->create()->load($data['_attribute']['attribute_translation_id']);

                    $att_trans_model->addData(['translated_value' => $data['_value']['value']]);

                    $att_trans_model->addData(['is_imported' => 1, 'imported_at' => $this->_timezoneInterface->date()->format('y-m-d H:i:s')]);

                    $att_trans_model->save();

                    ($data['_attribute']['is_label']==1) ? $this->saveLabel($data['_attribute']['attribute_code'], $data['_value']['value']) : false;

                } catch (\Exception $e) {

                    $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
                    $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                    $this->_messageManager->addError($e->getMessage());
                }

            }

            if (array_key_exists('option_translation_id', $data['_attribute'])) {

                try {

                    $att_opt_model = $this->_attributeOptionTranslationFactory->create()->load($data['_attribute']['option_translation_id']);

                    $att_opt_model->addData(['translated_value' => $data['_value']['value']]);

                    $att_opt_model->addData(['is_imported' => 1, 'imported_at' => $this->_timezoneInterface->date()->format('y-m-d H:i:s')]);

                    $att_opt_model->save();

                    if (!in_array($att_opt_model->getData('option_id'), $this->_saveOptionIds)) {

                        $translatedOptions = $this->_attributeOptionTranslationCollection->create()
                            ->addFieldToSelect(['option_id', 'translated_value'])
                            ->addFieldToFilter('attribute_translation_id', array('in' => $this->_attributeTranslationIds))
                            ->addFieldToFilter('option_id', array('eq' => $att_opt_model->getData('option_id')));

                        $translatedOptions->massUpdate(array('translated_value' => $att_opt_model->getData('translated_value'),'is_imported'=>1,'imported_at' => $this->_timezoneInterface->date()->format('y-m-d H:i:s')));

                        $this->_saveOptionIds[] = $att_opt_model->getData('option_id');

                    }

                } catch (\Exception $e) {

                    $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
                    $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                    $this->_messageManager->addError($e->getMessage());

                }

            }

        }

        return $this;
    }


    public function publishTranslatedProductData()
    {
        $product_ids = $this->getProductIds($this->_jobModel->getId());

        $this->publishTranslatedOptionValues($this->_jobModel->getId());

        $this->publishTranslatedAttributeLabels($this->_jobModel->getId());

        foreach ($product_ids as $id) {

            $productData = $this->_attributeTranslationCollection->create()
                ->addFieldToSelect(['attribute_id', 'original_value', 'translated_value'])
                ->addFieldToFilter('job_id', array('eq' => $this->_jobModel->getId()))
                ->addFieldToFilter('entity_id', array('eq' => $id))
                ->addFieldToFilter('is_label', array('eq' => 0));

            $attData = [];

            foreach ($productData->getData() as $data) {

                $attData[$data['attribute_id']] = $data['translated_value'];

            }

            $this->_productAction->updateAttributes(array($id), $attData, $this->_jobModel->getTargetStoreId());

            foreach ($productData->getData() as $data) {

                $updateRow = $this->_attributeTranslationFactory->create()->load($data['attribute_translation_id']);

                $updateRow->addData(['is_published' => 1, 'published_at' => $this->_timezoneInterface->date()->format('y-m-d H:i:s')]);

                try {

                    $updateRow->save();

                } catch (\Exception $e) {

                    $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
                    $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                    $this->_messageManager->addError($e->getMessage());


                }

            }
        }

        return $this;
    }

    public function saveLabel($label_id, $value)
    {

        $labels = $this->_attributeTranslationCollection->create()
            ->addFieldToFilter('job_id', array('eq' => $this->_jobModel->getId()))
            ->addFieldToFilter('is_label', array('eq' => 1))
            ->addFieldToFilter('attribute_code',array('eq'=>$label_id));

        try {

            $labels->massUpdate(array('translated_value' => $value,'is_imported'=>1,'imported_at' => $this->_timezoneInterface->date()->format('y-m-d H:i:s')));

        } catch (\Exception $e) {

            $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
            $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
            $this->_messageManager->addError($e->getMessage());


        }


    }

    protected function publishTranslatedAttributeLabels($job_id)
    {

        $labels = $this->_attributeTranslationCollection->create()
            ->addFieldToSelect(['attribute_id', 'original_value', 'translated_value'])
            ->addFieldToFilter('job_id', array('eq' => $job_id))
            ->addFieldToFilter('is_label', array('eq' => 1))
            ->addFieldToFilter('translated_value', array('notnull' => true));

        $labelData = clone $labels;

        $labelData->getSelect()->group('attribute_id');

        foreach ($labelData->getData() as $data) {

            $att = $this->_attributeRepository->get(\Magento\Catalog\Model\Product::ENTITY, $data['attribute_id']);

            $new_labels = $att->getStoreLabels();

            $new_labels[$this->_jobModel->getTargetStoreId()] = $data['translated_value'];

            try {

                $att->setStoreLabels($new_labels)->save();

            } catch (\Exception $e) {

                $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
                $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                $this->_messageManager->addError($e->getMessage());


            }


        }

        foreach ($labels->getData() as $data) {

            $updateRow = $this->_attributeTranslationFactory->create()->load($data['attribute_translation_id']);

            $updateRow->addData(['is_published' => 1, 'published_at' => $this->_timezoneInterface->date()->format('y-m-d H:i:s')]);

            try {

                $updateRow->save();

            } catch (\Exception $e) {

                $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
                $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                $this->_messageManager->addError($e->getMessage());


            }
        }

        return $this;
    }

    protected function publishTranslatedOptionValues($job_id)
    {

        $this->getOptionIds($job_id);

        $translatedOptions = $this->_attributeOptionTranslationCollection->create()
            ->addFieldToSelect(['option_id', 'original_value', 'translated_value'])
            ->addFieldToFilter('attribute_translation_id', array('in' => $this->_attributeTranslationIds));

        $translatedOptionData = clone $translatedOptions;

        $translatedOptionData->getSelect()->group('option_id');

        $connection = $this->_resourceConnection->getConnection();

        $table = $this->_resourceConnection->getTableName('eav_attribute_option_value');

        if (!empty($translatedOptionData->getData())) {

            foreach ($translatedOptionData as $data) {

                $select_query = sprintf($this->_selectQuery, $table, $data['option_id'], $this->_jobModel->getTargetStoreId());

                if ($connection->fetchOne($select_query)) {

                    $update_query = sprintf($this->_updateQuery, $table, $data['translated_value'], $data['option_id'], $this->_jobModel->getTargetStoreId());

                    try{

                        $connection->query($update_query);

                    }catch (\Exception $e){

                        $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
                        $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                        $this->_messageManager->addError($e->getMessage());

                    }


                } else {

                    try{

                        $connection->insertArray($table, ['option_id', 'store_id', $table . '.value'],

                            [[$data['option_id'], $this->_jobModel->getTargetStoreId(), $data['translated_value']]]);

                    }catch (\Exception $e){

                        $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
                        $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                        $this->_messageManager->addError($e->getMessage());


                    }

                };

            }

            foreach ($translatedOptions->getData() as $data) {

                $updateRow = $this->_attributeOptionTranslationFactory->create()->load($data['attribute_option_translation_id']);

                $updateRow->addData(['is_published' => 1, 'published_at' => $this->_timezoneInterface->date()->format('y-m-d H:i:s')]);

                try{

                    $updateRow->save();

                }catch (\Exception $e){

                    $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
                    $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                    $this->_messageManager->addError($e->getMessage());

                }


            }

        }

        return $this;

    }

    protected function getProductIds($job_id)
    {
        $product_ids = $this->_attributeTranslationCollection->create()
            ->addFieldToSelect(array('entity_id'))
            ->addFieldToFilter('job_id', array('eq' => $job_id));

        $product_ids->getSelect()->group('entity_id');

        $products = $product_ids->toArray();

        $productIdArray = [];

        array_walk_recursive($products['items'], function ($value, $key) use (&$productIdArray) {
            if ($key == 'entity_id') {
                $productIdArray[] = $value;
            }
        });

        return $productIdArray;
    }

    /**
     * @param $job_id
     * @return $this
     */
    protected function getOptionIds($job_id)
    {

        //Straker Translations Translation Ids
        $translatedOptionKeys = [];

        //Find Attributes with translated Options
        $translatedAttributes = $this->_attributeTranslationCollection->create()
            ->addFieldToSelect(['attribute_id', 'original_value', 'translated_value'])
            ->addFieldToFilter('job_id', array('eq' => $job_id))
            ->addFieldToFilter('has_option', array('eq' => 1))
            ->toArray()['items'];


        //Walk over array Array to get a single array of Straker's attribute_translation id (primary key)
        array_walk_recursive($translatedAttributes, function ($value, $key) use (&$translatedOptionKeys) {

            if ($key == 'attribute_translation_id') {

                $translatedOptionKeys[] = $value;

            }
        });

        $this->_attributeTranslationIds = $translatedOptionKeys;

        return $this;

    }

    public function saveConfigLabel($attribute, $store_id)
    {
        $connection = $this->_resourceConnection->getConnection();

        $connection->insertOnDuplicate(
            $this->_labelTable,
            [
                'product_super_attribute_id' => (int)$attribute->getId(),
                'use_default' => (int)0,
                'store_id' => $store_id,
                'value' => $attribute->getLabel(),
            ],
            ['value', 'use_default']
        );

        return $this;
    }

    public function saveTranslatedCategoryData()
    {

        foreach ($this->_categoryData as $data) {

            $att_trans_model = $this->_attributeTranslationFactory->create()->load($data['_attribute']['attribute_translation_id']);

            $att_trans_model->addData(['is_imported' => 1, 'translated_value' => $data['_value']['value']]);

            try{

                $att_trans_model->save();

            }catch (\Exception $e){

                $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
                $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                $this->_messageManager->addError($e->getMessage());

            }

        }

        return $this;
    }

    public function publishTranslatedCategoryData()
    {

        $translatedCategories = $this->_attributeTranslationCollection->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('job_id', array('eq' => $this->_jobModel->getId()))->toArray();


        foreach ($translatedCategories['items'] as $data) {

            $attribute_code = $this->_attributeRepository->get(\Magento\Catalog\Model\Category::ENTITY, $data['attribute_id'])->setStoreId($this->_jobModel->getTargetStoreId())->getAttributeCode();

            $category = $this->_categoryFactory->create()->load($data['entity_id'])->setStoreId($this->_jobModel->getTargetStoreId());

            try{

                $category->setData($attribute_code, $data['translated_value'])->getResource()->saveAttribute($category, $attribute_code);


            }catch (\Exception $e){

                $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
                $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                $this->_messageManager->addError($e->getMessage());

            }
        }

        return $this;
    }

    public function saveTranslatedPageData()
    {
        foreach ($this->_pageData as $data) {

            $att_trans_model = $this->_attributeTranslationFactory->create()->load($data['_attribute']['attribute_translation_id']);

            $att_trans_model->addData(['translated_value' => $data['_value']['value']]);

            $att_trans_model->addData(['is_imported' => 1]);

            $att_trans_model->addData(['imported_at' => $this->_timezoneInterface->date()->format('y-m-d H:i:s')]);

            try{

                $att_trans_model->save();

            }catch (\Exception $e){

                $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
                $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                $this->_messageManager->addError($e->getMessage());

            }

        }

        return $this;
    }

    public function publishTranslatedPageData()
    {

        $saveData = [];

        $translatedPageAttributes = $this->_attributeTranslationCollection->create()
            ->addFieldToSelect(['attribute_id', 'translated_value', 'entity_id','attribute_code'])
            ->addFieldToFilter('job_id', array('eq' => $this->_jobModel->getId()));


        foreach ($translatedPageAttributes as $attData) {

            $saveData[$attData->getEntityId()][$attData->getAttributeCode()] = $attData->getTranslatedValue();

            $updateRow = $this->_attributeTranslationFactory->create()->load($attData->getAttributeTranslationId());

            $updateRow->addData(['is_published' => 1, 'published_at' => $this->_timezoneInterface->date()->format('y-m-d H:i:s')]);

            try{

                $updateRow->save();

            }catch (\Exception $e){

                $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
                $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                $this->_messageManager->addError($e->getMessage());

            }

        }

        foreach ($saveData as $key => $data) {

            $original_page = $this->_pageFactory->create()->load($key);

            $updatePage = $this->_urlFinder->findOneByData(
                [
                    'request_path'=>$original_page->getIdentifier(),
                    'store_id'=>$this->_jobModel->getTargetStoreId()
                ]
            );
            if ($updatePage){

//                $originalData = $original_page->getData();

//                $dbData = array_merge($originalData, $data);

                $updateData = $this->_pageFactory->create()->load($updatePage->getEntityId());

                try{
//                    $updateData->setData($dbData)->save();
                    foreach($data as $k => $v){
                        $updateData->setData($k, $v);
                    }

                    $updateData->save();

                }catch (\Exception $e){

                    $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
                    $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                    $this->_messageManager->addError($e->getMessage());

                }

            } else {

                $originalData = $original_page->getData();

                unset($originalData['page_id']);

                unset($originalData['store_id']);

                $originalData['store_id'] = [$this->_jobModel->getTargetStoreId()];

                $dbData = array_merge($originalData, $data);

                $newPage = $this->_pageFactory->create();

                try{

                    $newPage->setData($dbData)->save();

                }catch (\Exception $e){

                    $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
                    $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                    $this->_messageManager->addError($e->getMessage());

                }
            }

        }

        return $this;

    }

    public function saveTranslatedBlockData()
    {
        foreach ($this->_blockData as $data) {

            $att_trans_model = $this->_attributeTranslationFactory->create()->load($data['_attribute']['attribute_translation_id']);

            $att_trans_model->addData(['translated_value' => $data['_value']['value']]);

            $att_trans_model->addData(['is_imported' => 1]);

            $att_trans_model->addData(['imported_at' => $this->_timezoneInterface->date()->format('y-m-d H:i:s')]);

            try{

                $att_trans_model->save();

            }catch (\Exception $e){

                $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
                $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                $this->_messageManager->addError($e->getMessage());

            }


        }
        return $this;
    }

    //key key in url table
    public function publishTranslatedBlockData()
    {

        $translatedBlockAttributes = $this->_attributeTranslationCollection->create()
            ->addFieldToSelect(['attribute_translation_id', 'translated_value', 'entity_id', 'attribute_code'])
            ->addFieldToFilter('job_id', array('eq' => $this->_jobModel->getId()));

        $saveData = [];

        foreach ($translatedBlockAttributes as $attData) {

            $saveData[$attData->getEntityId()][$attData->getAttributeCode()] = $attData->getTranslatedValue();

            $updateRow = $this->_attributeTranslationFactory->create()->load($attData->getAttributeTranslationId());

            $updateRow->addData(['is_published' => 1, 'published_at' => $this->_timezoneInterface->date()->format('y-m-d H:i:s')]);

            try{

                $updateRow->save();

            }catch (\Exception $e){

                $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
                $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                $this->_messageManager->addError($e->getMessage());

            }

        }

        foreach ($saveData as $key => $data) {

            $original_block = $this->_blockFactory->create()->load($key);

            $existingBlock = $this->_blockCollection
                ->addFieldToFilter('store_id',$this->_jobModel->getTargetStoreId())
                ->addFieldToFilter('identifier', $original_block->getIdentifier());

            if (count($existingBlock->getItems()) === 1) {

                $items = $existingBlock->getItems();

                $oldBlock = reset($items);

                try{

                    foreach($data as $k => $v){
                        $oldBlock->setData($k, $v);
                    }
                    $oldBlock->save();

                }catch (\Exception $e){

                    $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
                    $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                    $this->_messageManager->addError($e->getMessage());

                }


            } else {
                //Cms blocks that link to `All default store views` (store_id: [0]) are not allowed to save with the same identifier.
                $this->unlinkDefaultStore($original_block, $this->_jobModel->getSourceStoreId());

                $originalData = $original_block->getData();
                unset($originalData['block_id']);
                unset($originalData['store_id']);
                unset($originalData['stores']);

                $originalData['stores'] = $originalData['store_id'] = [$this->_jobModel->getTargetStoreId()];

                $dbData = array_merge($originalData, $data);

                $newBlock = $this->_blockFactory->create();

                try{

                    $newBlock->setData($dbData)->save();

                }catch (\Exception $e){

                    $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
                    $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                    $this->_messageManager->addError($e->getMessage());

                }
            }

        }

        return $this;

    }

    private function unlinkDefaultStore(
        Block $originalBlock,
        $sourceStoreId
    ) {
        $stores = $originalBlock->getStores();
        if(in_array(\Magento\Store\Model\Store::DEFAULT_STORE_ID, $stores)){
            unset($stores[\Magento\Store\Model\Store::DEFAULT_STORE_ID]);
            if(!in_array($sourceStoreId, $stores)) {
                $stores[] = $sourceStoreId;
            }
            $originalBlock->setStores($stores);
            $originalBlock->save()->load($originalBlock->getId());
        }
    }
}
