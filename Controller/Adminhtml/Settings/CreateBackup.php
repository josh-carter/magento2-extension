<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;

class CreateBackup extends Action
{
    protected $_api;
    protected $_jsonFactory;

    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        StrakerAPIInterface $api
    ) {
        $this->_api = $api;
        $this->_jsonFactory = $jsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $response = $this->_api->dbBackup();
        return $this->_jsonFactory->create()->setData($response);
    }
}
