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

namespace Skrill\Skrill\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * install the Skrill plugin order statuses
     *
     * @param  ModuleDataSetupInterface $setup
     * @param  ModuleContextInterface   $context
     * @return void
     *
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (!$context->getVersion()) {
            /**
             * Prepare database for install
             */
            $setup->startSetup();

            $statuses = [
                'payment_pa' => 'Pre-Authorization of Payment',
                'invalid_credential' => 'Invalid Credential',
                'payment_accepted' => 'Payment Accepted',
            ];
            foreach ($statuses as $code => $info) {
                $status[] = [
                    'status' => $code,
                    'label' => $info
                ];
            }
            $setup->getConnection()
                ->insertArray($setup->getTable('sales_order_status'), ['status', 'label'], $status);

            $states = [
                'payment_pa' => 'new',
                'invalid_credential' => 'new',
                'payment_accepted' => 'processing',
            ];

            foreach ($states as $status => $stateValue) {
                $state[] = [
                    'status' => $status,
                    'state' => $stateValue,
                    'is_default' => 0,
                    'visible_on_front' => '1'
                ];
            }
            
            $setup->getConnection()
                ->insertArray(
                    $setup->getTable('sales_order_status_state'),
                    ['status', 'state', 'is_default', 'visible_on_front'],
                    $state
                );

            /**
             * Prepare database after install
             */
            $setup->endSetup();
        }
    }
}
