<?php
namespace Skrill\Skrill\Block\Adminhtml\System\Config\Form;

use Magento\Framework\App\Config\ScopeConfigInterface;

class MulticurrencyButtonDelete extends \Magento\Config\Block\System\Config\Form\Field
{
    const BUTTON_TEMPLATE = 'system/config/multicurrency_button_delete.phtml';

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
                'id'        => 'skrill_button',
                'class'     => 'button_delete',
                'button_label'     => __('SKRILL_BACKEND_BT_MC_DELETE'),
                'onclick'   => 'deleteElement(this.id); return false;'
            ]
        );
        return $this->_toHtml();
    }
}
