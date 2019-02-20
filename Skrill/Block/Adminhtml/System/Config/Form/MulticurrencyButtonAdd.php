<?php
namespace Skrill\Skrill\Block\Adminhtml\System\Config\Form;

use Magento\Framework\App\Config\ScopeConfigInterface;

class MulticurrencyButtonAdd extends \Magento\Config\Block\System\Config\Form\Field
{
    const BUTTON_TEMPLATE = 'system/config/multicurrency_button_add.phtml';

     /**
      * Set template to itself
      *
      * @return $this
      */
    public function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::BUTTON_TEMPLATE);
        }
        return $this;
    }

    /**
     * Render button
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->addData(
            [
                'id'            => 'skrill_button',
                'button_label'  => __('SKRILL_BACKEND_BT_MC_ADD'),
                'onclick'       => 'addElement(); return false;'
            ]
        );
        return $this->_toHtml();
    }
}
