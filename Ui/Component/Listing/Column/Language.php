<?php

namespace Straker\EasyTranslationPlatform\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Straker\EasyTranslationPlatform\Model;

class Language extends Column
{
    protected $_strakerAPI;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Model\StrakerAPI $strakerAPI,
        array $components,
        array $data
    ) {
    
        $this->_strakerAPI = $strakerAPI;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')] = $this->_strakerAPI->getLanguageName($item[$this->getData('name')]);
            }
        }

        return $dataSource;
    }
}
