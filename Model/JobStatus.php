<?php
namespace Straker\EasyTranslationPlatform\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class JobStatus extends AbstractModel implements JobStatusInterface, IdentityInterface
{
    const CACHE_TAG                 = 'straker_easytranslationplatform_jobstatus';
    const ENTITY                    = 'straker_job_status';
    const JOB_STATUS_INIT           = 1;
    const JOB_STATUS_QUEUED         = 2;
    const JOB_STATUS_READY          = 3;
    const JOB_STATUS_INPROGRESS     = 4;
    const JOB_STATUS_COMPLETED      = 5;
    const JOB_STATUS_CONFIRMED      = 6;
    const JOB_STATUS                = ['init', 'queued','ready','in_progress','completed','confirmed'];

    protected function _construct()
    {
        $this->_init(\Straker\EasyTranslationPlatform\Model\ResourceModel\JobStatus::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
