<?php

/**
 * Account import/export class.
 *
 * @category   apps
 * @package    account-import
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2014 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/account_import/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\account_import;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');
clearos_load_language('account_import');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Script as Script;
use \clearos\apps\base\Shell as Shell;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Script');
clearos_load_library('base/Shell');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_Not_Found_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Account import/export class.
 *
 * @category   apps
 * @package    account-import
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2014 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/account_import/
 */

class Account_Import extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CSV = 'import.csv';
    const FILE_CSV_TEMPLATE = 'import_template.csv';
    const FILE_STATUS = 'account_import.json';
    const COMMAND_IMPORT = '/usr/sbin/account-import';
    const COMMAND_PS = '/bin/ps';
    const FOLDER_ACCOUNT_IMPORT = '/var/clearos/account_import';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Account Import/Export constructor.
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns boolean indicating whether import is currently running.
     *
     * @return boolean
     * @throws Engine_Exception
     */

    function is_import_in_progress()
    {
        clearos_profile(__METHOD__, __LINE__);

        $script = new Script(basename(self::COMMAND_IMPORT));

        return $script->is_running();
    }

    /**
     * Returns JSON-encoded data indicating progress of import currently running.
     *
     * @return string
     * @throws Engine_Exception
     */

    function get_progress()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(CLEAROS_TEMP_DIR . "/" . self::FILE_STATUS, FALSE);
        $status = array();
        if (!$file->exists())
            throw new Engine_Exception(lang('account_import_no_data_found'));

        $lines = $file->get_contents_as_array();

        if (empty($lines))
            throw new Engine_Exception(lang('account_import_no_data_found'));
        else
            $lines = array_reverse($lines);

        foreach ($lines as $line)
            $status[] = json_decode($line);
        
        return $status;
    }

    /**
     * Performs an account import.
     *
     * @return void
     * @throws Engine_Exception, File_Not_Found_Exception
     */

    function import()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->is_import_in_progress())
            throw new Engine_Exception(lang('account_import_import_already_in_progress'));
            
        $file = new File(self::FOLDER_ACCOUNT_IMPORT . '/' . self::FILE_CSV, TRUE);
        if (!$file->exists())
            throw new File_Not_Found_Exception(lang('account_import_csv_file_not_found'));

        $this->delete_log();

        $options = array();
        $options['background'] = TRUE;

        $shell = new Shell();
        $shell->execute(self::COMMAND_IMPORT, '', TRUE, $options);
    }

    /**
     * Puts the CSV file in the cache directory, ready for import begin.
     *
     * @param string $filename string CSV filename
     *
     * @return void
     * @throws Engine_Exception, File_Not_Found_Exception
     */

    function set_csv_file($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(CLEAROS_TEMP_DIR . '/' . $filename, TRUE);
            if (!$file->exists())
                throw new File_Not_Found_Exception(clearos_exception_message($e));

            // Move uploaded file to cache
            $file->move_to(self::FOLDER_ACCOUNT_IMPORT . '/' . self::FILE_CSV);
            $file->chown('root', 'root'); 
            $file->chmod(600);
        } catch (File_Not_Found_Exception $e) {
            throw new File_Not_Found_Exception(clearos_exception_message($e));
        }
    }

    /**
     * Returns state of CSV file upload.
     *
     * @return boolean state of CSV file upload
     * @throws Engine_Exception, File_Not_Found_Exception
     */

    function is_csv_file_uploaded()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FOLDER_ACCOUNT_IMPORT . '/' . self::FILE_CSV, TRUE);
            if (!$file->exists())
                return FALSE;
            return TRUE;
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Deletes log file.
     *
     * @return void
     * @throws Engine_Exception
     */

    function delete_log()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(CLEAROS_TEMP_DIR . "/" . self::FILE_STATUS, FALSE);

        if ($file->exists())
            $file->delete();
    }

    /**
     * Resets (deletes) the CSV file.
     *
     * @return void
     * @throws Engine_Exception, File_Not_Found_Exception
     */

    function delete_csv_file()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FOLDER_ACCOUNT_IMPORT . '/' . self::FILE_CSV, TRUE);
            if (!$file->exists())
                throw new File_Not_Found_Exception(lang('account_import_csv_file_not_found'));
            $file->delete();
        } catch (File_Not_Found_Exception $e) {
            throw new File_Not_Found_Exception(clearos_exception_message($e));
        }
    }

    /**
     * Resets (deletes) the CSV file.
     *
     * @return integer size 
     * @throws Engine_Exception, File_Not_Found_Exception
     */

    function get_csv_size()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FOLDER_ACCOUNT_IMPORT . '/' . self::FILE_CSV, TRUE);
            if (!$file->exists())
                throw new File_Not_Found_Exception(lang('account_import_csv_file_not_found'));
            return $file->get_size();
        } catch (File_Not_Found_Exception $e) {
            throw new File_Not_Found_Exception(clearos_exception_message($e));
        }
    }

    /**
     * Returns the number of records.
     *
     * @return integer the number of records
     * @throws Engine_Exception, File_Not_Found_Exception
     */

    function get_number_of_records()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FOLDER_ACCOUNT_IMPORT . '/' . self::FILE_CSV, TRUE);
            if (!$file->exists())
                throw new File_Not_Found_Exception(lang('account_import_csv_file_not_found'));
            return count($file->get_contents_as_array()) - 1;
        } catch (File_Not_Found_Exception $e) {
            throw new File_Not_Found_Exception(clearos_exception_message($e));
        }
    }
}
