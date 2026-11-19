<?php
namespace Magecomp\Countryflag\Model\Checkout;

class LayoutProcessorplugin
{
    protected $_helper;

    public function __construct(
        \Magecomp\Countryflag\Helper\Data $helper
    ) {
        $this->_helper = $helper;
    }
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        array  $jsLayout
    ) {
        if($this->_helper->isEnabled()){
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone']['notice'] = __('Enter Number Without Country code & without any sign. e.g:-(99xxxxxx09)');
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone']['config']['tooltip']['description'] = __('For Order Updates.');
        }
       
        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
        ['payment']['children']['payments-list']['children'])) 
        {
            foreach ($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'] as $key => $payment) 
            {                
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']['telephone']['notice'] = __('Enter Number Without Country code & without any sign. e.g:-(99xxxxxx09)');                
            } 

        }   
 
        return $jsLayout;
    }
}