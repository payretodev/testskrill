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
 
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
 
class UpgradeData implements UpgradeDataInterface
{
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();
        $installer = $setup;

        if (version_compare($context->getVersion(), '2.0.03', '<')) {
            if ($installer->getTableRow($installer->getTable('sales_order_status'), 'status', 'invalid_credential')) {
                $installer->updateTableRow(
                    $installer->getTable('sales_order_status'),
                    'status',
                    'invalid_credential',
                    'status',
                    'not_verified'
                );
                $installer->updateTableRow(
                    $installer->getTable('sales_order_status'),
                    'label',
                    'Invalid Credential',
                    'label',
                    'Not Verified'
                );
            }

            if ($installer->getTableRow(
                $installer->getTable('sales_order_status_state'),
                'status',
                'invalid_credential'
            )) {
                $installer->updateTableRow(
                    $installer->getTable('sales_order_status_state'),
                    'status',
                    'invalid_credential',
                    'status',
                    'not_verified'
                );
            }

            if ($installer->getTableRow($installer->getTable('sales_order_grid'), 'status', 'invalid_credential')) {
                $installer->updateTableRow(
                    $installer->getTable('sales_order_grid'),
                    'status',
                    'invalid_credential',
                    'status',
                    'not_verified'
                );
            }

            if ($installer->getTableRow(
                $installer->getTable('sales_order_status_history'),
                'status',
                'invalid_credential'
            )) {
                $installer->updateTableRow(
                    $installer->getTable('sales_order_status_history'),
                    'status',
                    'invalid_credential',
                    'status',
                    'not_verified'
                );
            }

            if ($installer->getTableRow($installer->getTable('sales_order'), 'status', 'invalid_credential')) {
                $installer->updateTableRow(
                    $installer->getTable('sales_order'),
                    'status',
                    'invalid_credential',
                    'status',
                    'not_verified'
                );
            }
        }

        $setup->endSetup();
    }
}
