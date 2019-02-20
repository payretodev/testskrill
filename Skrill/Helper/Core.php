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

class Core extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $paymentUrl = 'https://pay.skrill.com';
    private $queryUrl = 'https://www.skrill.com/app/query.pl';
    private $refundUrl = 'https://www.skrill.com/app/refund.pl';

    /**
     * [__construct description]
     * @param \Magento\Framework\App\Helper\Context       $context
     * @param \Skrill\Skrill\Helper\Logger                $logger
     * @param \Skrill\Skrill\Helper\Curl                  $curl
     * @param \Magento\Framework\Locale\TranslatedLists   $translatedList
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Skrill\Skrill\Helper\Logger $logger,
        \Skrill\Skrill\Helper\Curl $curl,
        \Magento\Framework\Locale\TranslatedLists $translatedList
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->curl = $curl;
        $this->translatedList = $translatedList;
    }

    /**
     * Get payment URL
     * @return string
     */
    public function getPaymentUrl()
    {
        return $this->paymentUrl;
    }

    /**
     * Get query URL
     * @return string
     */
    public function getQueryUrl()
    {
        return $this->queryUrl;
    }

    /**
     * Get refund URL
     * @return string
     */
    public function getRefundUrl()
    {
        return $this->refundUrl;
    }

    /**
     * get sid
     * @param  array $parameters
     * @return string
     */
    public function getSid($parameters)
    {
        $url = $this->getPaymentUrl();

        $this->logger->info('get Sid URL : '.$url);
        $this->logger->info(
            'get Sid Parameters : '.
            json_encode($parameters)
        );

        $request = http_build_query($parameters, '', '&');

        return $this->curl->sendRequest($url, $request);
    }

    /**
     * send request to Skrill by action
     * @param  string $url
     * @param  string $action
     * @param  array $parameters
     * @return string|boolean
     */
    public function doAction($url, $action, $parameters)
    {
        $parameters['action'] = $action;

        $parametersLog = $parameters;
        $parametersLog['password'] = '*****';

        $this->logger->info('do action : '.$action);
        $this->logger->info('do action URL : '.$url);
        $this->logger->info(
            'do action parameters : '.
            json_encode($parametersLog)
        );

        $request = http_build_query($parameters, '', '&');

        $response = $this->curl->sendRequest($url, $request);

        return $response;
    }

    /**
     * get payment status from Skrill
     * @param  array $parameters
     * @return array|boolean
     */
    public function getStatusTrn($parameters)
    {
        $url = $this->getQueryUrl();

        // check status_trn 3 times if no response.
        for ($i=0; $i < 3; $i++) {
            $response = true;
            try {
                $result = $this->doAction($url, 'status_trn', $parameters);
            } catch (\Exception $e) {
                $response = false;
            }
            if ($response && $result) {
                $resultCode = (int) substr($result, 0, 3);
                if ($resultCode == 401) {
                    if (strpos($result, 'Cannot login') !== false) {
                        return 'CANNOT_LOGIN';
                    } elseif (strpos($result, 'Your account is currently locked') !== false) {
                        return 'ACCOUNT_LOCKED';
                    }
                    return 'GENERAL_ERROR';
                }
                return $this->setResponseToArray($result);
            }
        }
        return 'GENERAL_ERROR';
    }

    /**
     * set response from string to array
     * @param  array $response
     * @return boolean | array
     */
    public function setResponseToArray($response)
    {
        $responses = explode("\n", $response);
        if (!empty($responses[1])) {
            $string = 'header='.$responses[0].'&'.$responses[1];
            $strings = explode('&', $string);
            foreach ($strings as $key => $value) {
                $values = explode('=', $value);
                $responseArray[urldecode($values[0])] = urldecode($values[1]);
            }
            return $responseArray;
        } else {
            return false;
        }
    }

    /**
     * get CountryIso3 by iso2
     * @param  string $iso2
     * @return string
     */
    public function getCountryIso3($iso2)
    {
        $iso3 = [
            "AF" => "AFG",
            "AL" => "ALB",
            "DZ" => "DZA",
            "AS" => "ASM",
            "AD" => "AND",
            "AO" => "AGO",
            "AI" => "AIA",
            "AQ" => "ATA",
            "AG" => "ATG",
            "AR" => "ARG",
            "AM" => "ARM",
            "AW" => "ABW",
            "AU" => "AUS",
            "AT" => "AUT",
            "AZ" => "AZE",
            "BS" => "BHS",
            "BH" => "BHR",
            "BD" => "BGD",
            "BB" => "BRB",
            "BY" => "BLR",
            "BE" => "BEL",
            "BZ" => "BLZ",
            "BJ" => "BEN",
            "BM" => "BMU",
            "BT" => "BTN",
            "BO" => "BOL",
            "BA" => "BIH",
            "BW" => "BWA",
            "BV" => "BVT",
            "BR" => "BRA",
            "IO" => "IOT",
            "VG" => "VGB",
            "BN" => "BRN",
            "BG" => "BGR",
            "BF" => "BFA",
            "BI" => "BDI",
            "KH" => "KHM",
            "CM" => "CMR",
            "CA" => "CAN",
            "CV" => "CPV",
            "KY" => "CYM",
            "CF" => "CAF",
            "TD" => "TCD",
            "CL" => "CHL",
            "CN" => "CHN",
            "CX" => "CXR",
            "CC" => "CCK",
            "CO" => "COL",
            "KM" => "COM",
            "CG" => "COG",
            "CD" => "COD",
            "CK" => "COK",
            "CR" => "CRI",
            "HR" => "HRV",
            "CU" => "CUB",
            "CY" => "CYP",
            "CZ" => "CZE",
            "CI" => "CIV",
            "DK" => "DNK",
            "DJ" => "DJI",
            "DM" => "DMA",
            "DO" => "DOM",
            "EC" => "ECU",
            "EG" => "EGY",
            "SV" => "SLV",
            "GQ" => "GNQ",
            "ER" => "ERI",
            "EE" => "EST",
            "ET" => "ETH",
            "FK" => "FLK",
            "FO" => "FRO",
            "FJ" => "FJI",
            "FI" => "FIN",
            "FR" => "FRA",
            "GF" => "GUF",
            "PF" => "PYF",
            "TF" => "ATF",
            "GA" => "GAB",
            "GM" => "GMB",
            "GE" => "GEO",
            "DE" => "DEU",
            "GH" => "GHA",
            "GI" => "GIB",
            "GR" => "GRC",
            "GL" => "GRL",
            "GD" => "GRD",
            "GP" => "GLD",
            "GU" => "GUM",
            "GT" => "GTM",
            "GG" => "GGY",
            "GN" => "HTI",
            "GW" => "HMD",
            "GY" => "VAT",
            "HT" => "GIN",
            "HM" => "GNB",
            "HN" => "HND",
            "HK" => "HKG",
            "HU" => "HUN",
            "IS" => "ISL",
            "IN" => "IND",
            "ID" => "IDN",
            "IR" => "IRN",
            "IQ" => "IRQ",
            "IE" => "IRL",
            "IM" => "IMN",
            "IL" => "ISR",
            "IT" => "ITA",
            "JM" => "JAM",
            "JP" => "JPN",
            "JE" => "JEY",
            "JO" => "JOR",
            "KZ" => "KAZ",
            "KE" => "KEN",
            "KI" => "KIR",
            "KW" => "KWT",
            "KG" => "KGZ",
            "LA" => "LAO",
            "LV" => "LVA",
            "LB" => "LBN",
            "LS" => "LSO",
            "LR" => "LBR",
            "LY" => "LBY",
            "LI" => "LIE",
            "LT" => "LTU",
            "LU" => "LUX",
            "MO" => "MAC",
            "MK" => "MKD",
            "MG" => "MDG",
            "MW" => "MWI",
            "MY" => "MYS",
            "MV" => "MDV",
            "ML" => "MLI",
            "MT" => "MLT",
            "MH" => "MHL",
            "MQ" => "MTQ",
            "MR" => "MRT",
            "MU" => "MUS",
            "YT" => "MYT",
            "MX" => "MEX",
            "FM" => "FSM",
            "MD" => "MDA",
            "MC" => "MCO",
            "MN" => "MNG",
            "ME" => "MNE",
            "MS" => "MSR",
            "MA" => "MAR",
            "MZ" => "MOZ",
            "MM" => "MMR",
            "NA" => "NAM",
            "NR" => "NRU",
            "NP" => "NPL",
            "NL" => "NLD",
            "AN" => "ANT",
            "NC" => "NCL",
            "NZ" => "NZL",
            "NI" => "NIC",
            "NE" => "NER",
            "NG" => "NGA",
            "NU" => "NIU",
            "NF" => "NFK",
            "KP" => "PRK",
            "MP" => "MNP",
            "NO" => "NOR",
            "OM" => "OMN",
            "PK" => "PAK",
            "PW" => "PLW",
            "PS" => "PSE",
            "PA" => "PAN",
            "PG" => "PNG",
            "PY" => "PRY",
            "PE" => "PER",
            "PH" => "PHL",
            "PN" => "PCN",
            "PL" => "POL",
            "PT" => "PRT",
            "PR" => "PRI",
            "QA" => "QAT",
            "RO" => "ROU",
            "RU" => "RUS",
            "RW" => "RWA",
            "RE" => "REU",
            "BL" => "BLM",
            "SH" => "SHN",
            "KN" => "KNA",
            "LC" => "LCA",
            "MF" => "MAF",
            "PM" => "SPM",
            "WS" => "WSM",
            "SM" => "SMR",
            "SA" => "SAU",
            "SN" => "SEN",
            "RS" => "SRB",
            "SC" => "SYC",
            "SL" => "SLE",
            "SG" => "SGP",
            "SK" => "SVK",
            "SI" => "SVN",
            "SB" => "SLB",
            "SO" => "SOM",
            "ZA" => "ZAF",
            "GS" => "SGS",
            "KR" => "KOR",
            "ES" => "ESP",
            "LK" => "LKA",
            "VC" => "VCT",
            "SD" => "SDN",
            "SR" => "SUR",
            "SJ" => "SJM",
            "SZ" => "SWZ",
            "SE" => "SWE",
            "CH" => "CHE",
            "SY" => "SYR",
            "ST" => "STP",
            "TW" => "TWN",
            "TJ" => "TJK",
            "TZ" => "TZA",
            "TH" => "THA",
            "TL" => "TLS",
            "TG" => "TGO",
            "TK" => "TKL",
            "TO" => "TON",
            "TT" => "TTO",
            "TN" => "TUN",
            "TR" => "TUR",
            "TM" => "TKM",
            "TC" => "TCA",
            "TV" => "TUV",
            "UM" => "UMI",
            "VI" => "VIR",
            "UG" => "UGA",
            "UA" => "UKR",
            "AE" => "ARE",
            "GB" => "GBR",
            "US" => "USA",
            "UY" => "URY",
            "UZ" => "UZB",
            "VU" => "VUT",
            "VA" => "GUY",
            "VE" => "VEN",
            "VN" => "VNM",
            "WF" => "WLF",
            "EH" => "ESH",
            "YE" => "YEM",
            "ZM" => "ZMB",
            "ZW" => "ZWE",
            "AX" => "ALA"
        ];
        if (array_key_exists($iso2, $iso3)) {
            return $iso3[$iso2];
        }
        return '';
    }

    /**
     * get CountryIso2 by iso3
     * @param  string $iso3
     * @return string
     */
    public function getCountryIso2($iso3)
    {
        $iso2 = [
             "AFG" => "AF",
             "ALB" => "AL",
             "DZA" => "DZ",
             "ASM" => "AS",
             "AND" => "AD",
             "AGO" => "AO",
             "AIA" => "AI",
             "ATA" => "AQ",
             "ATG" => "AG",
             "ARG" => "AR",
             "ARM" => "AM",
             "ABW" => "AW",
             "AUS" => "AU",
             "AUT" => "AT",
             "AZE" => "AZ",
             "BHS" => "BS",
             "BHR" => "BH",
             "BGD" => "BD",
             "BRB" => "BB",
             "BLR" => "BY",
             "BEL" => "BE",
             "BLZ" => "BZ",
             "BEN" => "BJ",
             "BMU" => "BM",
             "BTN" => "BT",
             "BOL" => "BO",
             "BIH" => "BA",
             "BWA" => "BW",
             "BVT" => "BV",
             "BRA" => "BR",
             "IOT" => "IO",
             "VGB" => "VG",
             "BRN" => "BN",
             "BGR" => "BG",
             "BFA" => "BF",
             "BDI" => "BI",
             "KHM" => "KH",
             "CMR" => "CM",
             "CAN" => "CA",
             "CPV" => "CV",
             "CYM" => "KY",
             "CAF" => "CF",
             "TCD" => "TD",
             "CHL" => "CL",
             "CHN" => "CN",
             "CXR" => "CX",
             "CCK" => "CC",
             "COL" => "CO",
             "COM" => "KM",
             "COG" => "CG",
             "COD" => "CD",
             "COK" => "CK",
             "CRI" => "CR",
             "HRV" => "HR",
             "CUB" => "CU",
             "CYP" => "CY",
             "CZE" => "CZ",
             "CIV" => "CI",
             "DNK" => "DK",
             "DJI" => "DJ",
             "DMA" => "DM",
             "DOM" => "DO",
             "ECU" => "EC",
             "EGY" => "EG",
             "SLV" => "SV",
             "GNQ" => "GQ",
             "ERI" => "ER",
             "EST" => "EE",
             "ETH" => "ET",
             "FLK" => "FK",
             "FRO" => "FO",
             "FJI" => "FJ",
             "FIN" => "FI",
             "FRA" => "FR",
             "GUF" => "GF",
             "PYF" => "PF",
             "ATF" => "TF",
             "GAB" => "GA",
             "GMB" => "GM",
             "GEO" => "GE",
             "DEU" => "DE",
             "GHA" => "GH",
             "GIB" => "GI",
             "GRC" => "GR",
             "GRL" => "GL",
             "GRD" => "GD",
             "GLD" => "GP",
             "GUM" => "GU",
             "GTM" => "GT",
             "GGY" => "GG",
             "HTI" => "GN",
             "HMD" => "GW",
             "VAT" => "GY",
             "GIN" => "HT",
             "GNB" => "HM",
             "HND" => "HN",
             "HKG" => "HK",
             "HUN" => "HU",
             "ISL" => "IS",
             "IND" => "IN",
             "IDN" => "ID",
             "IRN" => "IR",
             "IRQ" => "IQ",
             "IRL" => "IE",
             "IMN" => "IM",
             "ISR" => "IL",
             "ITA" => "IT",
             "JAM" => "JM",
             "JPN" => "JP",
             "JEY" => "JE",
             "JOR" => "JO",
             "KAZ" => "KZ",
             "KEN" => "KE",
             "KIR" => "KI",
             "KWT" => "KW",
             "KGZ" => "KG",
             "LAO" => "LA",
             "LVA" => "LV",
             "LBN" => "LB",
             "LSO" => "LS",
             "LBR" => "LR",
             "LBY" => "LY",
             "LIE" => "LI",
             "LTU" => "LT",
             "LUX" => "LU",
             "MAC" => "MO",
             "MKD" => "MK",
             "MDG" => "MG",
             "MWI" => "MW",
             "MYS" => "MY",
             "MDV" => "MV",
             "MLI" => "ML",
             "MLT" => "MT",
             "MHL" => "MH",
             "MTQ" => "MQ",
             "MRT" => "MR",
             "MUS" => "MU",
             "MYT" => "YT",
             "MEX" => "MX",
             "FSM" => "FM",
             "MDA" => "MD",
             "MCO" => "MC",
             "MNG" => "MN",
             "MNE" => "ME",
             "MSR" => "MS",
             "MAR" => "MA",
             "MOZ" => "MZ",
             "MMR" => "MM",
             "NAM" => "NA",
             "NRU" => "NR",
             "NPL" => "NP",
             "NLD" => "NL",
             "ANT" => "AN",
             "NCL" => "NC",
             "NZL" => "NZ",
             "NIC" => "NI",
             "NER" => "NE",
             "NGA" => "NG",
             "NIU" => "NU",
             "NFK" => "NF",
             "PRK" => "KP",
             "MNP" => "MP",
             "NOR" => "NO",
             "OMN" => "OM",
             "PAK" => "PK",
             "PLW" => "PW",
             "PSE" => "PS",
             "PAN" => "PA",
             "PNG" => "PG",
             "PRY" => "PY",
             "PER" => "PE",
             "PHL" => "PH",
             "PCN" => "PN",
             "POL" => "PL",
             "PRT" => "PT",
             "PRI" => "PR",
             "QAT" => "QA",
             "ROU" => "RO",
             "RUS" => "RU",
             "RWA" => "RW",
             "REU" => "RE",
             "BLM" => "BL",
             "SHN" => "SH",
             "KNA" => "KN",
             "LCA" => "LC",
             "MAF" => "MF",
             "SPM" => "PM",
             "WSM" => "WS",
             "SMR" => "SM",
             "SAU" => "SA",
             "SEN" => "SN",
             "SRB" => "RS",
             "SYC" => "SC",
             "SLE" => "SL",
             "SGP" => "SG",
             "SVK" => "SK",
             "SVN" => "SI",
             "SLB" => "SB",
             "SOM" => "SO",
             "ZAF" => "ZA",
             "SGS" => "GS",
             "KOR" => "KR",
             "ESP" => "ES",
             "LKA" => "LK",
             "VCT" => "VC",
             "SDN" => "SD",
             "SUR" => "SR",
             "SJM" => "SJ",
             "SWZ" => "SZ",
             "SWE" => "SE",
             "CHE" => "CH",
             "SYR" => "SY",
             "STP" => "ST",
             "TWN" => "TW",
             "TJK" => "TJ",
             "TZA" => "TZ",
             "THA" => "TH",
             "TLS" => "TL",
             "TGO" => "TG",
             "TKL" => "TK",
             "TON" => "TO",
             "TTO" => "TT",
             "TUN" => "TN",
             "TUR" => "TR",
             "TKM" => "TM",
             "TCA" => "TC",
             "TUV" => "TV",
             "UMI" => "UM",
             "VIR" => "VI",
             "UGA" => "UG",
             "UKR" => "UA",
             "ARE" => "AE",
             "GBR" => "GB",
             "USA" => "US",
             "URY" => "UY",
             "UZB" => "UZ",
             "VUT" => "VU",
             "GUY" => "VA",
             "VEN" => "VE",
             "VNM" => "VN",
             "WLF" => "WF",
             "ESH" => "EH",
             "YEM" => "YE",
             "ZMB" => "ZM",
             "ZWE" => "ZW",
             "ALA" => "AX"        ];
        if (array_key_exists($iso3, $iso2)) {
            return $iso2[$iso3];
        }
        return '';
    }

    /**
     * get status translation
     * @param  string $code
     * @return string
     */
    public function getStatusTranslation($code)
    {
        $status = [
            '2'  => 'BACKEND_TT_PROCESSED',
            '0'  => 'BACKEND_TT_PENDING',
            '-1' => 'BACKEND_TT_CANCELLED',
            '-2' => 'BACKEND_TT_FAILED',
            '-3' => 'BACKEND_TT_CHARGEBACK',
            '-4' => 'BACKEND_TT_REFUNDED',
            '-5' => 'BACKEND_TT_REFUNDED_FAILED',
            '-6' => 'BACKEND_TT_REFUNDED_PENDING',
            '-7' => 'BACKEND_GENERAL_FRAUD',
            '-8' => 'BACKEND_TT_NOT_VERIFIED'
        ];
        if (array_key_exists($code, $status)) {
            return __($status[$code]);
        }
        return __('ERROR_GENERAL_ABANDONED_BYUSER');
    }

    /**
     * get skrill error mapping
     * @param  string $code
     * @return string
     */
    public function getSkrillErrorMapping($code)
    {
        $error_messages = [
            "01" => "SKRILL_ERROR_01",
            "02" => "SKRILL_ERROR_02",
            "03" => "SKRILL_ERROR_03",
            "04" => "SKRILL_ERROR_04",
            "05" => "SKRILL_ERROR_05",
            "08" => "SKRILL_ERROR_08",
            "09" => "SKRILL_ERROR_09",
            "10" => "SKRILL_ERROR_10",
            "12" => "SKRILL_ERROR_12",
            "15" => "SKRILL_ERROR_15",
            "19" => "SKRILL_ERROR_19",
            "24" => "SKRILL_ERROR_24",
            "28" => "SKRILL_ERROR_28",
            "32" => "SKRILL_ERROR_32",
            "37" => "SKRILL_ERROR_37",
            "38" => "SKRILL_ERROR_38",
            "42" => "SKRILL_ERROR_42",
            "44" => "SKRILL_ERROR_44",
            "51" => "SKRILL_ERROR_51",
            "63" => "SKRILL_ERROR_63",
            "70" => "SKRILL_ERROR_70",
            "71" => "SKRILL_ERROR_71",
            "80" => "SKRILL_ERROR_80",
            "98" => "SKRILL_ERROR_98",
            "99" => "SKRILL_ERROR_99_GENERAL"
        ];
        if (array_key_exists($code, $error_messages)) {
            return $error_messages[$code];
        }
        return 'SKRILL_ERROR_99_GENERAL';
    }

    /**
     * get country name from country iso code
     * @param  string $country
     * @return string
     */
    public function getCountryName($country)
    {
        if (strlen($country) == 3) {
            $country = $this->getCountryIso2($country);
        }
        if (!empty($country)) {
            return $this->translatedList->getCountryTranslation($country);
        }
        return '';
    }

    /**
     * get comment order history
     * @param  array $response
     * @param  boolean | string $type
     * @return string
     */
    public function getComment($response, $type = false)
    {
        $separator = ". ";

        $comment = __('SKRILL_BACKEND_ORDER_STATUS')." : ".$this->getStatusTranslation($response['status']).$separator;
        if (isset($response['payment_type'])) {
            $comment .=
                __('SKRILL_BACKEND_ORDER_PM')." : ".__('SKRILL_FRONTEND_PM_'.$response['payment_type']).$separator;
        }
        if (isset($response['payment_instrument_country'])) {
            $cardIssuer = $this->getCountryName($response['payment_instrument_country']);
            if ($cardIssuer) {
                $comment .= __('SKRILL_BACKEND_ORDER_COUNTRY')." : ".$cardIssuer.$separator;
            }
        }
        if ($type == "fraud") {
            $comment = __('SKRILL_BACKEND_GENERAL_TRANSACTION')." ".__('BACKEND_GENERAL_FRAUD').$separator;
            $comment .= __('SKRILL_BACKEND_GENERAL_TRANSACTION_ID')." : ".$response['mb_transaction_id'].$separator;
            $comment .=
                __('SKRILL_BACKEND_ORDER_STATUS')." : ".$this->getStatusTranslation($response['status']).$separator;
        }
        if ($type == "refundStatus") {
            if (isset($response['amount'])) {
                $comment .= __('BACKEND_TT_AMOUNT')." : ".$response['amount'].$separator;
            }
        }
        return $comment;
    }

    /**
     * do refund
     * @param  array $parameters
     * @return boolean | xml
     */
    public function doRefund($parameters)
    {
        $url = $this->getRefundUrl();

        $parametersLog = $parameters;
        $parametersLog['password'] = '*****';

        $this->logger->info('refund URL : '.$url);
        $this->logger->info(
            'doRefund prepare parameters : '.
            json_encode($parametersLog)
        );

        $prepareResponse = $this->doAction($url, 'prepare', $parameters);
        $prepareResponse = simplexml_load_string($prepareResponse);

        $error = (string) $prepareResponse->error->error_msg;

        if (!empty($error)) {
            if ($error == 'CANNOT_LOGIN') {
                return $error;
            }
            if (strpos($error, 'LOCK') !== false) {
                return 'ACCOUNT_LOCKED';
            }
            return 'GENERAL_ERROR';
        }

        $sid = (string) $prepareResponse->sid;

        if (!empty($sid)) {
            $this->logger->info('doRefund sid : '.$sid);

            $parameters['sid'] = $sid;
            $refundResponse = $this->doAction($url, 'refund', $parameters);

            return simplexml_load_string($refundResponse);
        }
        return 'GENERAL_ERROR';
    }

    /**
     * check if md5 is valid
     * @param  string $md5
     * @return boolean
     */
    public function isMd5Valid($md5 = '')
    {
        return preg_match('/^[a-f0-9]{32}$/', $md5);
    }

    /**
     * get the version of magento
     * 
     * @return string
     */
    public function getShopVersion()
    {
    if (defined('\Magento\Framework\AppInterface::VERSION')) {
    return \Magento\Framework\AppInterface::VERSION;
    }

    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    $productMetaData = $objectManager->create('\Magento\Framework\App\ProductMetadataInterface');

    return $productMetaData->getVersion();
    }
}
