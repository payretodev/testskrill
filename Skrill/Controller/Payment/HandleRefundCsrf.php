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
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class HandleReturnCsrf extends \Skrill\Skrill\Controller\Payment\Index implements CsrfAwareActionInterface
{
    /**
     * execute payment handlereturn
     */
    public function execute()
    {
        $status = $this->getRequest()->getParam('status');

        $this->logger->info('process refund status url with status with csrf: '.$status);

        if (isset($status)) {
            $responseStatus = $this->getResponseStatus();
            $this->logger->info('refund status url response : '.
                json_encode($responseStatus));

            $orderId = $this->getRequest()->getParam('orderId');
            $this->_order = $this->getOrderByIncerementId($orderId);

            if ($responseStatus['status'] == \Skrill\Skrill\Model\Method\AbstractMethod::PROCESSED_STATUS) {
                $responseStatus['status'] = \Skrill\Skrill\Model\Method\AbstractMethod::REFUNDED_STATUS;
            } elseif ($responseStatus['status'] == \Skrill\Skrill\Model\Method\AbstractMethod::PENDING_STATUS) {
                $responseStatus['status'] = \Skrill\Skrill\Model\Method\AbstractMethod::REFUNDPENDING_STATUS;
            } else {
                $responseStatus['status'] = \Skrill\Skrill\Model\Method\AbstractMethod::REFUNDFAILED_STATUS;
            }
            $this->_order->getPayment()->setAdditionalInformation(
                'skrill_refund_status',
                $responseStatus['status']
            )->save();
            $comment = $this->helperCore->getComment($responseStatus, 'refundStatus');
            $this->_order->addStatusHistoryComment($comment, false)->save();

            $this->logger->info('process refund status url : success');
        }
    }

    /**
     * Create exception in case CSRF validation failed.
     * Return null if default exception will suffice.
     *
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Perform custom request validation.
     * Return null if default validation is needed.
     *
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
