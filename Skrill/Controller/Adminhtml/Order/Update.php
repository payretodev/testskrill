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
namespace Skrill\Skrill\Controller\Adminhtml\Order;

class Update extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Sales\Model\Order
     */
    private $order;

    /**
     * @var \Skrill\Skrill\Controller\Payment\Index
     */
    private $payment;

    /**
     * @var \Skrill\Skrill\Helper\Logger
     */
    private $logger;

    /**
     * @var \Skrill\Skrill\Helper\Core
     */
    private $helperCore;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Sales\Model\Order $order
     * @param \Skrill\Skrill\Controller\Payment\Index $payment
     * @param \Skrill\Skrill\Helper\Logger $logger
     * @param \Skrill\Skrill\Helper\Core $helperCore
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Sales\Model\Order $order,
        \Skrill\Skrill\Controller\Payment\Index $payment,
        \Skrill\Skrill\Helper\Logger $logger,
        \Skrill\Skrill\Helper\Core $helperCore
    ) {
        parent::__construct($context);
        $this->order = $order;
        $this->payment = $payment;
        $this->logger = $logger;
        $this->helperCore = $helperCore;
    }

    /**
     * execute update order
     *
     */
    public function execute()
    {
        $this->logger->info('process update order from backend');
        $orderId = $this->getRequest()->getParam('order_id');
        $order = $this->order->load($orderId);
        $payment = $order->getPayment();
        $paymentMethod = $payment->getMethod();
        $method = $payment->getMethodInstance();

        $settings = $method->getSkrillSettings(strtolower($payment->getAdditionalInformation('skrill_currency')));
        $parameters['email'] = $settings['merchant_account'];
        $parameters['password'] = $settings['api_passwd'];
        $parameters['trn_id'] = $payment->getAdditionalInformation('skrill_transaction_id');

        $responseStatus = $this->helperCore->getStatusTrn($parameters);

        if ($responseStatus === 'GENERAL_ERROR') {
            $this->logger->info('process update order from backend : failed');
            $this->redirectErrorOrderDetail('ERROR_UPDATE_BACKEND', $orderId);
        } elseif ($responseStatus == 'ACCOUNT_LOCKED') {
            $this->logger->info('process update order from backend : failed - account locked');
            $this->redirectErrorOrderDetail('ERROR_UPDATE_LOCKED_BACKEND', $orderId);
        } elseif ($responseStatus == 'CANNOT_LOGIN') {
            $this->logger->info('process update order from backend : failed - wrong api password');
            $this->redirectErrorOrderDetail('ERROR_UPDATE_MQI_BACKEND', $orderId);
        } else {
            $this->logger->info('responseStatus : '.
                json_encode($responseStatus));
            $generatedSignaturedByOrder = $this->payment->generateMd5sigByOrder($order, $responseStatus);
            $isCredentialValid = $this->payment->isPaymentSignatureEqualsGeneratedSignature(
                $responseStatus['md5sig'],
                $generatedSignaturedByOrder
            );
            if ($responseStatus['mb_currency'] != $responseStatus['currency']) {
                $errorWrongCurrency = __('ERROR_WRONG_CURRENCY1').
                    $responseStatus['currency'].
                    __('ERROR_WRONG_CURRENCY2').
                    $responseStatus['mb_currency'];

                $this->messageManager->addError($errorWrongCurrency);
                $this->_redirect('sales/order/view', ['order_id' => (int)$orderId]);
            } elseif (!$isCredentialValid) {
                $this->logger->info('process update order from backend : not verified');
                $this->redirectErrorOrderDetail('ERROR_UPDATE_NOTVERIFIED_BACKEND', $orderId);
            } else {
                $invoiceIds = $order->getInvoiceCollection()->getAllIds();
                if (empty($invoiceIds)
                    && $responseStatus['status'] == \Skrill\Skrill\Model\Method\AbstractMethod::PROCESSED_STATUS
                ) {
                    $this->payment->createInvoice($order);
                }

                $this->payment->saveAdditionalInformation($order, $responseStatus);
                $comment = $this->helperCore->getComment($responseStatus);
                $order->addStatusHistoryComment($comment, false);
                $order->save();
                $this->logger->info('process update order from backend : success');
                $this->redirectSuccessOrderDetail('SUCCESS_GENERAL_UPDATE_PAYMENT', $orderId);
            }
        }
    }

    /**
     * redirect to error page when update a payment status
     * @param  string  $errorIdentifier
     * @param  string  $orderId
     * @param  boolean|string $detailError
     * @param  string  $url
     * @return void
     */
    private function redirectErrorOrderDetail(
        $errorIdentifier,
        $orderId,
        $detailError = false,
        $url = 'sales/order/view'
    ) {
        $message = __($errorIdentifier);
        if ($detailError) {
            $message .= ' : ' . __($detailError);
        }
        $this->messageManager->addError($message);
        $this->_redirect($url, ['order_id' => (int)$orderId]);
    }

    /**
     * redirect to success page when update a payment status
     * @param  string $successIdentifier
     * @param  string $orderId
     * @param  string $url
     * @return void
     */
    private function redirectSuccessOrderDetail($successIdentifier, $orderId, $url = 'sales/order/view')
    {
        $this->messageManager->addSuccess(__($successIdentifier));
        $this->_redirect($url, ['order_id' => (int)$orderId]);
    }
}
