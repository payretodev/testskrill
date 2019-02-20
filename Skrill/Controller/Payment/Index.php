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

class Index extends \Magento\Framework\App\Action\Action
{
    public $modelQuote = false;
    public $salesOrder = false;
    public $checkoutHelper;
    public $localeResolver;
    public $resultPageFactory;
    public $logger;
    public $helperCore;
    public $method;
    public $invoiceService;
    public $dbTransaction;
    public $salesEmailInvoice;
    public $salesEmailOrder;
    public $catalogSession;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Skrill\Skrill\Helper\Logger $logger
     * @param \Skrill\Skrill\Helper\Core $helperCore
     * @param \Magento\Sales\Model\Order $salesOrder
     * @param \Magento\Quote\Model\Quote $modelQuote
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\Transaction $dbTransaction
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $salesEmailInvoice
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $salesEmailOrder
     * @param \Magento\Catalog\Model\Session $catalogSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Skrill\Skrill\Helper\Logger $logger,
        \Skrill\Skrill\Helper\Core $helperCore,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order $salesOrder,
        \Magento\Quote\Model\Quote $modelQuote,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $dbTransaction,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $salesEmailInvoice,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $salesEmailOrder,
        \Magento\Catalog\Model\Session $catalogSession
    ) {
        parent::__construct($context);
        $this->checkoutHelper = $checkoutHelper;
        $this->localeResolver = $localeResolver;
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;
        $this->helperCore = $helperCore;
        $this->checkoutSession = $checkoutSession;
        $this->salesOrder = $salesOrder;
        $this->modelQuote = $modelQuote;
        $this->invoiceService = $invoiceService;
        $this->dbTransaction = $dbTransaction;
        $this->salesEmailInvoice = $salesEmailInvoice;
        $this->salesEmailOrder = $salesEmailOrder;
        $this->catalogSession = $catalogSession;
    }

    /**
     * get checkout session
     *
     * @return \Magento\Checkout\Model\Session
     */
    public function _getCheckoutSession()
    {
        return $this->checkoutHelper->getCheckout();
    }

    /**
     * get Quote from checkout session
     *
     * @return \Magento\Sales\Model\Quote
     *
     */
    public function _getQuote()
    {
        if (!$this->modelQuote) {
            $this->modelQuote = $this->_getCheckoutSession()->getQuote();
        }
        return $this->modelQuote;
    }

    /**
     * get last order object
     *
     * @return \Magento\Sales\Model\Order
     */
    public function _getOrder()
    {
        $order = $this->salesOrder;
        $order->load($this->_getCheckoutSession()->getLastOrderId());

        return $order;
    }

    /**
     * get an order based on increment id
     * @param  string $incrementId
     * @return \Magento\Sales\Model\Order
     */
    public function getOrderByIncerementId($incrementId)
    {
        $order = $this->salesOrder;
        $order->loadByIncrementId($incrementId);

        return $order;
    }

    /**
     * execute Payment
     */
    public function execute()
    {
        $this->logger->info('process generate payment form');
        $this->order = $this->_getOrder();
        $this->method = $this->order->getPayment()->getMethodInstance();
        $methodTitle = $this->method->getTitle();

        if ($this->order->getPayment()->getAdditionalInformation('is_payment_processed')) {
            $this->_redirect(
                'skrill/payment/handlereturn',
                [
                    'orderId' => $this->order->getIncrementId(),
                    '_secure' => true
                ]
            );
        }

        $paymentUrl = $this->catalogSession->getPaymentUrl();

        $transaction_id = $this->getRequest()->getParam('trn_id');
        $responseStatus = $this->validatePaymentProcess($this->order, $transaction_id);

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
                        'orderId' => $this->order->getIncrementId(),
                        '_secure' => true
                    ]
                );
            }
        }

        $display = $this->method->getGeneralConfig('display');

        if ($display == 'REDIRECT') {
            $this->_redirect($paymentUrl);
        }

        $resultPage = $this->resultPageFactory->create();
        $this->addBreadCrumbs($resultPage);

        $blockSkrill = $resultPage->getLayout()->getBlock('skrillPaymentForm');
        $blockSkrill->setPaymentUrl($paymentUrl);

        return $resultPage;
    }

    /**
     * validate payment during process
     * @param  Object $order
     * @param  string $transaction_id
     * @return array
     */
    public function validatePaymentProcess($order, $transaction_id)
    {
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $settings = $method->getSkrillSettings(strtolower($payment->getAdditionalInformation('skrill_currency')));
        $parameters['email'] = $settings['merchant_account'];
        $parameters['password'] = $settings['api_passwd'];
        $parameters['trn_id'] = $transaction_id;

        $responseStatus = $this->helperCore->getStatusTrn($parameters);

        return $responseStatus;
    }
    /**
     * Add breadcrumbs
     * @param \Magento\Framework\View\Result\Page $resultPage
     * @return void
     */
    public function addBreadCrumbs($resultPage)
    {
        $breadcrumbs = $resultPage->getLayout()->getBlock('breadcrumbs');
        $breadcrumbs->addCrumb(
            'home',
            [
                'label' => __('Home'),
                'title' => __('Home'),
                'link' => $this->_url->getUrl('')
            ]
        );
        $breadcrumbs->addCrumb(
            $this->method->getCode(),
            [
                'label' => $this->method->getTitle(),
                'title' => $this->method->getTitle()
            ]
        );
    }

    /**
     * redirect to checkout page when error or warning happen
     *
     * @param string $errorIdentifier
     * @param string $url
     * @return void
     *
     */
    public function redirectError($errorIdentifier, $url = 'checkout/cart')
    {
        $this->messageManager->addError(__($errorIdentifier));
        $this->_redirect($url, ['_secure' => true]);
    }

    /**
     * deactive quote
     *
     * @return void
     */
    public function deactiveQuote()
    {
        $quote = $this->modelQuote;
        $quote->loadActive($this->_getCheckoutSession()->getLastQuoteId());
        $quote->setReservedOrderId($this->salesOrder->getIncrementId());
        $quote->setIsActive(false)->save();
    }

    /**
     * get payment parameters
     *
     * @return array
     */
    public function getPaymentParameters()
    {
        $parameters = [];
        $settings = $this->method->getSkrillSettings();
        $billingAddress = $this->order->getBillingAddress();
        $currency = $this->checkoutSession->getQuote()->getQuoteCurrencyCode();

        $parameters['pay_to_email'] = $settings['merchant_account'];
        $parameters['recipient_description'] = $settings['recipient_desc'];
        $parameters['logo_url'] = $settings['logo_url'];

        if (isset($settings['merchant_email'])) {
            $parameters['status_url2'] = $settings['merchant_email'];
        }
        if ($this->method->getBrand() != 'FLEXIBLE') {
            $parameters['payment_methods'] = $this->method->getBrand();
        }

        $parameters['prepare_only'] = 1;
        $parameters['language'] = $this->method->getLangCode();

        $parameters['amount'] = $this->checkoutSession->getQuote()->getGrandTotal();
        $parameters['currency'] = $currency;
        $parameters['transaction_id'] = $this->order->getIncrementId().time();
        $parameters['detail1_description'] = "Order pay from " . $billingAddress->getEmail();
        $parameters['merchant_fields'] = 'platform,paymentkey';
        $parameters['platform'] = '90219005';
        $parameters['paymentkey'] = $this->generatePaymentKey($parameters);

        $parameters['pay_from_email'] = $billingAddress->getEmail();
        $parameters['firstname'] = $billingAddress->getFirstname();
        $parameters['lastname'] = $billingAddress->getLastname();
        $parameters['address'] = implode(' ', $billingAddress->getStreet());
        $parameters['city'] = $billingAddress->getCity();
        $parameters['postal_code'] = $billingAddress->getPostcode();
        $parameters['country'] = $this->helperCore->getCountryIso3($billingAddress->getCountryId());
        $parameters['detail1_description'] = "Order";
        $parameters['detail1_text'] = $this->order->getIncrementId();
        $parameters['detail2_description'] = "Order Amount";
        $parameters['detail2_text'] = $this->setFormatNumber($this->_getOrder()->getSubTotal()).' '.$currency;

        $shipping = $this->_getOrder()->getShippingAmount();
        if(!empty($shipping)){
            $parameters['detail3_description'] = "Shipping";
            $parameters['detail3_text'] = $this->setFormatNumber($shipping).' '.$currency;
        }

        $tax = $this->_getOrder()->getTaxAmount();
        if(!empty($tax)){
            $parameters['detail4_description'] = "Tax";
            $parameters['detail4_text'] = $this->setFormatNumber($tax).' '.$currency;
        }

        $parameters['cancel_url'] = $this->_url->getUrl(
            'skrill/payment/handlecancel',
            [
                'orderId' => $this->order->getIncrementId(),
                'trn_id' => $parameters['transaction_id'],
                '_secure' => true
            ]
        );
        $parameters['return_url'] = $this->_url->getUrl(
            'skrill/payment/handlereturn',
            [
                'orderId' => $this->order->getIncrementId(),
                '_secure' => true
            ]
        );

        if(version_compare($this->helperCore->getShopVersion(), '2.3.0', '<')) {
            $statusUrl = "handlestatus";
        } else {
            $statusUrl = "handlestatuscsrf";
        }

        $parameters['status_url'] = $this->_url->getUrl(
            'skrill/payment/' . $statusUrl,
            [
                'orderId' => $this->order->getIncrementId(),
                '_secure' => true
            ]
        );

        return $parameters;
    }

    /**
     * set formated number with 2 digits
     * @param string $number
     */
    public function setFormatNumber($number)
    {
        $number = (float) str_replace(',', '.', $number);
        return number_format($number, 2, '.', '');
    }

    /**
     * generate payment key
     * @param  array $parameters
     * @return string
     */
    public function generatePaymentKey($parameters)
    {
        $string = $parameters['transaction_id'].$parameters['amount'];

        return strtoupper(md5($string));
    }

    /**
     * generate Md5sig
     * @param \Magento\Sales\Model\Order $order
     * @param  array $responseStatus
     * @return string
     */
    public function generateMd5sigByOrder($order, $responseStatus)
    {
        $method = $order->getPayment()->getMethodInstance();
        $settings = $method->getSkrillSettings(strtolower($responseStatus['mb_currency']));

        $string = $settings['merchant_id'].
            $responseStatus['transaction_id'].
            strtoupper($settings['secret_word']).
            $responseStatus['mb_amount'].
            $responseStatus['mb_currency'].
            $responseStatus['status'];

        return strtoupper(md5($string));
    }

    /**
     * is fraud or not
     * @param  array $responseStatus
     * @return boolean
     */
    public function isFraud($responseStatus)
    {
        return !($responseStatus['paymentkey'] == $this->generatePaymentKey($responseStatus));
    }

    /**
     * is payment signature equals generated signature
     * @param string $paymentSignature
     * @param string $generatedSignature
     * @return boolean
     */
    public function isPaymentSignatureEqualsGeneratedSignature($paymentSignature, $generatedSignature)
    {
        return $paymentSignature == $generatedSignature;
    }

    /**
     * create invoice
     *
     * @param \Magento\Sales\Model\Order $order
     * @return void
     *
     */
    public function createInvoice($order)
    {
        $invoiceService = $this->invoiceService;

        $invoice = $invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
        $invoice->register();
        $invoice->getOrder()->setCustomerNoteNotify(false);
        $invoice->getOrder()->setIsInProcess(true);

        $transactionSave = $this->dbTransaction;
        $transactionSave->addObject($invoice)->addObject($invoice->getOrder())->save();

        $invoiceSender = $this->salesEmailInvoice;
        $invoiceSender->send($invoice);
    }

    /**
     * add order additional information
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $responseStatus
     * @return void
     */
    public function saveAdditionalInformation($order, $responseStatus)
    {
        $payment = $order->getPayment();
        if (isset($responseStatus['transaction_id'])) {
            $payment->setAdditionalInformation('skrill_transaction_id', $responseStatus['transaction_id']);
        }
        if (isset($responseStatus['mb_transaction_id'])) {
            $payment->setAdditionalInformation('skrill_mb_transaction_id', $responseStatus['mb_transaction_id']);
        }
        if (isset($responseStatus['mb_amount'])) {
            $payment->setAdditionalInformation('skrill_mb_amount', $responseStatus['mb_amount']);
        }
        if (isset($responseStatus['ip_country'])) {
            $payment->setAdditionalInformation('skrill_ip_country', $responseStatus['ip_country']);
        }
        if (isset($responseStatus['status'])) {
            $payment->setAdditionalInformation('skrill_status', $responseStatus['status']);
        }
        if (isset($responseStatus['payment_type'])) {
            $payment->setAdditionalInformation('skrill_payment_type', $responseStatus['payment_type']);
        }
        if (isset($responseStatus['payment_instrument_country'])) {
            $payment->setAdditionalInformation('skrill_issuer_country', $responseStatus['payment_instrument_country']);
        }
        if (isset($responseStatus['pay_from_email'])) {
            $payment->setAdditionalInformation('skrill_pay_from_email', $responseStatus['pay_from_email']);
        }
        if (isset($responseStatus['pay_to_email'])) {
            $payment->setAdditionalInformation('pay_to_email', $responseStatus['pay_to_email']);
        }
        
        $payment->save();

        $this->setAdditionalInformationCurrency($payment, $responseStatus);
    }

    /**
     * function for save additional information (currency and mb_currency)
     * @param object $payment
     * @param array $responseStatus
     */
    protected function setAdditionalInformationCurrency($payment, $responseStatus)
    {
        if (isset($responseStatus['currency'])) {
            $payment->setAdditionalInformation('skrill_currency', $responseStatus['currency']);
        }
        
        if (isset($responseStatus['mb_currency'])) {
            $payment->setAdditionalInformation('skrill_mb_currency', $responseStatus['mb_currency']);
        }

        $payment->save();
    }

    /**
     * get response status
     * @return array
     */
    public function getResponseStatus()
    {
        $responseStatus = [];
        foreach ($this->getRequest()->getParams() as $responseName => $responseValue) {
            $responseStatus[strtolower($responseName)] = $responseValue;
        }
        return $responseStatus;
    }

    /**
     * validate payment
     * @param  \Magento\Sales\Model\Order $order
     * @param  array $responseStatus
     * @return void
     */
    public function validatePayment($order, $responseStatus)
    {
        $this->logger->info('validate payment');

        if ($responseStatus['payment_type'] == 'NGP') {
            $responseStatus['payment_type'] = 'OBT';
        }
        $currentStatus = $order->getPayment()->getAdditionalInformation('skrill_status');
        $this->saveAdditionalInformation($order, $responseStatus);

        $isFraud = $this->isFraud($responseStatus);
        $isFraudLog = ($isFraud) ? 'true' : 'false';
        $this->logger->info('is Fraud : '.$isFraudLog);

        $generatedSignaturedByOrder = $this->generateMd5sigByOrder($order, $responseStatus);
        $isCredentialValid = $this->isPaymentSignatureEqualsGeneratedSignature(
            $responseStatus['md5sig'],
            $generatedSignaturedByOrder
        );
        $isCredentialValidLog = ($isCredentialValid) ? 'true' : 'false';
        $this->logger->info('is credential valid : '.$isCredentialValidLog);

        if ($isFraud) {
            $this->processFraud($order, $responseStatus);
        } elseif (!$isCredentialValid) {
            $this->processNotVerified($order, $responseStatus);
        } else {
            if (!isset($currentStatus)) {
                $this->processPayment($order, $responseStatus);
            } else {
                if ($currentStatus == \Skrill\Skrill\Model\Method\AbstractMethod::PENDING_STATUS) {
                    $this->updateOrderStatus($order, $responseStatus);
                }
            }
        }
    }

    /**
     * update order status
     * @param  \Magento\Sales\Model\Order $order
     * @param  array $responseStatus
     * @return void
     */
    public function updateOrderStatus($order, $responseStatus)
    {
        $this->logger->info('update order status');

        if ($responseStatus['status'] == \Skrill\Skrill\Model\Method\AbstractMethod::PROCESSED_STATUS) {
            $this->createInvoice($order);
            $comment = $this->helperCore->getComment($responseStatus);
            $order->addStatusHistoryComment($comment, 'payment_accepted')->save();
            $this->logger->info('update order status to processed');
        } elseif ($responseStatus['status'] == \Skrill\Skrill\Model\Method\AbstractMethod::FAILED_STATUS) {
            if (isset($responseStatus['failed_reason_code'])) {
                $order->getPayment()->setAdditionalInformation(
                    'failed_reason_code',
                    $responseStatus['failed_reason_code']
                );
            }
            $comment = $this->helperCore->getComment($responseStatus);
            $order->addStatusHistoryComment($comment, false);
            $order->cancel();
            $this->logger->info('update order status to failed');
        }
    }

    /**
     * process fraud
     * @param  \Magento\Sales\Model\Order $order
     * @param  array $responseStatus
     * @return void
     */
    public function processFraud($order, $responseStatus)
    {
        $this->logger->info('process Fraud');

        $comment = $this->helperCore->getComment($responseStatus, 'fraud');
        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
            ->setStatus(\Magento\Sales\Model\Order::STATUS_FRAUD)
            ->save();
        $order->getPayment()->setAdditionalInformation(
            'skrill_status',
            \Skrill\Skrill\Model\Method\AbstractMethod::FRAUD_STATUS
        )->save();
        $order->addStatusHistoryComment($comment, false)->save();
        $this->deactiveQuote();
    }

    /**
     * process not verified
     * @param  \Magento\Sales\Model\Order $order
     * @param  array $responseStatus
     * @return void
     */
    public function processNotVerified($order, $responseStatus)
    {
        $this->logger->info('process not verified');

        $comment = $this->helperCore->getComment($responseStatus, 'not verified');
        $order->setState(\Magento\Sales\Model\Order::STATE_NEW)
            ->setStatus(\Skrill\Skrill\Model\Method\AbstractMethod::NOT_VERIFIED)
            ->save();
        $order->getPayment()->setAdditionalInformation(
            'skrill_status',
            \Skrill\Skrill\Model\Method\AbstractMethod::INVALIDCREDENTIAL_STATUS
        )->save();
        $order->addStatusHistoryComment($comment, false)->save();
        $this->deactiveQuote();
    }

    /**
     * process payment
     * @param  \Magento\Sales\Model\Order $order
     * @param  array $responseStatus
     * @return void
     */
    public function processPayment($order, $responseStatus)
    {
        $this->logger->info('process payment');
        $comment = $this->helperCore->getComment($responseStatus);
        $orderSender = $this->salesEmailOrder;
        $order->getPayment()->setAdditionalInformation('is_payment_processed', true)->save();
        
        if ($responseStatus['status'] == \Skrill\Skrill\Model\Method\AbstractMethod::PENDING_STATUS) {
            $this->logger->info('process payment with status pending');
            $orderSender->send($order);
            $order->setState(\Magento\Sales\Model\Order::STATE_NEW)
                ->setStatus(\Skrill\Skrill\Model\Method\AbstractMethod::PENDING)
                ->save();
            $order->addStatusHistoryComment($comment, false)->save();
        } elseif ($responseStatus['status'] == \Skrill\Skrill\Model\Method\AbstractMethod::PROCESSED_STATUS) {
            $this->logger->info('process payment with status processed');
            $orderSender->send($order);
            $this->createInvoice($order);
            $order->addStatusHistoryComment($comment, false)->save();
        } else {
            $this->logger->info('process payment with status failed');
            if (isset($responseStatus['failed_reason_code'])) {
                $order->getPayment()->setAdditionalInformation(
                    'failed_reason_code',
                    $responseStatus['failed_reason_code']
                )->save();
            }
            $order->addStatusHistoryComment($comment, false)->save();
            $order->cancel()->save();
        }
    }
}
