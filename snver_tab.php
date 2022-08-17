<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2021-2022 Petr Macek                                      |
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
	
	$number_of_hosts = read_config_option('snver_hosts_processed');
	
	print get_md5_include_js($config['base_path'].'/plugins/snver/snver.js');

	$host_where = '';

	html_start_box('<strong>SNVer</strong>', '100%', '', '3', 'center', '');
?>

	<tr>
 	 <td>
  	  <form name="form_snver" action="snver_tab.php">
   		<table width="50%" cellpadding="0" cellspacing="0">
    		<tr class="navigate_form">
     		<td>
		       <?php print html_host_filter(get_filter_request_var('host_id', FILTER_VALIDATE_INT), 'applyFilter', $host_where);?>
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

	if (in_array(get_filter_request_var ('host_id'),snver_get_allowed_devices($_SESSION['sess_user_id'], true))) 	{
		$out =  plugin_snver_get_info(get_request_var('host_id'));
		print $out;
		
		if ($number_of_hosts > 0) {
			print plugin_snver_get_history(get_request_var('host_id'),$out);

		}
		else {
        		print 'History data store disabled';
		}

	}
}


