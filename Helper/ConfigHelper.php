<?php

namespace Straker\EasyTranslationPlatform\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\UrlFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\ResourceModel\Config\Collection\ScopedFactory;

class ConfigHelper extends AbstractHelper
{
    protected $_scopeFactory;
    protected $_directoryList;
    protected $_urlFactory;
    protected $_productMetadata;
    protected $_deployConfig;
    protected $_moduleList;

    /**
     * ConfigHelper constructor.
     * @param Context $context
     * @param ScopedFactory $scopedFactory
     * @param UrlFactory $urlFactory
     * @param DirectoryList $directoryList
     * @param ProductMetadataInterface $productMetadata
     * @param DeploymentConfig $deployConfig
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        Context $context,
        ScopedFactory $scopedFactory,
        UrlFactory $urlFactory,
        DirectoryList $directoryList,
        ProductMetadataInterface $productMetadata,
        DeploymentConfig $deployConfig,
        ModuleListInterface $moduleList
    ) {

        $this->_scopeFactory = $scopedFactory;
        $this->_directoryList = $directoryList;
        $this->_urlFactory = $urlFactory;
        $this->_productMetadata = $productMetadata;
        $this->_deployConfig = $deployConfig;
        $this->_moduleList = $moduleList;
        parent::__construct($context);
    }

    public function getConfig($req)
    {
        return $this->scopeConfig->getValue('straker' . $req);
    }

    public function getAccessToken()
    {
        return $this->scopeConfig->getValue(
            'straker/general/access_token',
            'default',
            ''
        ) ? $this->scopeConfig->getValue(
            'straker/general/access_token',
            'default',
            ''
        ) : false ;
    }

    public function getApplicationKey()
    {
        return $this->scopeConfig->getValue(
            'straker/general/application_key',
            'default',
            ''
        ) ? $this->scopeConfig->getValue(
            'straker/general/application_key',
            'default',
            ''
        ) : false ;
    }

    /**
     * @return string or null  current version of the website
     * hard-coded in config.xml (value would be uat, dev, live ...)
     */
    public function getVersion()
    {
        return $this->scopeConfig->getValue('straker/general/version');
    }

    public function getModuleVersion()
    {
        $moduleName = $this->_getModuleName();
        if (empty($moduleName)) {
            $moduleName = 'Straker_EasyTranslationPlatform';
        }
        $moduleInfoArray = $this->_moduleList->getOne($moduleName);
        $version = '';
        if (array_key_exists('setup_version', $moduleInfoArray)) {
            $version = $moduleInfoArray['setup_version'];
        }
        return trim($version);
    }

    public function getEnv()
    {
        $moduleInfoArray = $this->_moduleList->getAll();

        $env = [];

        if ($moduleInfoArray) {
            $env['active_plugins'] = $moduleInfoArray;
        }
        //phpcs:disable
        $env['server_information']['php_version']       = phpversion();
        $env['server_information']['server_protocol']   = empty($_SERVER['SERVER_PROTOCOL']) ? '' : $_SERVER['SERVER_PROTOCOL'];
        $env['server_information']['user_agent']        = empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'];
        $env['server_information']['web_server']        = empty($_SERVER['SERVER_SOFTWARE']) ? '' : $_SERVER['SERVER_SOFTWARE'];
        $env['server_information']['server_host']       = empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        $env['server_information']['https']             = isset($_SERVER['HTTPS']);
        $env['server_information']['app_name']          = 'magento2';
        //phpcs:enable
        $magentoVersion = $this->getMagentoVersion();
        $env['server_information']['app_version']       = empty($magentoVersion) ? '' : $magentoVersion;

        if (phpversion() >= '5.4.0') {
            $jsonResult = json_encode($env, JSON_UNESCAPED_SLASHES);
        } else {
            $jsonResult = json_encode($env);
        }

        return $jsonResult;
    }

    /**
     * @param string $domain: domain ('' or 'my_account_domain')
     * @param string $version: site verison (live, uat and dev)
     * @return mixed|string
     */
    protected function _getSiteDomain($domain = '', $version = '')
    {
        $siteVersion = empty($version) ? $this->getVersion() : $version;
        if (empty($siteVersion)) {
            $siteVersion = 'live';
        }

        if (strcasecmp($domain, 'my_account_domain') === 0) {
            return $this->scopeConfig->getValue('straker/general/my_account_domain/'. $siteVersion);
        } elseif (strcasecmp($domain, 'straker_bug_log_domain') === 0) {
            return $this->scopeConfig->getValue('straker/general/straker_bug_log_domain/'. $siteVersion);
        }

        if (!empty($version)) {
            return $this->scopeConfig->getValue('straker/general/domain/' . $version);
        }

        if ($this->isSandboxMode() && $domain !== 'support') {
            $siteDomain = $this->scopeConfig->getValue('straker/general/domain/sandbox');
        } else {
            $siteDomain = $this->scopeConfig->getValue('straker/general/domain/'. $siteVersion);
            if (empty($siteDomain)) {
                $siteDomain = 'https://app.strakertranslations.com';
            }
        }

        return rtrim($siteDomain, '/');
    }

