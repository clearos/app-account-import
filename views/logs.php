<?php

/**
 * Dynamic DNS view.
 *
 * @category   Apps
 * @package    Dynamic_DNS
 * @subpackage Views
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011 ClearCenter
 * @license    http://www.clearcenter.com/app_license ClearCenter license
 * @link       http://www.clearcenter.com/support/documentation/clearos/account_import/
 */

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////


$this->lang->load('base');
$this->lang->load('account_import');

///////////////////////////////////////////////////////////////////////////////
// Buttons
///////////////////////////////////////////////////////////////////////////////

if ($in_progress) {
    $buttons = NULL;
} else {
    $buttons = array(
        anchor_custom(
            '/app/account_import/logs/clear', lang('account_import_clear'), 'high'
        )
    );
}

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
        lang('base_description'),
        lang('base_timestamp')
);

///////////////////////////////////////////////////////////////////////////////
// List table
///////////////////////////////////////////////////////////////////////////////

echo form_open('account_import');

echo summary_table(
    lang('account_import_log'),
    $buttons,
    $headers,
    NULL,
    array('id' => 'logs', 'sort' => FALSE, 'paginate' => TRUE, 'no_action' => TRUE)
);

echo form_close();
