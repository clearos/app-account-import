<?php

/**
 * Account Import default controller.
 *
 * @category   apps
 * @package    account-import
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2012 ClearFoundation
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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\accounts\Accounts_Engine as Accounts_Engine;
use \clearos\apps\accounts\Accounts_Not_Initialized_Exception as Accounts_Not_Initialized_Exception;
use \clearos\apps\accounts\Accounts_Driver_Not_Set_Exception as Accounts_Driver_Not_Set_Exception;
use \clearos\apps\groups\Group_Engine as Group_Engine;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Account Import/Export controller.
 *
 * @category   apps
 * @package    account-import
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/account_import/
 */

class Account_Import extends ClearOS_Controller
{
    /**
     * Account_Import default controller
     *
     * @param String $start force start of import
     *
     * @return view
     */

    function index($start)
    {
        // Show account status widget if we're not in a happy state
        //---------------------------------------------------------

        $this->load->module('accounts/status');

        $supported = array('openldap_directory', 'samba_directory');

        if ($this->status->unhappy($supported)) {
            $this->status->widget('account_import', $supported);
            return;
        }

        // Load dependencies
        //------------------

        $this->lang->load('account_import');
        $this->load->helper('number');
        $this->load->library('account_import/Account_Import');


        // Load views
        //-----------

        if ($start || $this->account_import->is_import_in_progress()) {
            $views = array(
                'account_import/progress',
                'account_import/logs'
            );
        } else {
            $views = array(
                'account_import/import',
                'account_import/logs'
            );
        }

        $this->page->view_forms($views, lang('account_import_app_name'));

    }

    /**
     * Account_Import download template controller
     *
     * @return view
     */

    function template()
    {
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename=import.csv');
        header('Content-Disposition: inline; filename=import.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $this->lang->load('account_import');
        $this->lang->load('base');
        $this->load->factory('users/User_Factory', NULL);
        $this->load->factory('accounts/Accounts_Factory');
        $this->load->factory('groups/Group_Manager_Factory');
        $info_map = $this->user->get_info_map();
        $groups = $this->group_manager->get_list(Group_Engine::FILTER_NORMAL);
        $groups = array_merge($groups, $this->group_manager->get_list(Group_Engine::FILTER_WINDOWS));
        $csv = array(
            'field' => array(),
            'value' => array()
        );

        // Core
        $hide_core = array(
            'home_directory', 
            'login_shell', 
            'uid_number', 
            'gid_number'
        );

        foreach ($info_map['core'] as $key_name => $details) {
            if (in_array($key_name, $hide_core))
                continue;

            $csv['field'][] =  "core.$key_name";

            switch ($key_name) {
                case 'username':
                    $csv['value'][] =  'wshatner';
                    $csv['field'][] =  "core.password";
                    $csv['value'][] =  '1234';
                    break;
                case 'first_name':
                    $csv['value'][] =  'William';
                    break;
                case 'last_name':
                    $csv['value'][] =  'Shatner';
                    break;
                default:
                    $csv['value'][] =  '---';
                    break;
            }
        }

        // Extensions
        if (! empty($info_map['extensions'])) {
            foreach ($info_map['extensions'] as $extension => $parameters) {
                foreach ($parameters as $key => $details) {
                    // Re-initialize array
                    $options = array();

                    if (isset($details['field_priority']) && ($details['field_priority'] === 'hidden')) {
                        continue;
                    } else if (isset($details['field_priority']) && ($details['field_priority'] === 'read_only')) {
                        continue;
                    }

                    $csv['field'][] =  "extensions.$extension.$key";

                    if ($details['field_type'] === 'list') {
                        if ($key === 'country') {
                            $csv['value'][] =  'CA (two letter ISO code)';
                        } else if ($key === 'hard_quota') {
                            $csv['value'][] = '0=No quota, ###=Quota (MB)';
                        } else if (($key === 'account_flag') || ($key === 'administrator_flag') || ($key === 'state')) {
                            $csv['value'][] = '0=' . lang('base_disabled') . ', 1=' . lang('base_enabled');
                        } else {
                            $csv['value'][] = '';
                        }
                    } else if ($details['field_type'] === 'simple_list') {
                        foreach ($details['field_options'] as $option => $value) {
                            if ($key === 'login_shell')
                                $options[] = $value;
                            else
                                $options[] = $option . '=' . $value;
                        }

                        $csv['value'][] = implode(',', $options);
                    } else if ($details['field_type'] === 'text') {
                        if ($key === 'city')
                            $csv['value'][] = 'Montreal' . ($details['required'] ? '' : ' (Optional)');
                        else if ($key === 'region')
                            $csv['value'][] = 'Quebec' . ($details['required'] ? '' : ' (Optional)');
                        else if ($key === 'mobile' || $key === 'telephone' || $key === 'fax')
                            $csv['value'][] = '+1.514.555.5555' . ($details['required'] ? '' : ' (Optional)');
                        else
                            $csv['value'][] = 'Text' . ($details['required'] ? '' : ' (Optional)');
                    } else if ($details['field_type'] === 'integer') {
                        $csv['value'][] = '0';
                    } else if ($details['field_type'] === 'text_array') {
                        if ($key === 'aliases')
                            $csv['value'][] = 'kirk, tjhooker';
                        else
                            $csv['value'][] = 'Setting1, Setting2, Setting3';
                    }
                }
            }
        }

        // Plugins
        if (! empty($info_map['plugins'])) {
            foreach ($info_map['plugins'] as $plugin => $name) {
                $csv['field'][] = "plugins.$name";
                $csv['value'][] = '0=' . lang('base_no') . ', 1=' . lang('base_yes');
            }
        }

        if (is_array($groups) && !empty($groups)) {
            $csv['field'][] = 'groups';
            $csv['value'][] = implode(',', $groups);
        }
        
        foreach ($csv as $type => $data)
            echo "\"" . implode("\",\"", $data) . "\"\n";
    }
}
