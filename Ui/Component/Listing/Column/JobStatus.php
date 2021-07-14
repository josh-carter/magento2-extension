<?php

namespace Straker\EasyTranslationPlatform\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class JobStatus extends Column
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
                if (isset($item['job_status_id'])) {
                    $statusId = $item['job_status_id'];
                    if ($statusId == 3) {
                        $item[$this->getData('name')] = "
                            <a  href='#'
                                class='straker-view-quote-anchor'
                                data-job-id='". $item['job_id'] ."'
                                data-job-key='" . $item['job_key'] . "' >"
                            . __('View Quote')
                            . "</a>";
                    }
                }
            }
        }

        return $dataSource;
    }
}
