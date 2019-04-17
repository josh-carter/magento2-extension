<?php
namespace Straker\EasyTranslationPlatform\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Straker\EasyTranslationPlatform\Helper\ProductHelper;

class ProductFilters implements ArrayInterface
{
    /** @var \Magento\Catalog\Api\Data\ProductAttributeInterface[] $_productAttributes */
    protected $_productAttributes;
    protected $_productHelper;
    protected $_option;

    public function __construct(
        ProductHelper $productHelper
    )
    {
        $this->_productHelper = $productHelper;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $this->_productAttributes = $this->_productHelper->getProductGridAttributeList();
        if (!empty($this->_productAttributes)) {
            if (count($this->_productAttributes)) {
                foreach ($this->_productAttributes as $attribute) {
                    $this->_option[] = [
                        'label' => __($attribute->getFrontend()->getLabel()),
                        'value' => $attribute->getAttributeCode()
                    ];
                }
                usort($this->_option, function ($a, $b) {
                    return strcmp($a['label'], $b['label']);
                });
                return $this->_option;
            }
        }

        return [[ 'label' => 'No attributes are available! ', 'value' => '' ]];
    }
}
