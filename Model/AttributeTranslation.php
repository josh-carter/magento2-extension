<?php
namespace Straker\EasyTranslationPlatform\Model;

class AttributeTranslation extends \Magento\Framework\Model\AbstractModel implements AttributeTranslationInterface, \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'straker_easytranslationplatform_attributetranslation';

    const ENTITY = 'straker_attribute_translation';
    
    protected $_optionFactory;
    protected $_options = [];
    protected $_optionIds = [];
    protected $_optionsCount;
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Straker\EasyTranslationPlatform\Model\AttributeOptionTranslationFactory $optionFactory,
        array $data = []
    ) {
        $this->_optionFactory = $optionFactory;
        parent::__construct($context, $registry);
    }

    protected function _construct()
    {
        $this->_init(\Straker\EasyTranslationPlatform\Model\ResourceModel\AttributeTranslation::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getOptions()
    {
        if ($this->getData('has_option')) {
            $this->_loadOptions();
        }
        return $this->_options;
    }

    public function getOptionCollection()
    {
        return $this->_optionFactory->create()->getCollection()->addOptionFilter($this->getId());
    }

    protected function _loadOptions()
    {
        $this->_options = [];
        $this->_optionsCount = 0;
        foreach ($this->getOptionCollection() as $option) {
            $this->_options[$option->getOptionId()] = $option;
            $this->_optionIds[ $option->getOptionId() ] = $option->getOptionId();

            $this->_optionsCount++;
        }
    }
}
