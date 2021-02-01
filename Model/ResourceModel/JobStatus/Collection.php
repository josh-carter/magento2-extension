<?php
namespace Straker\EasyTranslationPlatform\Model\ResourceModel\JobStatus;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected function _construct()
    {
        $this->_init(
            \Straker\EasyTranslationPlatform\Model\JobStatus::class,
            \Straker\EasyTranslationPlatform\Model\ResourceModel\JobStatus::class
        );
    }

    /**
     * Convert items array to array for select options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $data = $this->_toOptionArray('status_id', 'status_name');
        foreach ($data as $key => $item) {
            $data[$key]['label'] = $this->convertToFrontendLabel($item['label']);
        }
        return $data;
    }

    private function convertToFrontendLabel($label)
    {
        $frontEndLabel = '';
        switch ($label) {
            case 'init':
                $frontEndLabel = __(ucwords('created'));
                break;
            case 'queued':
                $frontEndLabel = __(ucwords('waiting for quote'));
                break;
            case 'ready':
                $frontEndLabel =  __(ucwords('quote ready'));
                break;
            case 'in_progress':
                $frontEndLabel = __(ucwords('in progress'));
                break;
            case 'completed':
                $frontEndLabel = __(ucwords('ready to publish'));
                break;
            case 'confirmed':
                $frontEndLabel = __(ucwords('published'));
                break;
        }

        return $frontEndLabel;
    }
}