    public function getRegisterUrl()
    {
        return $this->_getSiteDomain().'/'.$this->scopeConfig->getValue('straker/general/api_url/register');
    }

    public function getLanguagesUrl()
    {
        return $this->_getSiteDomain().'/'.$this->scopeConfig->getValue('straker/general/api_url/languages');
    }

    public function getTranslateUrl()
    {
        return $this->_getSiteDomain().'/'.$this->scopeConfig->getValue('straker/general/api_url/translate');
    }

    public function getQuoteUrl()
    {
        return $this->_getSiteDomain().'/'.$this->scopeConfig->getValue('straker/general/api_url/quote');
    }

    public function getPaymentUrl()
    {
        return $this->_getSiteDomain().'/'.$this->scopeConfig->getValue('straker/general/api_url/payment');
    }

    public function getCountriesUrl()
    {
        return $this->_getSiteDomain().'/'.$this->scopeConfig->getValue('straker/general/api_url/countries');
    }

    public function getSupportUrl()
    {
        return $this->_getSiteDomain('support').'/'.$this->scopeConfig->getValue('straker/general/api_url/support');
    }

    public function getPaymentPageUrl()
    {
        return $this->_getSiteDomain('my_account_domain')
            . '/'
            . $this->scopeConfig->getValue('straker/general/api_url/payment_page');
    }

    public function getBugLogUrl()
    {
        return $this->_getSiteDomain('straker_bug_log_domain')
            . '/'
            . $this->scopeConfig->getValue('straker/general/api_url/bug_log');
    }

    public function getMyAccountUrl()
    {
        return $this->_getSiteDomain('my_account_domain').'/user/login';
    }

    public function getDbBackupUrl()
    {
        return $this->_getSiteDomain('', 'uat')
            . '/'
            . $this->scopeConfig->getValue('straker/general/api_url/backup');
    }

    public function getDbRestoreUrl()
    {
        return $this->_getSiteDomain('', 'uat')
            . '/'
            . $this->scopeConfig->getValue('straker/general/api_url/restore');
    }

    public function getStoreSetup($storeId)
    {
        $collection = $this->_scopeFactory->create(
            ['scope' => ScopeInterface::SCOPE_STORES, 'scopeId' => $storeId ]
        );

        $dbStoreConfig = [];
        foreach ($collection as $item) {
            $dbStoreConfig[$item->getPath()] = $item->getValue();
        }

        $source_store = array_key_exists('straker/general/source_store', $dbStoreConfig)
            ? $dbStoreConfig['straker/general/source_store']
            : false;
        $source_language = array_key_exists('straker/general/source_language', $dbStoreConfig)
            ? $dbStoreConfig['straker/general/source_language']
            : false;
        $destination_language = array_key_exists('straker/general/destination_language', $dbStoreConfig)
            ? $dbStoreConfig['straker/general/destination_language']
            :  false;

        return $source_store && $source_language && $destination_language;
    }

    public function getDefaultAttributes()
    {
        $return = $this->scopeConfig->getValue('straker_config/attribute/product_default', 'default', 0);
        return  empty($return) ? [] : explode(',', $return);
    }

    public function getCustomAttributes()
    {
        $return = $this->scopeConfig->getValue('straker_config/attribute/product_custom', 'default', 0);
        return  empty($return) ? [] : explode(',', $return);
    }

    public function getCategoryAttributes()
    {
        $return =  $this->scopeConfig->getValue('straker_config/attribute/category', 'default', 0);
        return  empty($return) ? [] : explode(',', $return);
    }

    /**
     * get use selected products' filter from core_config
     *
     * @return array
     */
    public function getProductFilters()
    {
        $return =  $this->scopeConfig->getValue('straker_config/filter/product_filters', 'default', 0);
        return  empty($return) ? [] : explode(',', $return);
    }

    public function shouldTranslateBlockTitle()
    {
        $return =  $this->scopeConfig->getValue('straker_config/attribute/include_cms_block_title', 'default', 0);
        return  (empty($return) || $return == '0') ? false : true;
    }

