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

class HandleStatus extends \Skrill\Skrill\Controller\Payment\Index
{

    /**
     * execute payment handlestatus
     */
    public function execute()
    {
        $status = $this->getRequest()->getParam('status');

        $this->logger->info('process status url with status : '.$status);

        if (isset($status)) {
            $responseStatus = $this->getResponseStatus();
            $this->logger->info("status url response : ".
                json_encode($responseStatus));

            $orderId = $this->getRequest()->getParam('orderId');
            $this->_order = $this->getOrderByIncerementId($orderId);
            $this->method = $this->_order->getPayment()->getMethodInstance();

            $this->validatePayment($this->_order, $responseStatus);
        }
    }
}
