<?php

namespace Straker\EasyTranslationPlatform\Helper;

use Magento\Cms\Model\Block;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Xml\Parser;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

use Magento\Framework\Message\ManagerInterface;

use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory as OptionCollection;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Cms\Model\PageFactory as PageFactory;
use Magento\Cms\Model\BlockFactory as BlockFactory;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use Straker\EasyTranslationPlatform\Logger\Logger;

use Straker\EasyTranslationPlatform\Model\BlockCollection;

use Straker\EasyTranslationPlatform\Model\Job;
use Straker\EasyTranslationPlatform\Model\JobFactory;
use Straker\EasyTranslationPlatform\Model\AttributeTranslationFactory;
use Straker\EasyTranslationPlatform\Model\AttributeOptionTranslationFactory;
use Straker\EasyTranslationPlatform\Model\ResourceModel\AttributeTranslation\CollectionFactory
    as AttributeTranslationCollection;
use Straker\EasyTranslationPlatform\Model\ResourceModel\AttributeOptionTranslation\CollectionFactory
    as AttributeOptionTranslationCollection;

class ImportHelper extends AbstractHelper
{
    /** @var $configHelper ConfigHelper */
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
    protected $_attributeTranslationIds;
    protected $_saveOptionIds = [];

    protected $_productData;
    protected $_categoryData;
    protected $_timezoneInterface;

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
    ) {
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

    public function create($job_id): ImportHelper
    {
        $this->_jobModel = $this->_jobFactory->create()->load($job_id);
        return $this;
    }

    public function parseTranslatedFile(): ImportHelper
    {
        try {
            $filePath = $this->configHelper->getTranslatedXMLFilePath()
                . DIRECTORY_SEPARATOR
                . $this->_jobModel->getData('translated_file');

            $parsedData = $this->_xmlParser->load($filePath)->xmlToArray();

            if (isset($parsedData['root']['data'])) {
                $dataArray = $parsedData['root']['data'];
            } elseif (isset($parsedData['root']['_value']['data'])) {
                $dataArray = $parsedData['root']['_value']['data'];
            } else {
                $dataArray = [];
            }

            if (key_exists('_value', $dataArray)) {
                $this->_parsedFileData[0] = $dataArray;
            } else {
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
        } catch (\Exception $e) {
            $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), [$e]);
            $this->_strakerApi->_callStrakerBugLog(
                __FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(),
                $e->__toString()
            );
            $this->_messageManager->addErrorMessage($e->getMessage());
        }
    }

    public function saveData(): ImportHelper
    {
        if (!empty($this->_productData)) {
            $this->saveTranslatedProductData();
        }
        if (!empty($this->_categoryData)) {
            $this->saveTranslatedCategoryData();
        }

        if (!empty($this->_pageData)) {
            $this->saveTranslatedCmsData('cms_page');
        }

        if (!empty($this->_blockData)) {
            $this->saveTranslatedCmsData('cms_block');
        }

        return $this;
    }

    public function publishTranslatedData(): ImportHelper
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

    public function saveTranslatedProductData(): ImportHelper
    {
        $this->getOptionIds($this->_jobModel->getId());

        foreach ($this->_productData as $data) {
            if (array_key_exists('attribute_translation_id', $data['_attribute'])) {
                try {
                    $attTransModel = $this->_attributeTranslationFactory
                        ->create()
                        ->load($data['_attribute']['attribute_translation_id']);

                    $attTransModel->addData([
                        'translated_value' => $data['_value']['value'],
                        'is_imported' => 1,
                        'imported_at' => $this->_timezoneInterface->date()->format('y-m-d H:i:s')
                    ]);

                    $attTransModel->save();

                    ($data['_attribute']['is_label'] == 1)
                        ? $this->saveLabel($data['_attribute']['attribute_code'], $data['_value']['value'])
                        : false;
                } catch (\Exception $e) {
                    $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), [$e]);
                    $this->_strakerApi->_callStrakerBugLog(
                        __FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(),
                        $e->__toString()
                    );
                    $this->_messageManager->addErrorMessage($e->getMessage());
                }
            }

            if (array_key_exists('option_translation_id', $data['_attribute'])) {
                try {
                    $attOptModel = $this->_attributeOptionTranslationFactory->create()
                        ->load($data['_attribute']['option_translation_id']);

                    $attOptModel->addData([
                        'translated_value' => $data['_value']['value'],
                        'is_imported' => 1,
                        'imported_at' => $this->_timezoneInterface->date()->format('y-m-d H:i:s')
                    ]);

                    $attOptModel->save();

                    if (!in_array($attOptModel->getData('option_id'), $this->_saveOptionIds)) {
                        $translatedOptions = $this->_attributeOptionTranslationCollection->create()
                            ->addFieldToSelect(['option_id', 'translated_value'])
                            ->addFieldToFilter('attribute_translation_id', ['in' => $this->_attributeTranslationIds])
                            ->addFieldToFilter('option_id', ['eq' => $attOptModel->getData('option_id')]);

                        $translatedOptions->massUpdate([
                            'translated_value' => $attOptModel->getData('translated_value'),
                            'is_imported' => 1,
                            'imported_at' => $this->_timezoneInterface->date()->format('y-m-d H:i:s')
                        ]);

                        $this->_saveOptionIds[] = $attOptModel->getData('option_id');
                    }
                } catch (\Exception $e) {
                    $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), [$e]);
                    $this->_strakerApi->_callStrakerBugLog(
                        __FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(),
                        $e->__toString()
                    );
                    $this->_messageManager->addErrorMessage($e->getMessage());
                }
            }
        }

        return $this;
    }

    public function publishTranslatedProductData(): ImportHelper
    {
        $product_ids = $this->getProductIds($this->_jobModel->getId());
        $this->publishTranslatedOptionValues($this->_jobModel->getId());
        $this->publishTranslatedAttributeLabels($this->_jobModel->getId());

        foreach ($product_ids as $id) {
            $productAttributeCollection = $this->_attributeTranslationCollection->create()
                ->addFieldToSelect(['attribute_id', 'original_value', 'translated_value'])
                ->addFieldToFilter('job_id', ['eq' => $this->_jobModel->getId()])
                ->addFieldToFilter('entity_id', ['eq' => $id])
                ->addFieldToFilter('is_label', ['eq' => 0]);

            $attData = [];

            foreach ($productAttributeCollection->getData() as $data) {
                $attData[$data['attribute_id']] = $data['translated_value'];
            }

            $this->_productAction->updateAttributes([$id], $attData, $this->_jobModel->getTargetStoreId());
            $this->updateAttributeTranslationRow($productAttributeCollection->getData());
        }

        return $this;
    }

    public function saveLabel($labelId, $value)
    {
        $labels = $this->_attributeTranslationCollection->create()
            ->addFieldToFilter('job_id', ['eq' => $this->_jobModel->getId()])
            ->addFieldToFilter('is_label', ['eq' => 1])
            ->addFieldToFilter('attribute_code', ['eq' => $labelId]);

        try {
            $labels->massUpdate([
                'translated_value' => $value,
                'is_imported'=>1,
                'imported_at' => $this->_timezoneInterface->date()->format('y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), [$e]);
            $this->_strakerApi->_callStrakerBugLog(
                __FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(),
                $e->__toString()
            );
            $this->_messageManager->addErrorMessage($e->getMessage());
        }
    }

    protected function publishTranslatedAttributeLabels($jobId): ImportHelper
    {
        $labels = $this->_attributeTranslationCollection->create()
            ->addFieldToSelect(['attribute_id', 'original_value', 'translated_value'])
            ->addFieldToFilter('job_id', ['eq' => $jobId])
            ->addFieldToFilter('is_label', ['eq' => 1])
            ->addFieldToFilter('translated_value', ['notnull' => true]);

        $labelData = clone $labels;

        $labelData->getSelect()->group('attribute_id');

        foreach ($labelData->getData() as $data) {
            $attr = $this->_attributeRepository->get(\Magento\Catalog\Model\Product::ENTITY, $data['attribute_id']);
            $new_labels = $attr->getStoreLabels();
            $new_labels[$this->_jobModel->getTargetStoreId()] = $data['translated_value'];

            try {
                $attr->setStoreLabels($new_labels)->save();
            } catch (\Exception $e) {
                $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), [$e]);
                $this->_strakerApi->_callStrakerBugLog(
                    __FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(),
                    $e->__toString()
                );
                $this->_messageManager->addErrorMessage($e->getMessage());
            }
        }

        $this->updateAttributeTranslationRow($labels->getData());
        return $this;
    }

    protected function publishTranslatedOptionValues($jobId): ImportHelper
    {
        $this->getOptionIds($jobId);

        $translatedOptions = $this->_attributeOptionTranslationCollection->create()
            ->addFieldToSelect(['option_id', 'original_value', 'translated_value'])
            ->addFieldToFilter('attribute_translation_id', ['in' => $this->_attributeTranslationIds]);

        $translatedOptionData = clone $translatedOptions;
        $translatedOptionData->getSelect()->group('option_id');
        $connection = $this->_resourceConnection->getConnection();
        $table = $this->_resourceConnection->getTableName('eav_attribute_option_value');

        if (!empty($translatedOptionData->getData())) {
            foreach ($translatedOptionData as $data) {
                $select = $connection->select()
                    ->from($table)
                    ->where(
                        'option_id = ' . $data['option_id'] . ' AND store_id = ' . $this->_jobModel->getTargetStoreId()
                    )->columns(['option_id']);

                if ($connection->fetchOne($select)) {
                    $connection->update(
                        $table,
                        ['value' => $data['translated_value']],
                        ['option_id' => $data['option_id'], 'store_id' => $this->_jobModel->getTargetStoreId()]
                    );
                } else {
                    try {
                        $connection->insertArray(
                            $table,
                            ['option_id', 'store_id', $table . '.value'],
                            [[$data['option_id'], $this->_jobModel->getTargetStoreId(), $data['translated_value']]]
                        );
                    } catch (\Exception $e) {
                        $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), [$e]);
                        $this->_strakerApi->_callStrakerBugLog(
                            __FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(),
                            $e->__toString()
                        );
                        $this->_messageManager->addErrorMessage($e->getMessage());
                    }
                }
            }

            foreach ($translatedOptions->getData() as $data) {
                $updateRow = $this->_attributeOptionTranslationFactory->create()
                    ->load($data['attribute_option_translation_id']);
                $this->updateRow($updateRow);
            }
        }

        return $this;
    }

    protected function getProductIds($jobId): array
    {
        $productIds = $this->_attributeTranslationCollection->create()
            ->addFieldToSelect(['entity_id'])
            ->addFieldToFilter('job_id', ['eq' => $jobId]);

        $productIds->getSelect()->group('entity_id');
        $products = $productIds->toArray();
        $productIdArray = [];

        array_walk_recursive($products['items'], function ($value, $key) use (&$productIdArray) {
            if ($key == 'entity_id') {
                $productIdArray[] = $value;
            }
        });

        return $productIdArray;
    }

    /**
     * @param $jobId
     * @return $this
     */
    protected function getOptionIds($jobId): ImportHelper
    {
        //Straker Translations Translation Ids
        $translatedOptionKeys = [];

        //Find Attributes with translated Options
        $translatedAttributes = $this->_attributeTranslationCollection->create()
            ->addFieldToSelect(['attribute_id', 'original_value', 'translated_value'])
            ->addFieldToFilter('job_id', ['eq' => $jobId])
            ->addFieldToFilter('has_option', ['eq' => 1])
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

    public function saveTranslatedCategoryData()
    {
        foreach ($this->_categoryData as $data) {
            $attTransModel = $this->_attributeTranslationFactory->create()
                ->load($data['_attribute']['attribute_translation_id']);
            $attTransModel->addData([
                'is_imported' => 1,
                'translated_value' => $data['_value']['value'],
                'imported_at' => $this->_timezoneInterface->date()->format('y-m-d H:i:s')
            ]);
            $this->updateRow($attTransModel);
        }

        return $this;
    }

    public function publishTranslatedCategoryData(): ImportHelper
    {
        $translatedCategories = $this->_attributeTranslationCollection->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('job_id', ['eq' => $this->_jobModel->getId()])->toArray();

        foreach ($translatedCategories['items'] as $data) {
            $attributeCode = $this->_attributeRepository
                ->get(
                    \Magento\Catalog\Model\Category::ENTITY,
                    $data['attribute_id']
                )->setStoreId($this->_jobModel->getTargetStoreId())
                ->getAttributeCode();

            $category = $this->_categoryFactory->create()
                ->load($data['entity_id'])
                ->setStoreId($this->_jobModel->getTargetStoreId());

            try {
                $category
                    ->setData(
                        $attributeCode,
                        $data['translated_value']
                    )
                    ->getResource()
                    ->saveAttribute($category, $attributeCode);
            } catch (\Exception $e) {
                $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), [$e]);
                $this->_strakerApi->_callStrakerBugLog(
                    __FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(),
                    $e->__toString()
                );
                $this->_messageManager->addErrorMessage($e->getMessage());
            }
        }

        return $this;
    }

    public function saveTranslatedCmsData($type): ImportHelper
    {
        $cmsData = $type === 'cms_page' ? $this->_pageData : $this->_blockData;

        foreach ($cmsData as $data) {
            $attTransModel = $this->_attributeTranslationFactory->create()
                ->load($data['_attribute']['attribute_translation_id']);
            $attTransModel->addData(['translated_value' => $data['_value']['value']]);
            $attTransModel->addData(['is_imported' => 1]);
            $attTransModel->addData(['imported_at' => $this->_timezoneInterface->date()->format('y-m-d H:i:s')]);

            $this->updateRow($attTransModel);
        }

        return $this;
    }

    public function publishTranslatedPageData(): ImportHelper
    {
        $translatedPageAttributes = $this->_attributeTranslationCollection->create()
            ->addFieldToSelect(['attribute_id', 'translated_value', 'entity_id','attribute_code'])
            ->addFieldToFilter('job_id', ['eq' => $this->_jobModel->getId()]);

        $saveData = $this->updateAttributeTranslationRow($translatedPageAttributes->getData());

        foreach ($saveData as $key => $data) {
            $originalPage = $this->_pageFactory->create()->load($key);
            $updatePage = $this->_urlFinder->findOneByData([
                'request_path' => $originalPage->getIdentifier(),
                'store_id' => $this->_jobModel->getTargetStoreId()
            ]);

            if ($updatePage) {
                $pageModel = $this->_pageFactory->create()->load($updatePage->getEntityId());
                $this->publishCmsContent($data, $pageModel);
            } else {
                $originalData = $originalPage->getData();
                unset($originalData['page_id']);
                unset($originalData['store_id']);

                $originalData['store_id'] = [$this->_jobModel->getTargetStoreId()];

                $this->publishNewCmsContent(
                    Job::PAGE_ATTRIBUTES,
                    $data,
                    $originalData,
                    $this->_pageFactory->create()
                );
            }
        }

        return $this;
    }

    //key key in url table
    public function publishTranslatedBlockData(): ImportHelper
    {
        $translatedBlockAttributes = $this->_attributeTranslationCollection->create()
            ->addFieldToSelect(['attribute_translation_id', 'translated_value', 'entity_id', 'attribute_code'])
            ->addFieldToFilter('job_id', ['eq' => $this->_jobModel->getId()]);

        $saveData = $this->updateAttributeTranslationRow($translatedBlockAttributes->getData());

        foreach ($saveData as $key => $data) {
            $originalBlock = $this->_blockFactory->create()->load($key);

            $existingBlock = $this->_blockCollection
                ->addFieldToFilter('store_id', $this->_jobModel->getTargetStoreId())
                ->addFieldToFilter('identifier', $originalBlock->getIdentifier());

            if (count($existingBlock->getItems()) === 1) {
                $items = $existingBlock->getItems();
                $oldBlock = reset($items);
                $this->publishCmsContent($data, $oldBlock);
            } else {
                //Cms blocks that link to `All default store views` (store_id: [0])
                // are not allowed to save with the same identifier.
                $this->unlinkDefaultStore($originalBlock, $this->_jobModel->getSourceStoreId());

                $originalData = $originalBlock->getData();
                unset($originalData['block_id']);
                unset($originalData['store_id']);
                unset($originalData['stores']);

                $originalData['stores'] = $originalData['store_id'] = [$this->_jobModel->getTargetStoreId()];

                $this->publishNewCmsContent(
                    Job::BLOCK_ATTRIBUTES,
                    $data,
                    $originalData,
                    $this->_blockFactory->create()
                );
            }
        }
        return $this;
    }

    private function unlinkDefaultStore(
        Block $originalBlock,
        $sourceStoreId
    ) {
        $stores = $originalBlock->getStores();
        if (in_array(\Magento\Store\Model\Store::DEFAULT_STORE_ID, $stores)) {
            unset($stores[\Magento\Store\Model\Store::DEFAULT_STORE_ID]);
            if (!in_array($sourceStoreId, $stores)) {
                $stores[] = $sourceStoreId;
            }
            $originalBlock->setStores($stores);
            $originalBlock->save()->load($originalBlock->getId());
        }
    }

    /**
     * @param array $attributeData
     * @return array
     */
    private function updateAttributeTranslationRow(
        array $attributeData
    ): array {
        $saveData = [];

        foreach ($attributeData as $data) {
            if (key_exists('attribute_code', $data)
                && key_exists('entity_id', $data)
                && key_exists('translated_value', $data)
            ) {
                $saveData[$data['entity_id']][$data['attribute_code']]
                    = $data['translated_value'];
            }

            $updateRow = $this->_attributeTranslationFactory->create()->load($data['attribute_translation_id']);

            $this->updateRow($updateRow);
        }

        return $saveData;
    }

    /**
     * @param AbstractModel $updateRow
     */
    protected function updateRow(AbstractModel $updateRow): void
    {
        $updateRow->addData([
            'is_published' => 1,
            'published_at' => $this->_timezoneInterface->date()->format('y-m-d H:i:s')
        ]);

        try {
            $updateRow->save();
        } catch (\Exception $e) {
            $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), [$e]);
            $this->_strakerApi->_callStrakerBugLog(
                __FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(),
                $e->__toString()
            );
            $this->_messageManager->addErrorMessage($e->getMessage());
        }
    }

    /**
     * @param $data
     * @param AbstractModel|DataObject $dataModel
     */
    protected function publishCmsContent(
        $data,
        AbstractModel $dataModel
    ) {
        try {
            foreach ($data as $k => $v) {
                $dataModel->setData($k, $v);
            }
            $dataModel->save();
        } catch (\Exception $e) {
            $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), [$e]);
            $this->_strakerApi->_callStrakerBugLog(
                __FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(),
                $e->__toString()
            );
            $this->_messageManager->addErrorMessage($e->getMessage());
        }
    }

    /**
     * @param array $attributes
     * @param array $translatedData
     * @param array $originalData
     * @param AbstractModel $newModel
     * @return array
     */
    protected function publishNewCmsContent(
        array $attributes,
        array $translatedData,
        array $originalData,
        AbstractModel $newModel
    ): array {
        foreach ($attributes as $attribute) {
            if (isset($translatedData[$attribute])) {
                $originalData[$attribute] = $translatedData[$attribute];
            }
        }

        try {
            $newModel->setData($originalData)->save();
        } catch (\Exception $e) {
            $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), [$e]);
            $this->_strakerApi->_callStrakerBugLog(
                __FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(),
                $e->__toString()
            );
            $this->_messageManager->addErrorMessage($e->getMessage());
        }
        return [$translatedData, $originalData];
    }
}
