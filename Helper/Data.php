<?php

namespace Straker\EasyTranslationPlatform\Helper;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var UrlInterface
     */
    protected $_backendUrl;

    protected $_magentoDataTables = [
        'catalog_product_entity_varchar',
        'catalog_product_entity_text',
        'catalog_category_entity_varchar',
        'catalog_category_entity_text',
        'eav_attribute_option_value',
        'catalog_product_super_attribute_label',
        'cms_page',
        'cms_page_store',
        'cms_block',
        'cms_block_store',
        'url_rewrite',
        'catalog_url_rewrite_product_category'
    ];

    protected $_sandboxLanguages = [
            ['native_name' => 'Arabic','code' => 'Arabic','name' => 'Arabic','short_code' => 'sa'],
            ['native_name' => 'Chinese Simplified','code' => 'Chinese_Simplified','name' => 'Chinese Simplified','short_code' => 'zh-cn'],
            ['native_name' => 'English ( USA )','code' => 'English_US','name' => 'English (USA)','short_code' => 'en-us'],
            ['native_name' => 'French ( France )','code' => 'French','name' => 'French (France)','short_code' => 'fr-fr'],
            ['native_name' => 'German','code' => 'German','name' => 'German','short_code' => 'de-de'],
            ['native_name' => 'Japanese','code' => 'Japanese','name' => 'Japanese','short_code' => 'ja'],
            ['native_name' => 'Spanish ( Spain )','code' => 'Spanish','name' => 'Spanish (Spain)','short_code' => 'es-es']
    ];

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    const BACKUP_TABLE_SUFFIX = '_back';


    /**
     * @param Context $context
     * @param UrlInterface $backendUrl
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        UrlInterface $backendUrl,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->_backendUrl = $backendUrl;
        $this->storeManager = $storeManager;
    }

    /**
     * get products tab Url in admin
     * @return string
     */
    public function getProductUrl()
    {
        return $this->_backendUrl->getUrl('EasyTranslationPlatform/Jobs/products', ['_current' => true]);
    }

    public function getPagesUrl()
    {
        return $this->_backendUrl->getUrl('EasyTranslationPlatform/Jobs/pages', ['_current' => true]);
    }

    public function getBlockUrl()
    {
        return $this->_backendUrl->getUrl('EasyTranslationPlatform/Jobs/Blocks', ['_current' => true]);
    }

    public function getMagentoDataTableArray()
    {
        return $this->_magentoDataTables;
    }

    public function getBackupTableNames($tableName)
    {
        return $tableName . self::BACKUP_TABLE_SUFFIX;
    }

    public function getUrl($path = '/', $parameters = [])
    {
        return $this->_backendUrl->getUrl($path, $parameters);
    }

    public function getSandboxLanguages()
    {
        return json_decode(json_encode($this->_sandboxLanguages));
    }
}