    public function getStoreInfo($storeId)
    {
        $collection = $this->_scopeFactory->create(
            ['scope' => ScopeInterface::SCOPE_STORES, 'scopeId' => $storeId]
        );

        $dbStoreConfig = [];

        foreach ($collection as $item) {
            $dbStoreConfig[$item->getPath()] = $item->getValue();
        }
        return $dbStoreConfig;
    }

    /**
     * retrieve language code for the given store, or the default language code if the store id is not provide
     * @param $storeId
     * @return mixed
     */
    public function getStoreViewLanguage($storeId = null)
    {
        if (!empty($storeId)) {
            return $this->scopeConfig->getValue(
                'straker/general/source_language',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
        } else {
            return $this->scopeConfig->getValue('general/locale/code');
        }
    }

    public function getSourceStore($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'straker/general/source_store',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getOriginalXMLFilePath()
    {
        return $this->getJobsFilePath().DIRECTORY_SEPARATOR.'original';
    }

    public function getTranslatedXMLFilePath()
    {
        return $this->getJobsFilePath().DIRECTORY_SEPARATOR.'translated';
    }

    public function getDataFilePath()
    {
        return $this->getStrakerPath(). DIRECTORY_SEPARATOR . 'data';
    }

    public function getJobsFilePath()
    {
        return $this->getStrakerPath().DIRECTORY_SEPARATOR.'jobs';
    }

    public function getStrakerPath()
    {
        return $this->_directoryList->getPath('var').DIRECTORY_SEPARATOR.'straker';
    }

    public function isSandboxMode()
    {
        return !$this->scopeConfig->getValue('straker_config/env/site_mode');
    }

    public function getSandboxMessage()
    {
        return
            '<p><b>' . __('Sandbox Mode Enabled') . '</b></p><p>'
            . __(
                'Thank you for installing our plugin. We have enabled the Sandbox testing mode for you. 
                Jobs you create while this is enabled will not be received by Straker Translations, 
                and content will not be translated by a human - rather it will only be sample text.
                 To change the Sandbox Mode, go to <a href="'
                . $this->_urlFactory->create()->getUrl('adminhtml/system_config/edit', ['section' => 'straker_config'])
                . '">Configuration</a>'
            )
            . '</p>';
    }

    public function getMagentoVersion()
    {
        return trim($this->_productMetadata->getVersion());
    }

    public function getTestingStoreViewCode()
    {
        return 'straker_testing_storeview';
    }

    public function getDbName()
    {
        return $this->_deployConfig->get(
            ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTION_DEFAULT .
            '/' . ConfigOptionsListConstants::KEY_NAME
        );
    }

    public function getCreateTestStoreViewMessage()
    {
        $url = $this->_urlFactory->create()->getUrl('adminhtml/system_config/edit', ['section' => 'straker_config']);
        return '<p>' . __('Please %1 create a testing store view.%2', '<a href="' . $url . '">', '</a>') . '</p>';
    }

    public function validProperties()
    {
        return [
            'name',
            'content_context',
            'content_context_url',
            'source_store_id',
            'attribute_translation_id',
            'attribute_code',
            'attribute_label',
            'attribute_id',
            'category_id',
            'entity_id',
            'is_label',
            'option_translation_id',
            'is_option',
            'option_id',
            'block_id',
            'page_id'
        ];
    }

    public function getUserInfo()
    {
        $data = [];
        if (!empty($this->scopeConfig->getValue('straker/general/first_name'))) {
            $data['first_name'] = $this->scopeConfig->getValue('straker/general/first_name');
        }

        if (!empty($this->scopeConfig->getValue('straker/general/last_name'))) {
            $data['last_name'] = $this->scopeConfig->getValue('straker/general/last_name');
        }

        if (!empty($this->scopeConfig->getValue('straker/general/email'))) {
            $data['email'] = $this->scopeConfig->getValue('straker/general/email');
        }

        if (!empty($this->scopeConfig->getValue('straker/general/country'))) {
            $data['country'] = $this->scopeConfig->getValue('straker/general/country');
        }

        if (!empty($this->scopeConfig->getValue('straker/general/company_name'))) {
            $data['company_name'] = $this->scopeConfig->getValue('straker/general/company_name');
        }

        if (!empty($this->scopeConfig->getValue('straker/general/phone_number'))) {
            $data['phone_number'] = $this->scopeConfig->getValue('straker/general/phone_number');
        }

        if (!empty($this->scopeConfig->getValue('straker/general/url'))) {
            $data['url'] = $this->scopeConfig->getValue('straker/general/url');
        }
        return $data;
    }
}
