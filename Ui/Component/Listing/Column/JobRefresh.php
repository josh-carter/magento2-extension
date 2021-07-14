<?php

namespace Straker\EasyTranslationPlatform\Ui\Component\Listing\Column;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class JobRefresh extends Column
{
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
                if (isset($item['job_key'])) {
                    $item[$this->getData('name')] = "
                    <a  href='#'
                        class='straker-job-refresh-anchor'
                        data-job-id='". $item['job_id'] ."'
                        data-job-key='" . $item['job_key'] . "' >
                        <i class='fa fa-refresh'></i>
                    </a>";
                }
            }
        }
        return $dataSource;
    }
}
