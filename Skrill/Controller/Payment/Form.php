<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 *
 * @package     Skrill
 * @copyright   Copyright (c) 2014 Skrill
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Skrill\Skrill\Controller\Payment;

class Form extends \Skrill\Skrill\Controller\Payment\Index
{
    /**
     * execute payment form
     */
    public function execute()
    {
        $this->order = $this->_getOrder();

        $paymentMethod = $this->order->getPayment()->getMethod();
        $this->method = $this->order->getPayment()->getMethodInstance();

        $settings = $this->method->getSkrillSettings();

        if (empty($paymentMethod)
            || empty($settings['merchant_id'])
            || empty($settings['merchant_account'])
            || empty($settings['api_passwd'])
            || empty($settings['secret_word'])
        ) {
            $this->redirectError('ERROR_GENERAL_REDIRECT');
            return false;
        }
    	$paymentParameters = $this->getPaymentParameters();

        $sid = $this->helperCore->getSid($paymentParameters);

        if (!$sid) {
            $this->redirectError('ERROR_GENERAL_REDIRECT');
            return false;
        } elseif (!$this->helperCore->isMd5Valid($sid)) {
            $this->redirectError('ERROR_GET_SID');
            return false;
        }

        $paymentUrl = $this->helperCore->getPaymentUrl().'/?sid='.$sid;
        $this->catalogSession->setPaymentUrl($paymentUrl);
        
        $this->_redirect(
            'skrill/payment',
            [
                'trn_id'    => $paymentParameters['transaction_id'],
                '_secure' => true
            ]
        );
    }
}
