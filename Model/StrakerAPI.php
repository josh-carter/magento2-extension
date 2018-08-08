<?php
namespace Straker\EasyTranslationPlatform\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Exception;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Logger\Logger;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Message\ManagerInterface;
use Zend_Http_Client;

class StrakerAPI extends AbstractModel implements StrakerAPIInterface
{
    protected $_config;
    /**
     * Error codes recollected after each API call
     *
     * @var array
     */
    protected $_callErrors = [];
    protected $_storeId;
    protected $_logger;
    /**
     * Headers for each API call
     *
     * @var array
     */
    protected $_headers = [];
    protected $_options = [];
    protected $_configHelper;
    protected $_configModel;
    protected $_httpClient;
    protected $_storeManager;
    protected $_messageManager;

    public function __construct(
        Context $context,
        Registry $registry,
        ConfigHelper $configHelper,
        Config $configModel,
        ZendClientFactory $httpClient,
        Logger $logger,
        StoreManagerInterface $storeManagerInterface,
        ManagerInterface $messageInterface
    ){
        parent::__construct($context, $registry);
        $this->_configHelper = $configHelper;
        $this->_configModel = $configModel;
        $this->_httpClient = $httpClient;
        $this->_logger = $logger;
        $this->_storeManager = $storeManagerInterface;
        $this->_messageManager = $messageInterface;
    }

    protected function _call($url, $method = 'get', array $request = [], $timeout = 60 )
    {
        $httpClient = $this->_httpClient->create();
        $return = '';

        try{

            switch(strtolower($method)){
                case 'post':
                    $method = Zend_Http_Client::POST;
                    $httpClient->setParameterPost($request);
                    if(!empty($request['source_file'])){
                        $httpClient->setFileUpload($request['source_file'], 'source_file');
                    }
                    break;
                case 'get':
                    $method = Zend_Http_Client::GET;
                    break;
            }

            $httpClient->setUri($url);
            $httpClient->setConfig(['timeout' => $timeout, 'verifypeer' => 0]);
            $httpClient->setHeaders($this->getHeaders());
            $httpClient->setMethod($method);

            $response = $httpClient->request();

            if(!$response->isError()){
                $contentType = $response->getHeader('Content-Type');
                $body = $response->getBody();

                if(strpos($contentType,'application/json') !== false ){
                    $return = json_decode($body);
                }else{
                    $return = $body;
                }
            }else{
                $this->_messageManager->addError(__('Straker API error. Please check logs.'));
                $return = $response;
            }
        }catch(Exception $e){
            $this->_logger->error('error'.__FILE__.' '.__LINE__, [$e]);
            $this->_messageManager->addException($e, 'Straker API error. Please check logs.');
            $this->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
        }

        return $return;
    }

    protected function _buildQuery($request)
    {
        return http_build_query($request);
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options = [])
    {
        $this->_options = $options;
        return $this;
    }

    protected function _isCallSuccessful($response)
    {
        if(isset($response->code)){
            return false;
        }
        if(isset($response->success) || isset($response->languages) || isset($response->country)){
            return true;
        }
        return false;
    }

    /**
     * Handle logical errors
     *
     * @param array $response
     * @throst Mage_Core_Exception
     */
    protected function _handleCallErrors($response)
    {
        if(empty($response)){
            return;
        }
        if(isset($response->message) && strpos($response->message, 'Authentication failed') !== false){
            $response->magentoMessage = $response->message;
        }
        return;
        // to be added
    }

    protected function _getRegisterUrl()
    {
        return $this->_configHelper->getRegisterUrl();
    }

    protected function _getLanguagesUrl()
    {
        return $this->_configHelper->getLanguagesUrl();
    }

    protected function _getCountriesUrl()
    {
        return $this->_configHelper->getCountriesUrl();
    }

    protected function _getTranslateUrl()
    {
        return $this->_configHelper->getTranslateUrl();
    }

    protected function _getQuoteUrl()
    {
        return $this->_configHelper->getQuoteUrl();
    }

    protected function _getPaymentUrl()
    {
        return $this->_configHelper->getPaymentUrl();
    }

    protected function _getSupportUrl()
    {
        return $this->_configHelper->getSupportUrl();
    }

    public function getHeaders()
    {
        $this->_headers[] = 'Authorization: Bearer ' . $this->_configHelper->getAccessToken();
        $this->_headers[] = 'X-Auth-App: ' . $this->_configHelper->getApplicationKey();
        return $this->_headers;
    }

