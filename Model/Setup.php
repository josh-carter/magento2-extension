<?php

namespace Straker\EasyTranslationPlatform\Model;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreFactory;
use Straker\EasyTranslationPlatform\Api\Data\SetupInterface;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use \Straker\EasyTranslationPlatform\Helper\Data;
use Magento\Config\Model\ResourceModel\Config;

class Setup extends AbstractModel implements SetupInterface
{
    protected $_configModel;
    protected $_resourceConnection;
    protected $_dataHelper;
    protected $_storeFactory;
    protected $_storeManager;
    protected $_configHelper;

    public function __construct(
        Context $context,
        Registry $registry,
        Config $config,
        ResourceConnection $resourceConnection,
        Data $dataHelper,
        StoreFactory $storeFactory,
        StoreManagerInterface $storeManager,
        ConfigHelper $configHelper
    ) {
        $this->_configModel = $config;
        $this->_resourceConnection = $resourceConnection;
        $this->_dataHelper = $dataHelper;
        $this->_storeFactory = $storeFactory;
        $this->_storeManager = $storeManager;
        $this->_configHelper = $configHelper;
        parent::__construct($context, $registry);
    }

    public function saveClientData($data)
    {
        $this->_configModel->saveConfig(
            'straker/general/name',
            $data['first_name'] . ' ' . $data['last_name'],
            'default',
            0
        );
        $this->_configModel->saveConfig('straker/general/first_name', $data['first_name'], 'default', 0);
        $this->_configModel->saveConfig('straker/general/last_name', $data['last_name'], 'default', 0);
        $this->_configModel->saveConfig('straker/general/email', $data['email'], 'default', 0);

        if (!empty($data['country'])) {
            $this->_configModel->saveConfig('straker/general/country', $data['country'], 'default', 0);
        }

        if (!empty($data['company_name'])) {
            $this->_configModel->saveConfig('straker/general/company_name', $data['company_name'], 'default', 0);
        }

        if (!empty($data['company_size'])) {
            $this->_configModel->saveConfig('straker/general/company_size', $data['company_size'], 'default', 0);
        }

        if (!empty($data['phone_number'])) {
            $this->_configModel->saveConfig('straker/general/phone_number', $data['phone_number'], 'default', 0);
        }

        if (!empty($data['url'])) {
            $this->_configModel->saveConfig('straker/general/url', $data['url'], 'default', 0);
        }

        return $this->_configModel;
    }

    public function saveAppKey($appKey)
    {

        $this->_configModel->saveConfig('straker/general/application_key', $appKey, 'default', 0);

        return $this->_configModel;
    }

    public function saveAccessToken($accessToken)
    {

        $this->_configModel->saveConfig('straker/general/access_token', $accessToken, 'default', 0);

        return $this->_configModel;
    }

    public function saveStoreSetup($scopeId, $source_store = '', $source_language = '', $destination_language = '')
    {

        $this->_configModel->saveConfig(
            'straker/general/source_store',
            $source_store,
            ScopeInterface::SCOPE_STORES,
            $scopeId
        );
        $this->_configModel->saveConfig(
            'straker/general/source_language',
            $source_language,
            ScopeInterface::SCOPE_STORES,
            $scopeId
        );
        $this->_configModel->saveConfig(
            'straker/general/destination_language',
            $destination_language,
            ScopeInterface::SCOPE_STORES,
            $scopeId
        );

        return $this->_configModel;
    }

    public function saveAttributes($attributes)
    {

        if (!empty($attributes['custom'])) {
            $this->_configModel->saveConfig(
                'straker_config/attribute/product_custom',
                $attributes['custom'],
                'default'
            );
        }

        if (!empty($attributes['default'])) {
            $this->_configModel->saveConfig(
                'straker_config/attribute/product_default',
                $attributes['default'],
                'default'
            );
        }

        if (!empty($attributes['category'])) {
            $this->_configModel->saveConfig('straker_config/attribute/category', $attributes['category'], 'default');
        }

        $this->_cacheManager->clean(\Magento\Framework\App\Cache\Type\Config::CACHE_TAG);
        return $this->_configModel;
    }

