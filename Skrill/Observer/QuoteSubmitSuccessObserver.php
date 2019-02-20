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

class QuoteSubmitSuccessObserver implements ObserverInterface
{

    /**
     * @var \Magento\Backend\Model\Auth
     */
    private $auth;
    
    public function __construct(\Magento\Backend\Model\Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
    
        $isAdminLoggedIn = $this->auth->getUser();
        if (!$isAdminLoggedIn) {
            $quote = $observer->getEvent()->getQuote();
            $paymentMethod = $quote->getPayment()->getMethod();
            if (strpos($paymentMethod, 'skrill') !== false) {
                $quote->setIsActive(true);
                $quote->setReservedOrderId(null);
            }
        }
    }
}
