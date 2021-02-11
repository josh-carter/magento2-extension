<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Jobs;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Model\JobFactory;
use Straker\EasyTranslationPlatform\Model\StrakerAPI;
use Straker\EasyTranslationPlatform\Logger\Logger;
use Straker\EasyTranslationPlatform\Model\ResourceModel\AttributeOptionTranslation\CollectionFactory
    as AttributeOptionTranslationCollectionFactory;

class AttributeOption extends \Magento\Backend\App\Action
{
    protected $_resultJsonFactory;
    protected $_attributeOptionTranslationCollectionFactory;
    protected $_configHelper;
    protected $_strakerApi;
    protected $_jobFactory;
    protected $_logger;

    public function __construct(
        Context $context,
        AttributeOptionTranslationCollectionFactory $attributeOptionTranslationCollectionFactory,
        ConfigHelper $configHelper,
        JsonFactory $resultJsonFactory,
        StrakerAPI $strakerAPI,
        JobFactory $jobFactory,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->_attributeOptionTranslationCollectionFactory = $attributeOptionTranslationCollectionFactory;
        $this->_configHelper = $configHelper;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_strakerApi = $strakerAPI;
        $this->_jobFactory = $jobFactory;
        $this->_logger = $logger;
    }

    public function execute()
    {
        $attributeTranslationId = $this->getRequest()->getParam('attributeTranslationId');

        $result = [ 'status' => true, 'message' => '', 'option_data' => []];
        $options = [];

        $optionCollectionData = $this->_attributeOptionTranslationCollectionFactory
            ->create()
            ->addFieldToFilter('attribute_translation_id', ['eq' => $attributeTranslationId]);

        foreach ($optionCollectionData as $option) {
            $translatedValue = $option->getData('translated_value');
            array_push($options, [
                'attribute_option_translation_id' => $option->getData('attribute_option_translation_id'),
                'original_value'                  => $option->getData('original_value'),
                'translated_value'                => empty($translatedValue) ? '' : $option->getData('translated_value')
            ]);
        }

        $result['option_data'] = $options;
        return $this->_resultJsonFactory->create()->setData($result);
    }

    /**
     * @param $apiJob
     * @param \Straker\EasyTranslationPlatform\Model\Job $localJob
     * @return array
     */
    protected function _compareJobs($apiJob, $localJob)
    {
        if ($localJob->getJobStatusId() < $this->resolveApiStatus($apiJob)) {
            return $localJob->updateStatus($apiJob);
        }

        return ['isSuccess' => false, 'Message'=> __('The status is up to date') ];
    }

    protected function resolveApiStatus($apiJob)
    {
        $status = 0;
        if (!empty($apiJob) && !empty($apiJob->status)) {
            switch (strtolower($apiJob->status)) {
                case 'queued':
                    $status =  strcasecmp($apiJob->quotation, 'ready') == 0  ? 3 : 2;
                    break;
                case 'in_progress':
                    $status = 4;
                    break;
                case 'completed':
                    $status = 5;
                    break;
                default:
                    $status = 0;
                    break;
            }
        }

        return $status;
    }
    /**
     * Is the user allowed to view the attachment grid.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Straker_EasyTranslationPlatform::jobs');
    }
}
