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

class HandleReturn extends \Skrill\Skrill\Controller\Payment\Index
{
    /**
     * execute payment handlereturn
     */
    public function execute()
    {
        $this->logger->info('process return url');

        $orderId = $this->getRequest()->getParam('orderId');
        $this->_order = $this->getOrderByIncerementId($orderId);

        $additionalInformation = $this->_order->getPayment()->getAdditionalInformation();
        $this->logger->info('payment additional information : ', $additionalInformation);

        if (isset($additionalInformation['skrill_status'])) {
            if ($additionalInformation['skrill_status'] == \Skrill\Skrill\Model\Method\AbstractMethod::FAILED_STATUS) {
                $this->logger->info('process return url : failed payment');
                $failedReasonCode = '';
                if (isset($additionalInformation['failed_reason_code'])) {
                    $failedReasonCode = $additionalInformation['failed_reason_code'];
                }
                $this->redirectError($this->helperCore->getSkrillErrorMapping($failedReasonCode));
            } else {
                $this->logger->info('process return url : success payment');
                $this->deactiveQuote();
                $this->_getCheckoutSession()->setLastRealOrderId($this->_order->getIncrementId());
                $this->_redirect('checkout/onepage/success', ['_secure' => true]);
            }
        } else {
            $this->logger->info('process return url : late status url');
            $payment = $this->_order->getPayment();
            $paymentMethod = $this->_order->getPayment()->getMethod();
            $this->method = $this->_order->getPayment()->getMethodInstance();

            $transaction_id = $this->getRequest()->getParam('transaction_id');
            $payment->setAdditionalInformation('skrill_transaction_id', $transaction_id);
            $payment->setAdditionalInformation(
                'skrill_status',
                \Skrill\Skrill\Model\Method\AbstractMethod::PENDING_STATUS
            );
            $payment->save();

            $this->deactiveQuote();
            $this->_getCheckoutSession()->setLastRealOrderId($this->_order->getIncrementId());
            $message = __('FRONTEND_MESSAGE_YOUR_ORDER').' '.
                $this->method->getShopName().' '.
                __('FRONTEND_MESSAGE_INPROCESS').' '.
                __('FRONTEND_MESSAGE_PLEASE_BACK_AGAIN');
            $this->messageManager->addWarning($message);
            $this->_redirect('checkout/onepage/success', ['_secure' => true]);
        }
    }
}
