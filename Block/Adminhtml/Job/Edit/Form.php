<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\Edit;

use Magento\Backend\Block\Widget\Form\Generic;

/**
 * Adminhtml attachment edit form block
 *
 */
class Form extends Generic
{
    /**
     * Prepare form
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
