<?php

namespace Straker\EasyTranslationPlatform\Helper;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Eav\Model\AttributeRepository;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use Straker\EasyTranslationPlatform\Logger\Logger;
use Straker\EasyTranslationPlatform\Model\Job;
use Straker\EasyTranslationPlatform\Model\JobFactory;
use Straker\EasyTranslationPlatform\Model\JobType;

class AttributeHelper extends AbstractHelper
{

    protected $_translatedAttributeLabels = [];
    protected $_attributeRepository;
    protected $_jobFactory;
    protected $_strakerApi;

    /**
     * AttributeHelper constructor.
     * @param Context $context
     * @param AttributeRepository $attributeRepository
     * @param Logger $logger
     * @param JobFactory $jobFactory
     * @param StrakerAPIInterface $strakerAPI
     */
    public function __construct(
        Context $context,
        AttributeRepository $attributeRepository,
        Logger $logger,
        JobFactory $jobFactory,
        StrakerAPIInterface $strakerAPI
    ) {

        $this->_attributeRepository = $attributeRepository;
        $this->_logger = $logger;
        $this->_jobFactory = $jobFactory;
        $this->_strakerApi = $strakerAPI;
        parent::__construct($context);
    }

    public function getConfigurableAttributes($product)
    {
        $attributes = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
        $configAttributeData = [];

        foreach ($attributes as $attribute) {
            $value_data = [];
            foreach ($attribute['values'] as $value) {
                $value_data[] = ['option_id' => $value['value_index'], 'value' => $value['default_label']];
            }

            $configAttributeData[] = [
                'attribute_code'=>$attribute['attribute_code'],
                'attribute_id' => $attribute['attribute_id'],
                'label' => $attribute['label'],
                'value' => $value_data
            ];
        }

        return $configAttributeData;
    }

    public function findMultiOptionAttributes($attribute_id, $product, $store_id)
    {
        $attribute = $this->_attributeRepository->get(Product::ENTITY, $attribute_id)->setStoreFilter($store_id);
        $options = $product->getResource()->getAttributeRawValue($product->getId(), $attribute, $store_id);

        if ($options) {
            $values['attribute_id'] = $attribute_id;
            $values['label'] = $attribute->getFrontendLabel();
            $values['attribute_code'] = $attribute->getAttributeCode();
            $options = explode(',', $options);

            foreach ($options as $option_id) {
                $values['value'][] = ['option_id' => $option_id, 'value' => $attribute->getSource()->getOptionText($option_id)];
            }

            return $values;
        }

        return false;
    }

    public function appendAttributeLabel(
        $productData,
        $attribute,
        $jobName,
        $source_store_id,
        $xmlHelper
    ) {

        if ($productData) {

            try {

                $xmlHelper->appendDataToRoot([
                    'name' => $jobName,
                    'content_context' => 'product_attribute_label',
                    'content_context_url' => $productData['product_url'],
                    'attribute_translation_id' => $attribute['label_translation_id'],
                    'source_store_id' => $source_store_id,
                    'product_id' => $productData['product_id'],
                    'attribute_id' => $attribute['attribute_id'],
                    'attribute_label' => $attribute['label'],
                    'value' => $attribute['label'],
                    'translate' => in_array($attribute['label'], $this->_translatedAttributeLabels) ? 'false' : 'true'
                ]);

                array_push($this->_translatedAttributeLabels, $attribute['label']);

            } catch (\Exception $e) {
                $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                $this->_logger->error('error', __FILE__ . ' ' . __LINE__ . '' . $e->getMessage(), $e);
            }
        }
    }
}
