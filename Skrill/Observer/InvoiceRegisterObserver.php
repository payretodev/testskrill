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
 * @copyright   Copyright (c) 2015 Skrill
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Skrill\Skrill\Observer;

use Magento\Framework\Event\ObserverInterface;

class InvoiceRegisterObserver implements ObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $paymentMethod = $order->getPayment()->getMethod();
        if (strpos($paymentMethod, 'skrill') !== false) {
            $order->setStatus(\Skrill\Skrill\Model\Method\AbstractMethod::PAYMENT_ACCEPTED);
            $order->addStatusToHistory(\Skrill\Skrill\Model\Method\AbstractMethod::PAYMENT_ACCEPTED, '', true)->save();
        }
    }
}
