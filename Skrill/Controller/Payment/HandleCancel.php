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

class HandleCancel extends \Skrill\Skrill\Controller\Payment\Index
{
    /**
     * execute payment handlecancel
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('orderId');
        $this->_order = $this->getOrderByIncerementId($orderId);

        $transaction_id = $this->getRequest()->getParam('trn_id');
        $responseStatus = $this->validatePaymentProcess($this->_order, $transaction_id);

        if (isset($responseStatus['status'])) {
            if ($responseStatus['status'] == \Skrill\Skrill\Model\Method\AbstractMethod::FAILED_STATUS) {
                $failedReasonCode = '';
                if (isset($responseStatus['failed_reason_code'])) {
                    $failedReasonCode = $responseStatus['failed_reason_code'];
                }
                $this->redirectError($this->helperCore->getSkrillErrorMapping($failedReasonCode));
            } else {
                $this->_redirect(
                    'skrill/payment/handlereturn',
                    [
                        'orderId' => $orderId,
                        '_secure' => true
                    ]
                );
            }
        }
        $this->logger->info('process cancel url');

        $this->_order->cancel()->save();
        $this->redirectError('ERROR_GENERAL_CANCEL');
    }
}
