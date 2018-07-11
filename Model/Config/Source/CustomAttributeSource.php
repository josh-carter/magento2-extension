<?php
namespace Straker\EasyTranslationPlatform\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Straker\EasyTranslationPlatform\Helper\ProductHelper;

class CustomAttributeSource implements ArrayInterface
{
    protected $_attributeCollection;
    protected $_option;

    public function __construct(ProductHelper $productHelper)
    {
        $this->_attributeCollection = $productHelper->getCustomAttributes();
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        if (!empty($this->_attributeCollection)) {
            $attributesArray = $this->_attributeCollection->getItems();
            if (count($attributesArray)) {
                foreach ($attributesArray as $attribute) {
                    $this->_option[] = [
                       'label' => __($attribute->getFrontend()->getLabel()),
                       'value' => $attribute->getId()
                    ];
                }
                usort($this->_option, function ($a, $b) {
                    return strcmp($a['label'], $b['label']);
                });
                return $this->_option;
            }
        }
        return [ 'label' => __('No attributes are available! '), 'value' => '' ];
    }
}
