<?php
namespace Straker\EasyTranslationPlatform\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class AttributeOptionTranslation extends AbstractModel implements AttributeOptionTranslationInterface, IdentityInterface
{
    const CACHE_TAG = 'straker_easytranslationplatform_attributeoptiontranslation';

    const ENTITY = 'straker_attribute_option_translation';

    protected function _construct()
    {
        $this->_init(\Straker\EasyTranslationPlatform\Model\ResourceModel\AttributeOptionTranslation::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