    //phpcs:disable
    public function clearTranslations($storeId = null)
    {
        $result = ['Success' => false, 'Message' => '', 'Count' => 0];
        $deleteCount = 0;
        $connection = $this->_getConnection();
        try {
            $connection->beginTransaction();
            foreach ($this->_dataHelper->getMagentoDataTableArray() as $rawTableName) {
                $table = $this->_resourceConnection->getTableName($rawTableName);

                if (strcasecmp($rawTableName, 'cms_page') === 0 ||
                    strcasecmp($rawTableName, 'cms_block') === 0 ||
                    strcasecmp($rawTableName, 'catalog_url_rewrite_product_category') === 0) {
                    continue;
                }

                if ($connection->isTableExists($table)) {
                    if (strcasecmp($rawTableName, 'cms_page_store') === 0
                        || strcasecmp($rawTableName, 'cms_block_store') === 0
                    ) {
                        $idField = ( strcasecmp($rawTableName, 'cms_page_store') === 0 ) ? 'page_id' : 'block_id';

                        $select = $connection
                            ->select()
                            ->from($table, [ $idField ])
                            ->where('store_id != ?', Store::DEFAULT_STORE_ID);
                        $return = $select->query()->fetchAll();
                        $ids = array_column($return, $idField);

                        $rawTargetTable = strcasecmp($idField, 'page_id') === 0 ? 'cms_page' : 'cms_block';
                        $targetTable = $this->_resourceConnection->getTableName($rawTargetTable);

                        if ($connection->isTableExists($targetTable)) {
                            $where = [$idField. ' IN(?)' => $ids ];
                            $deleteCount = $connection->delete($targetTable, $where);
                        }
                    }

                    if ($storeId === null) {
                        //CLEAR FOR ALL STORES
                        if (strcasecmp($rawTableName, 'url_rewrite') === 0) {
                            $select = $connection->select()
                                ->from($table, ['url_rewrite_id'])
                                ->where('store_id != ?', 1);
                            $urlRewriteIds = [];
                            $return = $select->query()->fetchAll();
                            if (count($return) > 0) {
                                $urlRewriteIds = array_column($return, 'url_rewrite_id');
                            }
                            $where = [ 'store_id != ?' => 1];
                            $deleteCount += $connection->delete($table, $where);
                            $urlRewriteProductCategoryTable = $this->_resourceConnection
                                ->getTableName('catalog_url_rewrite_product_category');
                            if ($connection->isTableExists($urlRewriteProductCategoryTable)
                                && count($urlRewriteIds) > 0
                            ) {
                                $where = ['url_rewrite_id IN(?)'=> $urlRewriteIds ];
                                $deleteCount += $connection->delete($urlRewriteProductCategoryTable, $where);
                            }
                        } else {
                            $where = ['store_id != ?' => Store::DEFAULT_STORE_ID ];
                            $deleteCount += $connection->delete($table, $where);
                        }
                    } else {
                        //CLEAR FOR A SINGLE STORE
                        $where = ['store_id = ?' => $storeId ];
                        $deleteCount += $connection->delete($table, $where);
                    }
                }
            }
            $result['Success'] = true;
            $result['Count'] = $deleteCount;
            $connection->commit();
        } catch (Exception $e) {
            $connection->rollBack();
            $result['Message'] = $e->getMessage();
            throw new Exception($result['Message']);
        }

        return $result;
    }
    //phpcs:enable

    public function clearStrakerData()
    {
        $tables = [
            'straker_attribute_option_translation',
            'straker_attribute_translation',
            'straker_job'
        ];

        $result = ['Success' => false, 'Message' => '', 'Count' => 0];
        $deleteCount = 0;
        $connection = $this->_getConnection();

        try {
            $connection->beginTransaction();
            foreach ($tables as $table) {
                $table = $this->_resourceConnection->getTableName($table);
                if ($connection->isTableExists($table)) {
                    $deleteCount = $connection->delete($table);
                    $deleteCount++;
                }
            }

            $this->clearDefaultAttributeSettings();
            $result['Success'] = true;
            $result['Count'] = $deleteCount;
            $connection->commit();
        } catch (Exception $e) {
            $connection->rollBack();
            $result['Message'] = $e->getMessage();
            //phpcs:disable
            throw new Exception($result['Message']);
            //phpcs:enable
        }

        return $result;
    }

