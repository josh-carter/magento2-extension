<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Form\Renderer;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Helper\Data;

class JobDestination extends Element implements RendererInterface
{
    protected $_template = 'Straker_EasyTranslationPlatform::renderer/form/jobDestination.phtml';

    /**
     * @var \Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface
     */
    protected $_strakerAPIInterface;
    protected $_configHelper;
    protected $_dataHelper;

    public function __construct(
        Context $context,
        StrakerAPIInterface $strakerAPI,
        ConfigHelper $configHelper,
        Data $dataHelper,
        array $data = []
    ) {
    
        $this->_strakerAPIInterface = $strakerAPI;
        $this->_configHelper = $configHelper;
        $this->_dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element)
    {
        $this->_element = $element;
        $html = $this->toHtml();
        return $html;
    }

    public function getWebsites()
    {
        return $this->_storeManager->getWebsites();
    }

    public function getOptions()
    {
        if ($this->isSandboxModeEnabled()) {
            return $this->getSandboxLanguages();
        }
        return $this->_strakerAPIInterface->getLanguages();
    }

    public function isSandboxModeEnabled()
    {
        return $this->_configHelper->isSandboxMode();
    }

    public function getSandboxLanguages()
    {
        return $this->_dataHelper->getSandboxLanguages();
    }

    public function getTestingStoreCode()
    {
        return $this->_configHelper->getTestingStoreViewCode();
    }

    public function listStores($label, $forTarget = false)
    {
        $options = '<option value="">' . __($label) . '</option>';

        foreach ($this->getWebsites() as $website):
            $showWebsite = false;
            foreach ($website->getGroups() as $group):
                $showGroup = false;
                foreach ($group->getStores() as $store):
                    if ($showWebsite === false):
                        $showWebsite = true;
                        $options .= '<optgroup label="' . $this->escapeHtml($website->getName()) . '"></optgroup>';
                    endif;
                    if ($showGroup === false):
                        $showGroup = true;
                        $options .= '<optgroup label="' . $this->escapeHtml($group->getName())  . '">';
                    endif;
                    if ($forTarget) {
                        if ((!$this->isSandboxModeEnabled()
                            && strcasecmp($store->getCode(), $this->getTestingStoreCode()) != 0)
                        || ($this->isSandboxModeEnabled()
                            && strcasecmp($store->getCode(), $this->getTestingStoreCode()) == 0)):

                            $options .= '<option value="'
                                . $this->escapeHtml($store->getId())
                                . '">'
                                . $this->escapeHtml($store->getName())
                                . '</option>';
                        endif;
                    } else {
                        $options .= '<option value="' . $this->escapeHtml($store->getId()) . '">';
                        $options .= $this->escapeHtml($store->getName());
                        $options .= '</option>';
                    }
                endforeach;
                if ($showGroup):
                    $options .= '</optgroup>';
                endif;
            endforeach;
        endforeach;

        return $options;
    }
}
