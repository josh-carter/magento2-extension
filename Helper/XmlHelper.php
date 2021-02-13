<?php

namespace Straker\EasyTranslationPlatform\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DomDocument\DomDocumentFactory;
use \Magento\Framework\Filesystem\Driver\File as FileDriver;

class XmlHelper extends AbstractHelper
{
    /** @var string $_version */
    private $_version = "1.0";

    /** @var string $_encoding */
    private $_encoding = 'utf-8';

    /** @var string $_xmlFilePath */
    private $_xmlFilePath;

    /** @var string $_xmlFileName */
    private $_xmlFileName;

    /** @var \DOMDocument $_xmlFileName */
    private $_dom;

    /** @var \DOMElement $_xmlFileName */
    private $_root;

    /** @var \DOMElement $_xmlFileName */
    private $_data;

    /** @var JobHelper $_jobHelper */
    private $_configHelper;

    /** @var FileDriver */
    private $driver;

    private $_elemAttributes = [
        'name',
        'content_context',
        'content_context_url',
        'product_id',
        'attribute_id',
        'parent_attribute_id',
        'parent_attribute_name',
        'option_id'
    ];

    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        DomDocumentFactory $domDocumentFactory,
        FileDriver $driver
    ) {
        $this->_dom = $domDocumentFactory->create();
        $this->_configHelper = $configHelper;
        $this->_xmlFilePath = $this->_configHelper->getOriginalXMLFilePath();
        $this->driver = $driver;
        parent::__construct($context);
    }

    /**
     * @param $jobId
     * @param bool $showAppInfo
     * @return bool|\DOMElement
     */
    public function create($jobId)
    {
        $this->_dom->version = $this->getVersion();
        $this->_dom->encoding = $this->getEncoding();

        $this->_xmlFileName = $this->_xmlFilePath . DIRECTORY_SEPARATOR . 'straker_job'. $jobId .'.xml';
        $flag = true;

        if (!$this->driver->isExists($this->_xmlFilePath)) {
            $flag = $this->driver->createDirectory($this->_xmlFilePath);
        }

        if (!$flag) {
            return false;
        }

        if (!$this->driver->isExists($this->_xmlFileName)) {
            //phpcs:disable
            $isSuccess = file_put_contents($this->_xmlFileName, "");
            //phpcs:enable
            if ($isSuccess === false) {
                return false;
            }
        }

        $this->_root = $this->_dom->createElement('root');

        return true;
    }

    public function getAppInfo()
    {
        $appInfo = [];

        if (!empty($this->_root)) {
            $appInfo['app_name'] = 'magento2';
            $appInfo['php_ver'] = phpversion();

            //add magento version
            $appVersion = $this->_configHelper->getMagentoVersion();
            if ($appVersion !== '') {
                $appInfo['app_ver'] = $appVersion;
            }

            //add module version
            $strakerModuleVersion = $this->_configHelper->getModuleVersion();
            if ($strakerModuleVersion !== '') {
                $appInfo['straker_ver'] = $strakerModuleVersion;
            }
        }
        return $appInfo;
    }

    public function addContentSummary($nodeData, $showAppInfo = false)
    {
        $summaryNode = $this->_dom->createElement('summary');
        $summaryValue = [];

        if ($showAppInfo) {
            $summaryValue['app_info']  = $this->getAppInfo();
        }

        $contentData = [];
        foreach ($nodeData as $key => $value) {
            $contentData[$key] = $value;
        }

        if ($showAppInfo) {
            $summaryValue['content'] = $contentData;
        } else {
            $summaryValue = $contentData;
        }

        $summaryValue = json_encode($summaryValue);
        $summaryNode->nodeValue = $summaryValue;
        $firstNode = $this->_root->firstChild;
        $this->_root->insertBefore($summaryNode, $firstNode);
        return $summaryValue;
    }

    /**
     * @param array $attributes
     * @return bool
     */
    public function appendDataToRoot($attributes = [])
    {
        $this->_data = $this->_dom->createElement('data');

        foreach ($attributes as $key => $value) {
            ($key !='value')? $this->_data->setAttribute($key, $value) : false;
        }

        $valueElem = $this->_dom->createElement('value');
        $valueElem->appendChild($this->_dom->createCDATASection($attributes['value']));

        $this->_data->appendChild($valueElem);
        $this->_root->appendChild($this->_data);

        return true;
    }

    /**
     * @param array $data
     * @return bool
     */
    private function _validateKeys($data = [])
    {
        return ( 0 === count(array_diff($this->_elemAttributes, array_keys($data))) );
    }
    
    /**
     * @return bool
     */
    public function saveXmlFile()
    {
        $this->_dom->formatOutput = true;
        if (!$this->driver->isExists($this->_xmlFileName)) {
            return false;
        }
        $this->_dom->appendChild($this->_root);
        $saveData = $this->_dom->save($this->_xmlFileName);
        //var_dump($saveData);
        //exit;
        $this->_dom->documentElement->parentNode->removeChild($this->_root);

        return true;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->_version;
    }

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->_version = $version;
    }

    /**
     * @return string
     */
    public function getEncoding()
    {
        return $this->_encoding;
    }

    /**
     * @param string $encoding
     */
    public function setEncoding($encoding)
    {
        $this->_encoding = $encoding;
    }

    /**
     * @return string
     */
    public function getXmlFilePath()
    {
        return $this->_xmlFilePath;
    }

    /**
     * @return string
     */
    public function getXmlFileName()
    {
        return $this->_xmlFileName;
    }

    /**
     * @return \DOMElement
     */
    public function getRoot()
    {
        return $this->_root;
    }

    public function getDom()
    {
        return $this->_dom;
    }

    /**
     * @return array
     */
    public function getElemAttributes()
    {
        return $this->_elemAttributes;
    }
}
