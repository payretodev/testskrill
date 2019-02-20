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
namespace Skrill\Skrill\Model\Method;

abstract class AbstractMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = false;

    /**
     * @var string
     */
    public $_code= 'skrill_abstract';

    /**
     * @var string
     */
    public $brand = '';

    /**
     * @var string
     */
    public $methodTitle = '';

    /**
     * @var string
     */
    public $logo = '';

    /**
     * @var string
     */
    protected $storeManager;

    const DISALLOWED_COUNTRIES = 'AF,CU,ER,IR,IQ,JP,KG,LY,KP,SD,SS,SY';

    const PENDING = 'pending';
    const NOT_VERIFIED = 'not_verified';
    const PAYMENT_PA = 'payment_pa';
    const PAYMENT_ACCEPTED = 'payment_accepted';

    const PROCESSED_STATUS = '2';
    const PENDING_STATUS = '0';
    const FAILED_STATUS = '-2';
    const REFUNDED_STATUS = '-4';
    const REFUNDFAILED_STATUS = '-5';
    const REFUNDPENDING_STATUS = '-6';
    const FRAUD_STATUS = '-7';
    const INVALIDCREDENTIAL_STATUS = '-8';
    
    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Skrill\Skrill\Helper\Logger $skrillLogger
     * @param \Skrill\Skrill\Helper\Core $helperCore
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptorInterface
     * @param \Magento\Framework\Locale\ResolverInterface $localResolver
     * @param \Magento\Framework\Url $url
     * @param \Magento\Framework\HTTP\Header $httpHeader
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Skrill\Skrill\Helper\Logger $skrillLogger,
        \Skrill\Skrill\Helper\Core $helperCore,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Encryption\EncryptorInterface $encryptorInterface,
        \Magento\Framework\Locale\ResolverInterface $localResolver,
        \Magento\Framework\Url $url,
        \Magento\Framework\HTTP\Header $httpHeader,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->skrillLogger = $skrillLogger;
        $this->helperCore = $helperCore;
        $this->storeManager = $storeManager;
        $this->encryptorInterface = $encryptorInterface;
        $this->localResolver = $localResolver;
        $this->url = $url;
        $this->httpHeader = $httpHeader;
    }

    /**
     * get Helper Core
     * @return \Skrill\Skrill\Helper\Core
     */
    public function getHelperCore()
    {
        return $this->helperCore;
    }

    /**
     *
     * @param  string $paymentAction
     * @param  object $stateObject
     * @return object
     */
    public function initialize($paymentAction, $stateObject)
    {
        switch ($paymentAction) {
            case self::ACTION_ORDER:
                $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
                $stateObject->setStatus($this->getConfigData('order_status'));
                $stateObject->setIsNotified(false);
                break;
            default:
                break;
        }
    }

    /**
     * is payment method avaiable or not
     *
     * @param  \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return boolean
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $isAvailable = parent::isAvailable($quote);

        if ($isAvailable) {
            $showSeparately = $this->getConfigData('show_separately');
            if (!$showSeparately) {
                return false;
            }
        }

        return $isAvailable;
    }

    /**
     * is payment method can use for country
     *
     * @param  string $country
     * @return boolean|string
     */
    public function canUseForCountry($country)
    {
        $canUseForCountry = parent::canUseForCountry($country);

        if ($canUseForCountry) {
            if (!isset($country)) {
                return false;
            }
            $disallowedCountries = explode(',', self::DISALLOWED_COUNTRIES);
            if (in_array($country, $disallowedCountries)) {
                return false;
            }
        }

        return $canUseForCountry;
    }

    /**
     * check if all cards active or not
     *
     * @return boolean
     */
    public function isAllCardsActive()
    {
        $active = $this->getSpecificConfig('payment/skrill_acc/active');
        $showSeparately = $this->getSpecificConfig('payment/skrill_acc/show_separately');
        if ($active && $showSeparately) {
            return true;
        }
        return false;
    }

    /**
     * get a quote
     * @return \Magento\Sales\Model\Quote
     */
    public function getQuote(\Magento\Checkout\Model\Session $checkoutSession)
    {
        return $checkoutSession->getQuote();
    }

    /**
     * get an order
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        $infoInstance = $this->getInfoInstance();

        return $infoInstance->getOrder();
    }

    /**
     * get a title
     * @return string
     */
    public function getTitle()
    {
        return __($this->methodTitle);
    }

    /**
     * get a logo
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * get an order place redirect URL
     * @return boolean | string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return true;
    }

    /**
     * get the general configuration
     * @param  string $field
     * @param  string $storeId
     * @return string
     */
    public function getGeneralConfig($field, $storeId = null)
    {
        if (null === $storeId) {
            $storeId = $this->getStore();
        }

        $path = 'general/skrill_settings/' . $field;
        
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * get the specific configuration
     * @param  string $field
     * @param  string $storeId
     * @return string
     */
    public function getSpecificConfig($field, $storeId = null)
    {
        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        return $this->_scopeConfig->getValue($field, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Retrieve skrill settings
     *
     * @return array
     */
    public function getSkrillSettings($currency = false)
    {

        if (!$currency) {
            $currency = strtolower($this->storeManager->getStore()->getCurrentCurrencyCode());
        }
        
        if ($this->isMulticurrencyEnabled($currency)) {
            $settings = $this->getSkrillMulticurrencySettings($currency);
        } else {
            $settings = $this->getSkrillGeneralSettings();
        }
        
        return $settings;
    }

    /**
     * is Multicurrency enabled for current currency
     * @param  string  $currency
     * @return boolean
     */
    public function isMulticurrencyEnabled($currency)
    {
        $multicurrencyEnable = $this->getSpecificConfig(
            'general/skrill_multicurrency_settings/multicurrency_active_'.$currency
        );

        if ($multicurrencyEnable == '1') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Retrieve the Multi Currency skrill settings
     * @param string $currency
     * @return array
     */
    public function getSkrillMulticurrencySettings($currency)
    {
        $settings = [
            'recipient_desc'  => $this->getGeneralConfig('recipient_desc'),
            'logo_url'  => $this->getGeneralConfig('logo_url'),
            'shop_url'  => $this->getGeneralConfig('shop_url'),
            'merchant_email'  => $this->getGeneralConfig('merchant_email'),
            'merchant_id'  => $this->getSpecificConfig('general/skrill_multicurrency_settings/merchant_id_'.$currency),
            'merchant_account'  => $this->getSpecificConfig(
                'general/skrill_multicurrency_settings/merchant_account_'.$currency
            ),
        ];

        $apiPassword = $this->encryptorInterface->decrypt(
            $this->getSpecificConfig('general/skrill_multicurrency_settings/api_passwd_'.$currency)
        );
        $secretWord = $this->encryptorInterface->decrypt(
            $this->getSpecificConfig('general/skrill_multicurrency_settings/secret_word_'.$currency)
        );

        $settings['api_passwd'] = md5($apiPassword);
        $settings['secret_word'] = md5($secretWord);

        return $settings;
    }

    /**
     * Retrieve the general skrill settings
     *
     * @return array
     */
    public function getSkrillGeneralSettings()
    {
        $settings = [
            'merchant_id'  => $this->getGeneralConfig('merchant_id'),
            'merchant_account'  => $this->getGeneralConfig('merchant_account'),
            'recipient_desc'  => $this->getGeneralConfig('recipient_desc'),
            'logo_url'  => $this->getGeneralConfig('logo_url'),
            'shop_url'  => $this->getGeneralConfig('shop_url'),
            'merchant_email'  => $this->getGeneralConfig('merchant_email')
        ];

        $apiPassword = $this->encryptorInterface->decrypt($this->getGeneralConfig('api_passwd'));
        $secretWord = $this->encryptorInterface->decrypt($this->getGeneralConfig('secret_word'));

        $settings['api_passwd'] = md5($apiPassword);
        $settings['secret_word'] = md5($secretWord);

        return $settings;
    }

    /**
     * get a brand
     * @return string
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     *
     * @param  \Magento\Payment\Model\InfoInterface $payment
     * @param  float $amount
     * @return $this
     */
    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this;
    }

    /**
     * get a language code
     * @return string
     */
    public function getLangCode()
    {
        $locale = explode('_', $this->localResolver->getLocale());
        if (isset($locale[0])) {
            return strtoupper($locale[0]);
        }
        return 'EN';
    }

    /**
     * capture a payment
     * @param  \Magento\Payment\Model\InfoInterface $payment
     * @param  float $amount
     * @return $this
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $payment->setStatus('APPROVED')
                ->setTransactionId($payment->getAdditionalInformation('skrill_transaction_id'))
                ->setIsTransactionClosed(0)->save();

        return $this;
    }

    /**
     * get refund status url
     * @param  string $orderId
     * @return string
     */
    protected function getRefundStatusUrl($orderId)
    {

        if(version_compare($this->helperCore->getShopVersion(), '2.3.0', '<')){
            $refundUrl = "handlerefund";
        } else {
            $refundUrl = "handlerefundcsrf";
        }

        $refundStatusUrl = $this->url->getUrl(
            'skrill/payment/' . $refundUrl,
            [
                'orderId' => $orderId,
                '_secure' => true
            ]
        );

        return $refundStatusUrl;
    }

    /**
     * refund a payment
     * @param  \Magento\Payment\Model\InfoInterface $payment
     * @param  float $amount
     * @return $this
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->skrillLogger->info('process refund online');
        if ($payment->getAdditionalInformation('skrill_refund_status') == self::REFUNDPENDING_STATUS) {
            throw new \Magento\Framework\Exception\LocalizedException(__('BACKEND_GENERAL_WAIT_REFUND_PENDING'));
        }

        $orderId = $payment->getOrder()->getIncrementId();
        $skrillSettings = $this->getSkrillSettings(strtolower($payment->getAdditionalInformation('skrill_currency')));
        $parameters['email'] = $skrillSettings['merchant_account'];
        $parameters['password'] = $skrillSettings['api_passwd'];
        $parameters['mb_transaction_id'] = $payment->getAdditionalInformation('skrill_mb_transaction_id');
        $parameters['amount'] = $payment->getAdditionalInformation('skrill_mb_amount');
        $parameters['refund_status_url'] = $this->getRefundStatusUrl($orderId);

        $refundResponse = $this->getHelperCore()->doRefund($parameters);

        if ($refundResponse == 'CANNOT_LOGIN') {
            throw new \Magento\Framework\Exception\LocalizedException(__('ERROR_UPDATE_MQI_BACKEND'));
        }
        if ($refundResponse == 'ACCOUNT_LOCKED') {
            throw new \Magento\Framework\Exception\LocalizedException(__('ERROR_UPDATE_LOCKED_BACKEND'));
        }
        if ($refundResponse == 'GENERAL_ERROR') {
            throw new \Magento\Framework\Exception\LocalizedException(__('ERROR_GENERAL_REFUND_PAYMENT'));
        }

        $status = (string) $refundResponse->status;
        $mb_trans_id = (string) $refundResponse->mb_transaction_id;

        if ($status == self::PROCESSED_STATUS) {
            $this->skrillLogger->info('process refund online with status processed');
            $payment->setAdditionalInformation('skrill_refund_status', self::REFUNDED_STATUS);
            $payment->setTransactionId($mb_trans_id)
                    ->setIsTransactionClosed(0)->save();
            $response['status'] = self::REFUNDED_STATUS;
            $comment = $this->getHelperCore()->getComment($response);
            $payment->getOrder()->addStatusHistoryComment($comment, false)->save();
        } elseif ($status == self::PENDING_STATUS) {
            $this->skrillLogger->info('process refund online with status pending');
            throw new \Magento\Framework\Exception\LocalizedException(__('SUCCESS_GENERAL_REFUND_PAYMENT_PENDING'));
        } else {
            $this->skrillLogger->info('process refund online with status failed');
            throw new \Magento\Framework\Exception\LocalizedException(__('ERROR_GENERAL_REFUND_PAYMENT'));
        }

        return $this;
    }

    /**
     * get shop name
     *
     * @return string
     */
    public function getShopName()
    {
        $shopName = $this->getSpecificConfig('general/store_information/name');
        if (empty($shopName)) {
            return $this->httpHeader->getHttpHost();
        }
        return $shopName;
    }
}
