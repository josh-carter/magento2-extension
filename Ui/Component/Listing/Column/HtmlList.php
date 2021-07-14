<?php

namespace Straker\EasyTranslationPlatform\Ui\Component\Listing\Column;

use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Escaper;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Straker\EasyTranslationPlatform\Helper\JobHelper;

class HtmlList extends Column
{
    /**
     * @var Escaper
     */
    protected $escaper;
    protected $productTypes;
    protected $allProductTypes;

    protected $labels = [
        'page'      => 'CMS Page',
        'block'     => 'CMS Block',
        'product'   => 'Product',
        'category'  => 'Category'
    ];

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Escaper $escaper
     * @param Type $productTypes
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Escaper $escaper,
        Type $productTypes,
        array $components = [],
        array $data = []
    ) {
        $this->escaper = $escaper;
        $this->productTypes = $productTypes;
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
                $data = explode(JobHelper::SEPARATOR, $item[$this->getData('name')]);
                if (!empty($data)) {
                    $item = $this->generateHtml($data, $item);
                }
            }
        }

        return $dataSource;
    }

    private function getLabel($text)
    {
        if (isset($this->labels[$text])) {
            return $this->labels[$text];
        }
        return $text;
    }

    private function getAllProductTypes()
    {
        if ($this->allProductTypes === null) {
            $typeObjects = $this->productTypes->getTypes();

            $this->allProductTypes = array_reduce($typeObjects, function ($carry, $item) {
                $carry[$item['name']] = $item['label']->getText();
                return $carry;
            }, []);
        }

        return $this->allProductTypes;
    }

    /**
     * @param array $data
     * @param $item
     * @return mixed
     */
    protected function generateHtml(array $data, $item)
    {
        $html = '<ul>';
        foreach ($data as $v) {
            try {
                $json = json_decode($v, true);
            } catch (\Exception $e) {
                $json = null;
            }

            if ($json === null) {
                $html .= '<li>' . __($this->getLabel($v)) . '</li>';
            } else {
                $html .= $this->generateSummaryHtml($json);

            }
        }
        $html .= '</ul>';
        $item[$this->getData('name')] = $this->escaper->escapeHtml($html, ['ul', 'li', 'b']);
        return $item;
    }

    /**
     * @param $json
     * @return string
     */
    protected function generateSummaryHtml($json): string
    {
        $html = '';
        $productTypes = $this->getAllProductTypes();
        $isFirst = true;
        $counter = 1;
        $typeLength = count($productTypes);
        $totalProducts = 0;
        foreach ($productTypes as $name => $label) {
            if (isset($json[$name])) {
                if ($isFirst) {
                    $html .= '<ul>';
                    $html .= '<b>' . __($this->getLabel('product')). ': PRODUCT_TOTAL' . '</b>';
                    $isFirst = false;
                }

                $totalProducts += $json[$name];
                $html .= '<li>&nbsp;-&nbsp;' . __($label) . ': ' . $json[$name] . '</li>';
                $counter++;

                if ($typeLength === $counter) {
                    $html .= '</ul>';
                }
            }
        }

        $html = str_replace('PRODUCT_TOTAL', $totalProducts, $html);

        if (isset($json['category'])) {
            $html .= '<li><b>';
            $html .= __($this->getLabel('category'));
            $html .= ': ' . $json['category'] . '</b></li>';
        }

        if (isset($json['cms_page'])) {
            $html .= '<li><b>';
            $html .= __($this->getLabel('page'));
            $html .= ': ' . $json['cms_page'] . '</b></li>';
        }

        if (isset($json['cms_block'])) {
            $html .= '<li><b>';
            $html .= __($this->getLabel('block'));
            $html .= ': ' . $json['cms_block'] . '</b></li>';
        }
        return $html;
    }
}
