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



set_default_action();

$selectedTheme = get_selected_theme();

if (isset_request_var ('host')) {
	$_SESSION['host'] = get_filter_request_var ('host');
}

general_header();

html_start_box('<strong>SNVer</strong>', '100%', '', '3', 'center', '');
?>

<tr>
 <td>
  <form name="form_snver" action="snver_tab.php">
   <table width="100%" cellpadding="0" cellspacing="0">
    <tr class="navigate_form">

     <td style='white-space: nowrap;' width='50'>
      &nbsp;Host:&nbsp;
     </td>
     <td width='1'>
      <select name="host">

<?php
$hosts = db_fetch_assoc ('SELECT id, description FROM host WHERE id IN (' . snver_get_allowed_devices($_SESSION['sess_user_id']) . ') ORDER BY description');

if (count($hosts) > 0)	{
    foreach ($hosts as $host)	{
	// default host
	if (!isset($_SESSION['host'])) $_SESSION['host'] = $host['id'];

	if ($_SESSION['host'] == $host['id'])	{
	    echo '<option value="' . $host['id'] . '" selected="selected">' . $host['description'] . '</option>';
	}
	else
	    echo '<option value="' . $host['id'] . '">' . $host['description'] . '</option>';
    }
}

?>
     <td>
      &nbsp;<input type="submit" value="Go" title="Show">
     </td>
    </tr>
  </table>
 </form>
</td>
<tr><td>
<?php

if (!empty($_SESSION['host']) && in_array($_SESSION['host'],snver_get_allowed_devices($_SESSION['sess_user_id'], true))) 	{

	plugin_snver_get_info($_SESSION['host']);
	echo '</td></tr>';
	html_end_box();
}

bottom_footer();
