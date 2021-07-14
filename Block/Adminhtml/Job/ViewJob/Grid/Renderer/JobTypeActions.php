<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Grid\Renderer;

use Magento\Framework\DataObject;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Straker\EasyTranslationPlatform\Model\JobType as JobTypeModel;

class JobTypeActions extends AbstractRenderer
{
    public function render(DataObject $row)
    {
        $params = $this->getRequest()->getParams();

        return '<a href="'
                . $this->getUrl(
                    '*/*/ViewJob',
                    [
                        'job_type_id' => $row['job_type_id'],
                        'job_type_referrer' => 0,
                        'entity_id' => $row->getEntityId(),
                        'job_key' => $params['job_key'],
                        'source_store_id' => $params['source_store_id'],
                        'job_id' => $row->getJobId()
                    ]
                )
                . '" title="'. __('View Details') . '">' .__('View Details') . '</a>';
    }
}
