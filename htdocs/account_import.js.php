<?php

/**
 * Javascript helper for Account_Import.
 *
 * @category   apps
 * @package    account-import
 * @subpackage javascript
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/account_import/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

clearos_load_language('account_import');
clearos_load_language('base');

header('Content-Type: application/x-javascript');

echo "

$(document).ready(function() {
    get_progress();
});
function get_progress() {
    $.ajax({
        type: 'GET',
        dataType: 'json',
        url: '/app/account_import/ajax/get_progress',
        data: '',
        success: function(data) {
            if (data == undefined || data.code == null) {
                    $('#progress').progressbar({
                        value: 0
                    });
                    window.setTimeout(get_progress, 1000);
                    return;
            }
                
            if (data.code < 0) {
                table_logs.fnClearTable();
            } else {
                // Logs
                if (data.logs != undefined && data.logs != null && $('#logs').length > 0) {
                    table_logs.fnClearTable();
                    var progress = 0;
                    for (var index = 0 ; index < data.logs.length; index++) {
                        if (data.logs[index] == null)
                            continue;
                        date = new Date(data.logs[index].timestamp*1000);
                        span_tag = '<span>';
                        if (data.logs[index].code != 0)
                            span_tag = '<span style=\'color: red;\'>';
                        table_logs.fnAddData([
                            span_tag + data.logs[index].msg + '</span',
                            span_tag + $.datepicker.formatDate('M d, yy', date) + ' ' + date.toLocaleTimeString() + '</span>'
                        ]);
                        if (index == 0) {
                            $('#progress').progressbar({
                                value: Math.round(data.logs[index].progress)
                            });
                        }
                    }
                    table_logs.fnAdjustColumnSizing();
                }
		    }

            window.setTimeout(get_progress, 1000);
        },
        error: function(xhr, text, err) {
            // Don't display any errors if ajax request was aborted due to page redirect/reload
            if (xhr['abort'] == undefined)
                clearos_alert('errmsg', xhr.responseText.toString());
            window.setTimeout(get_progress, 1000);
        }
    });
}
";

// vim: syntax=php ts=4