    public function callRegister($data)
    {
        $data['meta_data'] = $this->_configHelper->getEnv();
        return $this->_call($this->_getRegisterUrl(), 'post', $data);
    }

    public function callTranslate($data)
    {
        $this->_headers[] = 'Content-Type:multipart/form-data';
        return $this->_call($this->_getTranslateUrl(), 'post', $data);
    }

    public function callSupport($data)
    {
        return $this->_call($this->_getSupportUrl(), 'post', $data);
    }

    public function getQuote($data)
    {
        return $this->_call($this->_getQuoteUrl() . '?' . $this->_buildQuery($data));
    }

    public function getPayment($data)
    {
        return $this->_call($this->_getPaymentUrl() . '?'.$this->_buildQuery($data));
    }

    public function getTranslation($data = [])
    {
        return $this->_call($this->_getTranslateUrl() . '?'. $this->_buildQuery($data));
    }

    public function getTranslatedFile($downloadUrl)
    {
        return $this->_call($downloadUrl);
    }

    public function getCountries()
    {
        $filePath = $this->_configHelper->getDataFilePath();
        $fileName = 'countries.json';
        if(!file_exists($filePath)){
            mkdir($filePath, 0777, true);
        }
        $fileFullPath = $filePath . DIRECTORY_SEPARATOR . $fileName;
        if(file_exists($fileFullPath)){
            $result = json_decode(file_get_contents($fileFullPath));
        }else{
            $result = $this->_call($this->_getCountriesUrl());
            if(!empty($result)){
                file_put_contents($fileFullPath, json_encode($result));
            }
        }
        return isset($result->country) ? $result->country : [];
    }

    public function getLanguages()
    {
        $filePath = $this->_configHelper->getDataFilePath();
        $fileName = 'languages.json';
        if(!file_exists($filePath)){
            mkdir($filePath, 0777, true);
        }
        $fileFullPath = $filePath . DIRECTORY_SEPARATOR . $fileName;
        if(file_exists($fileFullPath)){
            $result = json_decode(file_get_contents($fileFullPath));
        }else{
            $result = $this->_call($this->_getLanguagesUrl());
            if(!empty($result)){
                file_put_contents($fileFullPath, json_encode($result));
            }
        }
        return isset($result->languages) ? $result->languages : [];
    }

    public function getLanguageName($code = '')
    {
        $languages = $this->getLanguages();
        $languageName = '';
        foreach($languages as $k => $val){
            foreach($val as $i => $langCodes){
                if($langCodes == $code){
                    $languageName = $val->name;
                    break;
                }
            }
        }
        return $languageName;
    }

    // for demo only
    public function completeJob($jobNumber, $url)
    {
        return $this->_call($url, 'post', ['job_id' => $jobNumber]);
    }

    public function dbBackup()
    {
        return $this->_call(
            $this->_configHelper->getDbBackupUrl(),
            'post',
            [
                'app_title' => $this->_configHelper->getDbName(),
                'app_name' => $this->_configHelper->getDbName()
            ]
        );
    }

    public function dbRestore()
    {
        return $this->_call(
            $this->_configHelper->getDbRestoreUrl(),
            'post',
            [
                'app_title' => $this->_configHelper->getDbName(),
                'app_name' => $this->_configHelper->getDbName()
            ]
        );
    }

    public function _callStrakerBugLog($msg, $e = '')
    {
        $httpClient = $this->_httpClient->create();
        $url = $this->_configHelper->getBugLogUrl();

        $requestData = [
            'APIKey'           => $this->_configHelper->getApplicationKey(),
            'applicationCode'  => 'Magento2 Plugin',
            'HTMLReport'       => 'HTMLReport',
            'templatePath'     => $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            'message'          => $msg,
            'severityCode'     => 'ERROR',
            'exceptionMessage' => $msg,
            'exceptionDetails' => $e,
            'userAgent'        => $_SERVER['HTTP_USER_AGENT'],
            'dateTime'         => date('m/d/Y H:i:s'),
            'hostName'         => $_SERVER['HTTP_HOST']
        ];

        try{
            $httpClient->setHeaders($this->getHeaders());
            $httpClient->setConfig(['timeout' => 300, 'verifypeer' => 0]);
            $httpClient->setMethod(Zend_Http_Client::POST);
            $httpClient->setParameterPost($requestData);
            $httpClient->setUri($url);
            $httpClient->request();
        }catch(\Zend_Http_Client_Exception $e){
            $this->_logger->error('error'.__FILE__.' '.__LINE__, [$e]);
            $this->_messageManager->addExceptionMessage($e, __('Something went wrong while connecting Straker API.'));
        }
    }
}
