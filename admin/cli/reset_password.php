<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This script allows you to reset any local user password.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions

list($options, $unrecognized) = cli_get_params(
    array(
        'user'         => '',
        'pass'         => ''
    ),
    array(
        'h' => 'help'
    )
);

// now get cli options
// list($options, $unrecognized) = cli_get_params(array('help'=>false),
//                                               array('h'=>'help'));

$interactive = empty($options['non-interactive']);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
"Reset local user passwords, useful especially for admin acounts.

There are no security checks here because anybody who is able to
execute this file may execute any PHP too.

Options:
--user                User ID to Use
--password            Password to Set
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php admin/cli/reset_password.php

or 

\$sudo -u www-data /usr/bin/php admin/cli/reset_password.php -u admin -p SOMETHING
"; //TODO: localize - to be translated later when everything is finished

    echo $help;
    die;
}

// Get User ID to Reset
if ($interactive) {
    cli_heading('Password reset'); // TODO: localize
    $prompt = "enter username (manual authentication only)"; // TODO: localize
    $username = cli_input($prompt);
} else {
    if (empty($options['user'])) {
        $username = (object)array('option'=>'user', 'value'=>$options['user']);
        cli_error(get_string('cliincorrectvalueerror', 'user', $u));
    }
}

// Check for User Existence in DB
if (!$user = $DB->get_record('user', array('auth'=>'manual', 'username'=>$username,
      'mnethostid'=>$CFG->mnet_localhost_id))) {
    cli_error("Can not find user '$username'");
}

$prompt = "Enter new password"; // TODO: localize

if ($interactive) {
    $password = cli_input($prompt);
    $errmsg = '';//prevent eclipse warning
    if (!check_password_policy($password, $errmsg)) {
        cli_error($errmsg);
    }
} else {
    if (empty($options['password'])) {
        $password = (object)array('option'=>'password', 'value'=>$options['password']);
        cli_error(get_string('cliincorrectvalueerror', 'admin', $password));
    }
}

$hashedpassword = hash_internal_user_password($password);

$DB->set_field('user', 'password', $hashedpassword, array('id'=>$user->id));

echo "Password changed\n";

exit(0); // 0 means success