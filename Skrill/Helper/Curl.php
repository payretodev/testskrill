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
namespace Skrill\Skrill\Helper;

class Curl extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $http;
    private $logger;
    private $curlFactory;

    /**
     * [__construct description]
     * @param \Magento\Framework\App\Helper\Context       $context
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Skrill\Skrill\Helper\Logger                $logger
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Skrill\Skrill\Helper\Logger $logger
    ) {
        parent::__construct($context);
        $this->curlFactory = $curlFactory;
        $this->logger = $logger;
    }

    /**
     * get a response from the gateway
     * @param  boolean $isJsonDecoded
     * @return string | boolean
     */
    public function getResponse($isJsonDecoded = false)
    {
        $response = $this->http->read();
        $responseCode = \Zend_Http_Response::extractCode($response);
        $responseBody = \Zend_Http_Response::extractBody($response);
        $this->http->close();

        if ($responseCode == 200 || $responseCode == 202 || $responseCode == 400) {
            $this->logger->info(
                'response from gateway : '.
                json_encode($responseBody)
            );
            if ($isJsonDecoded) {
                return json_decode($responseBody, true);
            }
            return $responseBody;
        }

        return false;
    }

    /**
     * send request to the gateway
     *
     * @param string $url
     * @param string $request
     * @param boolean $isJsonDecoded
     * @return string | boolean
     */
    public function sendRequest($url, $request, $isJsonDecoded = false)
    {
        $this->http = $this->curlFactory->create();
        $this->http->setConfig(['verifypeer' => false]);
        $headers = ['Content-type: application/x-www-form-urlencoded;charset=UTF-8'];
        $this->http->write(\Zend_Http_Client::POST, $url, $http_ver = '1.1', $headers, $request);

        return $this->getResponse($isJsonDecoded);
    }
}
