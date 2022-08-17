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

$number_of_hosts = read_config_option('snver_hosts_processed');

$id = get_filter_request_var('host_id', FILTER_VALIDATE_INT);

$alive = db_fetch_row_prepared ("SELECT * FROM host 
			WHERE id = ? AND disabled != 'on' AND status BETWEEN 2 AND 3", array($id));

if (!$alive) {
	print 'Disabled/down device. Nothing to do<br/><br/>';
} else {

	$out = plugin_snver_get_info($id);

	print $out;

	print '<br/><br/>';

	if ($number_of_hosts > 0) {
		print plugin_snver_get_history($id,$out);

	}
	else {
		print 'History data store disabled';
	}
}

