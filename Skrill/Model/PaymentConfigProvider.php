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
namespace Skrill\Skrill\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\RequestInterface;

class PaymentConfigProvider implements ConfigProviderInterface
{
    private $paymentHelper;
    private $assetRepo;
    private $request;

    private $methodCodes = [
        'skrill_flexible',
        'skrill_wlt',
        'skrill_psc',
        'skrill_pch',
        'skrill_acc',
        'skrill_vsa',
        'skrill_msc',
        'skrill_mae',
        'skrill_amx',
        'skrill_gcb',
        'skrill_dnk',
        'skrill_psp',
        'skrill_csi',
        'skrill_obt',
        'skrill_ntl',
        'skrill_gir',
        'skrill_did',
        'skrill_sft',
        'skrill_ebt',
        'skrill_idl',
        'skrill_npy',
        'skrill_pli',
        'skrill_pwy',
        'skrill_epy',
        'skrill_ali',
        'skrill_glu',
        'skrill_adb',
        'skrill_aob',
        'skrill_aci',
        'skrill_aup',
        'skrill_btc'
    ];

    private $supportedLogo = [
        'skrill_adb' => [
            'santander-rio.png',
            'itau.png',
            'banco-do-brasil.png',
            'bradesco.png'
        ],
        'skrill_aob' => [
            'hsbc.png',
            'caixa.png',
            'santander.png',
            'PSEi.png',
            'webpaylogo.png',
            'bancolombia.jpg'
        ],
        'skrill_aci' => [
            'red-link.png',
            'pago-facil.png',
            'boleto-bancario.png',
            'servi-pag.png',
            'efecty.png',
            'davivienda.png',
            'exito.png',
            'banco-de-occidente.png',
            'carulla.png',
            'edeq.png',
            'surtimax.png',
            'bancomer_m.png',
            'oxxo.png',
            'banamex.png',
            'santander.png',
            'red-pagos.png'
        ]
    ];

    /**
     *
     * @param PaymentHelper    $paymentHelper
     * @param Repository       $assetRepo
     * @param RequestInterface $request
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Repository $assetRepo,
        RequestInterface $request
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->assetRepo = $assetRepo;
        $this->request = $request;
    }

    /**
     * get configurations
     * @return array
     */
    public function getConfig()
    {
        $config = [];
        foreach ($this->methodCodes as $code) {
            $methodInstance = $this->paymentHelper->getMethodInstance($code);
            if ($methodInstance->isAvailable()) {
                $asset = $this->createAsset('Skrill_Skrill::images/' . $methodInstance->getLogo());
                $astropayPaymentMethod = ['skrill_adb', 'skrill_aob', 'skrill_aci'];
                $display = 'block';
                $supportedBanks = '';

                if (in_array($code, $astropayPaymentMethod)) {
                    $display = 'none';
                    $supportedBanks = $this->getSupportedBankLogoByPaymentMethodCode($code);
                }

                $config['payment']['skrill']['logos'][$code] = [
                    'url' => $asset->getUrl(),
                    'height' => '50px',
                    'display' => $display,
                    'supportedBanks' => $supportedBanks
                ];
            }
        }

        return $config;
    }

    /**
     * create an asset
     * @param  string $fileId
     * @param  array  $params
     * @return object
     */
    public function createAsset($fileId, array $params = [])
    {
        $params = array_merge(['_secure' => $this->request->isSecure()], $params);
        return $this->assetRepo->createAsset($fileId, $params);
    }

    /**
     * get supported bank logo by payment method code
     * @param  string $paymentMethodCode
     * @return array
     */
    public function getSupportedBankLogoByPaymentMethodCode($paymentMethodCode)
    {
        $logo = [];

        $supportedBanks = $this->supportedLogo[$paymentMethodCode];

        foreach ($supportedBanks as $value) {
            $assetBankLogo = $this->createAsset('Skrill_Skrill::images/' . $value);
            $logo[] = $assetBankLogo->getUrl();
        }

        return $logo;
    }
}