    protected function clearDefaultAttributeSettings()
    {

        $this->_configModel->saveConfig('straker_config/attribute/product_custom', '', 'default', 0);
        $this->_configModel->saveConfig('straker_config/attribute/product_default', '', 'default', 0);
        $this->_configModel->saveConfig('straker_config/attribute/category', '', 'default', 0);

        return  $this->_configModel;
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function _getConnection()
    {
        return $this->_resourceConnection->getConnection();
    }

    public function deleteSandboxSetting()
    {
        $this->_configModel->deleteConfig('straker_config/env/site_mode', 'default', 0);
        return  $this->_configModel;
    }

    /**
     * @param int $mode 0: sandbox, 1: live
     */
    public function setSiteMode($mode = 0)
    {
        $this->_configModel->saveConfig('straker_config/env/site_mode', $mode, 'default', 0);
        $this->_clean();
    }

    private function _clean()
    {
        $this->_cacheManager->clean(\Magento\Framework\App\Cache\Type\Config::CACHE_TAG);
    }

    public function isTestingStoreViewExist()
    {
        $testingStore = $this->_storeFactory->create()->load($this->_configHelper->getTestingStoreViewCode());
        return $testingStore;
    }

    public function deleteTestingStoreView($siteMode = SetupInterface::SITE_MODE_LIVE)
    {
        $result = ['Success' => true, 'Message' => '', 'SiteMode' => SetupInterface::SITE_MODE_SANDBOX];
        try {
            $testingStore = $this->isTestingStoreViewExist();
            if ($testingStore->getId()) {
                $testingStore->delete();
                $this->_eventManager->dispatch('store_delete', ['store' => $testingStore]);
                //switch site mode
                if ($siteMode == SetupInterface::SITE_MODE_LIVE) {
                    if ($this->_configHelper->isSandboxMode()) {
                        $this->setSiteMode(SetupInterface::SITE_MODE_LIVE);
                    }
                    $result['SiteMode'] = SetupInterface::SITE_MODE_LIVE;
                } else {
                    if (!$this->_configHelper->isSandboxMode()) {
                        $this->setSiteMode(SetupInterface::SITE_MODE_SANDBOX);
                    }
                }
            } else {
                $result['Success'] = false;
                $result['Message'] = __('The testing store does not exist.');
            }
        } catch (Exception $e) {
            throw $e;
        }
        return $result;
    }

    public function createTestingStoreView($storeName = '', $siteMode = SetupInterface::SITE_MODE_SANDBOX)
    {
        $result = ['Success' => true, 'Message' => '', 'SiteMode' => SetupInterface::SITE_MODE_LIVE];
        try {
            $testingStore = $this->isTestingStoreViewExist();
            if ($testingStore->getId()) {
                $result['Success'] = false;
                $result['Message'] = __('The testing store exist.');
            } else {
                if (!empty(trim($storeName))) {
                    //create a store view
                    $testingStore->setName($storeName);
                    $testingStore->setId(null);
                    $testingStore->setIsActive(true);
                    $testingStore->setCode($this->_configHelper->getTestingStoreViewCode());
                    $currentWebsite = $this->_storeManager->getWebsite();
                    $defaultGroupId = $currentWebsite->getDefaultGroupId();
                    $testingStore->setStoreGroupId($defaultGroupId);
                    $testingStore->setWebsiteId($currentWebsite->getId());
                    $testingStore->save();
                    $this->_storeManager->reinitStores();
                    $this->_eventManager->dispatch('store_add', ['store' => $testingStore]);
                    //switch site mode
                    if ($siteMode == SetupInterface::SITE_MODE_SANDBOX) {
                        if (!$this->_configHelper->isSandboxMode()) {
                            $this->setSiteMode(SetupInterface::SITE_MODE_SANDBOX);
                        }
                        $result['SiteMode'] = SetupInterface::SITE_MODE_SANDBOX;
                    } else {
                        if ($this->_configHelper->isSandboxMode()) {
                            $this->setSiteMode(SetupInterface::SITE_MODE_LIVE);
                        }
                    }
                    return $result;
                }
                $result['Success'] = false;
                $result['Message'] = __('The testing store name is invalid.');
            }
        } catch (\Exception $e) {
            $result['Success'] = false;
            $result['Message'] = __('There was an error registering your details');
        }
        return $result;
    }
}
