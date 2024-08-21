<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2021-2023 Petr Macek                                      |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | https://github.com/xmacan/                                              |
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

chdir('../../');
include_once('./include/auth.php');
include_once('./lib/snmp.php');
include_once('./plugins/snver/functions.php');

if (!isempty_request_var('find')) {
	set_request_var('action','find');
	unset_request_var('host_id');
}

set_default_action();

$selectedTheme = get_selected_theme();

switch (get_request_var('action')) {
	case 'ajax_hosts':

		$sql_where = '';
		get_allowed_ajax_hosts(true, 'applyFilter', $sql_where);

		break;

	case 'find':
		general_header();
		display_snver_form();
		snver_find();
		bottom_footer();

		break;

        default:
		general_header();
		display_snver_form();
		bottom_footer();

                break;
}

function display_snver_form() {
	global $config;

	$snver_records = read_config_option('snver_records');

	print get_md5_include_js($config['base_path'].'/plugins/snver/snver.js');

	$host_where = '';

	$host_id = get_filter_request_var('host_id');

	html_start_box('<strong>SNVer</strong>', '100%', '', '3', 'center', '');

?>

	<tr>
	 <td>
	  <form name="form_snver" action="snver_tab.php">
		<table width="60%" cellpadding="0" cellspacing="0">
		<tr class="navigate_form">
		<td>
		       <?php print html_host_filter($host_id, 'applyFilter', $host_where);?>
		</td>
		<td>
			Find in stored data <input type='text' class='ui-button ui-corner-all ui-widget' name='find' id='find' value='<?php print get_request_var('find');?>'> 
			<input type='submit' class='ui-button ui-corner-all ui-widget' value='<?php print __('Find');?>'>
		</td>
		<td>
			<input type='button' class='ui-button ui-corner-all ui-widget' id='clear' value='<?php print __('Clear');?>' title='<?php print __esc('Clear Filters');?>'> 
		</td>
		</tr>
		</table>
	  </form>
       </td>
     <tr>
<?php
	html_end_box();

	if (in_array($host_id, snver_get_allowed_devices($_SESSION['sess_user_id'], true))) {

		$actual_info = plugin_snver_get_info($host_id);
		if ($actual_info) {
			print $actual_info;
			print '<br/><br/>';

			$optional = plugin_snver_get_info_optional($host_id);
			print $optional;
			print '<br/><br/>';
		}

		if ($snver_records > 0) {
			$history = plugin_snver_get_history($host_id);

			$old = $actual_info;
			if (cacti_sizeof($history)) {
				foreach ($history as $key=>$value) {
					if (strcmp ($value, $old) === 0) {
						echo "$key - Stejna data<br/>\n";
					} else {
						echo "$key - Jina data<br/>\n";
						echo $value;
					}

					$old = $value;
				}
			}
		} else {
			print 'History data store disabled';
		}
	}
}

