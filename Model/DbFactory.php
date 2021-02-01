<?php
namespace Straker\EasyTranslationPlatform\Model;

class DbFactory extends \Magento\Framework\Backup\Factory
{

    private $_objectManager;

    function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
        parent::__construct($objectManager);
    }

    public function create($type)
    {
        if (!in_array($type, $this->_allowedTypes)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                new \Magento\Framework\Phrase(
                    'Current implementation not supported this type (%1) of backup.',
                    [$type]
                )
            );
        }
        $class = 'Straker\EasyTranslationPlatform\Model\\' . ucfirst($type);
        return $this->_objectManager->create($class);
    }
}
