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

namespace Skrill\Skrill\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class Currency implements ArrayInterface
{
    /**
     * Define which currency are possible
     *
     * @return array
     */
    public function toOptionArray()
    {
        $currency = [
            [
                'label' => '-',
                'value' => ''
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_EUR'),
                'value' => 'eur'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_USD'),
                'value' => 'usd'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_GBP'),
                'value' => 'gbp'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_HKD'),
                'value' => 'hkd'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_SGD'),
                'value' => 'sgd'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_JPY'),
                'value' => 'jpy'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_CAD'),
                'value' => 'cad'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_AUD'),
                'value' => 'aud'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_CHF'),
                'value' => 'chf'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_DKK'),
                'value' => 'dkk'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_SEK'),
                'value' => 'sek'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_NOK'),
                'value' => 'nok'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_ILS'),
                'value' => 'ils'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_MYR'),
                'value' => 'myr'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_NZD'),
                'value' => 'nzd'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_TRY'),
                'value' => 'try'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_AED'),
                'value' => 'aed'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_MAD'),
                'value' => 'mad'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_QAR'),
                'value' => 'qar'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_SAR'),
                'value' => 'sar'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_TWD'),
                'value' => 'twd'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_THB'),
                'value' => 'thb'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_CZK'),
                'value' => 'czk'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_HUF'),
                'value' => 'huf'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_BGN'),
                'value' => 'bgn'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_PLN'),
                'value' => 'pln'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_ISK'),
                'value' => 'isk'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_INR'),
                'value' => 'inr'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_KRW'),
                'value' => 'krw'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_ZAR'),
                'value' => 'zar'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_RON'),
                'value' => 'ron'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_HRK'),
                'value' => 'hrk'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_JOD'),
                'value' => 'jod'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_OMR'),
                'value' => 'omr'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_RSD'),
                'value' => 'rsd'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_TND'),
                'value' => 'tnd'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_BHD'),
                'value' => 'bhd'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_KWD'),
                'value' => 'kwd'
            ],
            [
                'label' => __('SKRILL_BACKEND_MC_PEN'),
                'value' => 'pen'
            ]
        ];
        return $currency;
    }
}
