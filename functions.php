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


// fix for PHP 5.4
if (!function_exists('array_column')) {
	function array_column($array,$column_name) {
		return array_map(function($element) use($column_name) {
			return $element[$column_name];
		}, $array);
	}
}

function snver_get_allowed_devices($user_id, $array = false) {

	$x  = 0;
	$us = read_user_setting('hide_disabled', false, false, $user_id);

	if ($us == 'on') {
		set_user_setting('hide_disabled', '', $user_id);
	}

	$allowed = get_allowed_devices('', 'null', -1, $x, $user_id);

	if ($us == 'on') {
		set_user_setting('hide_disabled', 'on', $user_id);
	}

	if (cacti_count($allowed)) {
		if ($array) {
			return(array_column($allowed, 'id'));
		}
		return implode(',', array_column($allowed, 'id'));
	} else {
		return false;
	}
}


function plugin_snver_get_info($host_id) {
	global $config;
	
	include_once('./lib/snmp.php');

	$out = '';

	if (db_fetch_cell ('SELECT count(*) from plugin_snver_organizations') < 100) {
		return ('Please import SQL data from file plugins/snver/data/ent.sql. It is described in README.md');
	}

	$host = db_fetch_row_prepared ('SELECT * FROM host WHERE id = ?', array($host_id));

	if (!$host) {
		return false;
	}

	if ($host['availability_method'] == 0 || $host['availability_method'] == 3) {
		return ('No SNMP availability method');
	}


	// find organization

	cacti_oid_numeric_format();

	$string = @cacti_snmp_get($host['hostname'], $host['snmp_community'],
		'.1.3.6.1.2.1.1.2.0', $host['snmp_version'],
		$host['snmp_username'], $host['snmp_password'], $host['snmp_auth_protocol'],
		$host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
		$host['snmp_context'], $host['snmp_port'], $host['snmp_timeout'],0);

	if ($string == 'U') {
		return ('Cannot determine sysObjectID, is snmp configured correctly? Maybe host down.');
	} elseif (!$string) {
		return ('No snmp response now. Maybe host down.');
	}

	$out = '<b>Organization:</b><br/>';
	$out .= 'sysObjectID: ' . $string . '<br/>';

	preg_match('/^([a-zA-Z0-9\.: ]+)\.1\.3\.6\.1\.4\.1\.([0-9]+)[a-zA-Z0-9\. ]*$/',$string, $match);
	$id_org = $match[2]; 

	$org = db_fetch_cell_prepared ('SELECT organization FROM plugin_snver_organizations WHERE id = ?',
		array($id_org));

	$out .= "Organization: $org (id: $id_org) <br/><br/>";


	$out .= '<b>Entity MIB:</b><br/>';

	$data_descr = @cacti_snmp_walk($host['hostname'], $host['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.2', $host['snmp_version'], $host['snmp_username'], $host['snmp_password'], 
		$host['snmp_auth_protocol'], $host['snmp_priv_passphrase'], $host['snmp_priv_protocol'], $host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

	$data_name = @cacti_snmp_walk($host['hostname'], $host['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.7', $host['snmp_version'], $host['snmp_username'], $host['snmp_password'], 
		$host['snmp_auth_protocol'], $host['snmp_priv_passphrase'], $host['snmp_priv_protocol'], $host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

	$data_hardwarerev = @cacti_snmp_walk($host['hostname'], $host['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.8', $host['snmp_version'], $host['snmp_username'], $host['snmp_password'], 
		$host['snmp_auth_protocol'], $host['snmp_priv_passphrase'], $host['snmp_priv_protocol'], $host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

	$data_firmwarerev = @cacti_snmp_walk($host['hostname'], $host['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.9', $host['snmp_version'], $host['snmp_username'], $host['snmp_password'], 
		$host['snmp_auth_protocol'], $host['snmp_priv_passphrase'], $host['snmp_priv_protocol'], $host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

	$data_softwarerev = @cacti_snmp_walk($host['hostname'], $host['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.10', $host['snmp_version'], $host['snmp_username'], $host['snmp_password'], 
		$host['snmp_auth_protocol'], $host['snmp_priv_passphrase'], $host['snmp_priv_protocol'], $host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

	$data_serialnum = @cacti_snmp_walk($host['hostname'], $host['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.11', $host['snmp_version'], $host['snmp_username'], $host['snmp_password'], 
		$host['snmp_auth_protocol'], $host['snmp_priv_passphrase'], $host['snmp_priv_protocol'], $host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

	$data_mfgname = @cacti_snmp_walk($host['hostname'], $host['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.12', $host['snmp_version'], $host['snmp_username'], $host['snmp_password'], 
		$host['snmp_auth_protocol'], $host['snmp_priv_passphrase'], $host['snmp_priv_protocol'], $host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

	$data_modelname = @cacti_snmp_walk($host['hostname'], $host['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.13', $host['snmp_version'], $host['snmp_username'], $host['snmp_password'], 
		$host['snmp_auth_protocol'], $host['snmp_priv_passphrase'], $host['snmp_priv_protocol'], $host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

	$data_mfgdate = @cacti_snmp_walk($host['hostname'], $host['snmp_community'],'1.3.6.1.2.1.47.1.1.1.1.17', $host['snmp_version'], $host['snmp_username'], $host['snmp_password'], 
		$host['snmp_auth_protocol'], $host['snmp_priv_passphrase'], $host['snmp_priv_protocol'], $host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

	if (cacti_sizeof($data_descr) > 0) {
		foreach ($data_descr as $key=>$val) {

			if (!empty($data_hardwarerev[$key]['value']) || !empty($data_firmwarerev[$key]['value']) || !empty($data_softwarerev[$key]['value']) ||
				!empty($data_serialnum[$key]['value'])) {

				$out .= $data_name[$key]['value'] ? 'Name: ' . $data_name[$key]['value'] . '<br/>': '';
				$out .= $val['value'] ? 'Description: ' . $val['value'] . '<br/>': '';
				$out .= !empty($data_hardwarerev[$key]['value']) ? 'HW revision: ' . $data_hardwarerev[$key]['value'] . '<br/>': '';
				$out .= !empty($data_firmwarerev[$key]['value']) ? 'FW revision: ' . $data_firmwarerev[$key]['value'] . '<br/>': '';
				$out .= !empty($data_softwarerev[$key]['value']) ? 'SW revision: ' . $data_softwarerev[$key]['value'] . '<br/>': '';
				$out .= !empty($data_serialnum[$key]['value']) ? 'Serial number: ' . $data_serialnum[$key]['value'] . '<br/>': '';
				$out .= !empty($data_mfgname[$key]['value']) ? 'Manufact. name: ' . $data_mfgname[$key]['value'] . '<br/>': '';
				$out .= !empty($data_modelname[$key]['value']) ? 'Model name: ' . $data_modelname[$key]['value'] . '<br/>': '';
				if (!empty($data_mfgdate[$key])) {
					$data_mfgdate[$key]['value'] = str_replace(' ','',$data_mfgdate[$key]['value']);
					$man_year = hexdec(substr($data_mfgdate[$key]['value'],0,4));
					$man_month = str_pad(hexdec(substr($data_mfgdate[$key]['value'],4,2)),2,'0',STR_PAD_LEFT);
					$man_day = str_pad(hexdec(substr($data_mfgdate[$key]['value'],6,2)),2,'0',STR_PAD_LEFT);
					if ($man_year != 0) {
						$out .= 'Manufactory date: ' . $man_year . '-' . $man_month . '-' . $man_day . '<br/>';
					}
				}
				$out .= '<br/>';
			}
		}
	} else {
		$out .= 'Device doesn\'t support Entity MIB<br/><br/>';
	}

	// end of entity mib

	$macs = @cacti_snmp_walk($host['hostname'], $host['snmp_community'],
		'.1.3.6.1.2.1.2.2.1.6', $host['snmp_version'],
		$host['snmp_username'], $host['snmp_password'], $host['snmp_auth_protocol'],
		$host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
		$host['snmp_context'], $host['snmp_port'], $host['snmp_timeout'],1);

	if ($string == 'U' || $string == '') {
		$string = 'Cannot find MAC address. Device may not support it.';
	}

	$count = 0;
	$out .= '<b>MAC address:</b><br/>';

	$out .= '<table class="cactiTable"><tr>';

	foreach ($macs as $mac) {
		if (strlen($mac['value']) > 1) {
			$out .= '<td>' . $mac['value'] . '</td>';
			if ($count == 5) {
				$out .= '</tr><tr>';
				$count = 0;
			}
			else {
				$count++;
			}
		}
	}

	$out .= '</tr></table>';
	$out .= '<br/><br/>';

	$out .= '<b>Vendor specific:</b><br/>';

	$steps = db_fetch_assoc_prepared ('SELECT * FROM plugin_snver_steps WHERE org_id = ? AND mandatory = "yes" ORDER BY method',
		array($id_org));
	foreach ($steps as $step) {
		if (cacti_sizeof($step)) {
			if ($step['method'] == 'info') {
				$out .= 'Info: ' . $step['description'] . '<br/>';
			}
			if ($step['method'] == 'get') {
				$data = @cacti_snmp_get($host['hostname'], $host['snmp_community'],
					$step['oid'], $host['snmp_version'],
					$host['snmp_username'], $host['snmp_password'], $host['snmp_auth_protocol'],
					$host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
					$host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

				if (preg_match ('#' . $step['result'] . '#', $data, $matches) !== false) {
					$out .= ucfirst($step['description']) . ': ' . $matches[0] . '<br/>';
				} else {
					$out .= ucfirst($step['description']) . ': ' . $data . ' (cannot find specified regexp, so display all)<br/>';
				}
			}
			if ($step['method'] == 'walk') {
				$data = @cacti_snmp_walk($host['hostname'], $host['snmp_community'],
						$step['oid'], $host['snmp_version'],
						$host['snmp_username'], $host['snmp_password'], $host['snmp_auth_protocol'],
						$host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
						$host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

				if (cacti_sizeof($data) > 0) {
					foreach ($data as $row) {
						if (preg_match ('#' . $step['result'] . '#', $row['value'], $matches) !== false) {
							if (strlen($matches[0]) > 0) {
								$out .= ucfirst($step['description']) . ': ' . $matches[0] . '<br/>';
							}
						} else {
							$out .= ucfirst($step['description']) . ': ' . $row['value'] . ' (cannot find specified regexp, so display all)<br/>';
						}
					}
				} else {
					$out .= "I don't know, how to get the information about " . $step['description'] . "<br/>";
				}
			}
			if ($step['method'] == 'table') {
				$ind_des = explode (',', $step['table_items']);
				foreach ($ind_des as $a) {
					list ($i,$d) = explode ('-', $a);
					$oid_suff[] = $i;
					$desc[] = $d;
				} 
				
				$out .= '<table class="cactiTable"><tr>';
				foreach ($desc as $d) {
					$out .= '<th>' . $d . ' </th>';
				}
				
				$out .= '</tr>';

				foreach ($oid_suff as $i) {

					$data[$i] = @cacti_snmp_walk($host['hostname'], $host['snmp_community'],
						$step['oid'] . '.' . $i, $host['snmp_version'],
						$host['snmp_username'], $host['snmp_password'], $host['snmp_auth_protocol'],
						$host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
						$host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);
					$last = $i;
				}

				// display columns as rows only
				for ($f = 0; $f < count($data[$last]);$f++) {
					$out .= "<tr>";

					foreach ($oid_suff as $i) {
						$out .= "<td>" . $data[$i][$f]['value'] . " </td>";
					}
					$out .= "</tr>";
				}

				$out .= '</table>';
			}
		} else {
			$out .= "I don't know, how to get the information about device<br/>";
		}
	}

	$out .= '<br/><br/>';

	return ($out);
}



function plugin_snver_get_history($host_id) {

	$out = array();

	$data_his = db_fetch_assoc_prepared ('SELECT host_id,last_check, data FROM plugin_snver_history
		WHERE host_id = ? ORDER BY last_check DESC', array($host_id));

	if (cacti_sizeof($data_his)) {
		foreach ($data_his as $row) {
			$out[$row['last_check']] = stripslashes($row['data']);
		}
	}

	return ($out);
}


function snver_find() {

	if (read_config_option('snver_records') == 0) {
		print 'Store history is not allowed. Nothing to do ...';
		return false;
	}

	$find = get_filter_request_var('find', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^([a-zA-Z0-9_\-\.:]{3,})$/')));
	if (strlen($find) < 3) {
		print 'At least 3 chars...';
		return false;
	}

	$data = db_fetch_assoc ('SELECT id,description,data,last_check FROM host 
		LEFT JOIN plugin_snver_history ON host.id = plugin_snver_history.host_id 
		WHERE plugin_snver_history.data LIKE "%' . $find . '%"');

	if (cacti_sizeof($data)) {
		foreach ($data as $row) {
			print '<b>Host ' . $row['description'] . '(ID: ' . $row['id'] . ')<br/>';
			print 'Date ' . $row['last_check'] . '</b><br/>';
			print $row['data'] . '<br/><br/>';	
		}
	} else {
		print 'Not found';
	}
}


function plugin_snver_get_info_optional($host_id) {
	global $config;

	include_once('./lib/snmp.php');

	$out = '';

	$host = db_fetch_row_prepared ('SELECT * FROM host WHERE id = ?', array($host_id));

	if (!$host) {
		return false;
	}

	if ($host['availability_method'] == 0 || $host['availability_method'] == 3) {
		//return ('No SNMP availability method');
		return false;
	}

	if (function_exists('snmp_set_oid_output_format')) {
		snmp_set_oid_output_format (SNMP_OID_OUTPUT_NUMERIC);
	}

	// find organization

	cacti_oid_numeric_format();

	$string = @cacti_snmp_get($host['hostname'], $host['snmp_community'],
                '.1.3.6.1.2.1.1.2.0', $host['snmp_version'],
                $host['snmp_username'], $host['snmp_password'], $host['snmp_auth_protocol'],
                $host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
                $host['snmp_context'], $host['snmp_port'], $host['snmp_timeout'],1);

	if ($string == 'U') {  //!!! resit, at vracim i duvod a to i u zakladniho infa
		//return ('Cannot determine sysObjectID, is snmp configured correctly? Maybe host down');
		return false;
	} elseif (!$string) {
		return false;
	}

	preg_match('/^([a-zA-Z0-9\.: ]+)\.1\.3\.6\.1\.4\.1\.([0-9]+)[a-zA-Z0-9\. ]*$/',$string, $match);
	$id_org = $match[2]; 

	$out .= '<b>Vendor specific optional (not saved in history):</b><br/>';

	$steps = db_fetch_assoc_prepared ('SELECT * FROM plugin_snver_steps WHERE org_id = ? AND mandatory="no" ORDER BY method',
		array($id_org));

	foreach ($steps as $step) {
		if (cacti_sizeof($step)) {
			if ($step['method'] == 'info') {
				$out .= 'Info: ' . $step['description'] . '<br/>';
			}
			elseif ($step['method'] == 'get') {
				$data = @cacti_snmp_get($host['hostname'], $host['snmp_community'],
					$step['oid'], $host['snmp_version'],
					$host['snmp_username'], $host['snmp_password'], $host['snmp_auth_protocol'],
					$host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
					$host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

				if (preg_match ('#' . $step['result'] . '#', $data, $matches) !== false) {
					$out .= ucfirst($step['description']) . ': ' . $matches[0] . '<br/>';
				} else {
					$out .= ucfirst($step['description']) . ': ' . $data . ' (cannot find specified regexp, so display all)<br/>';
				}
			} elseif ($step['method'] == 'walk') {
				$data = @cacti_snmp_walk($host['hostname'], $host['snmp_community'],
						$step['oid'], $host['snmp_version'],
						$host['snmp_username'], $host['snmp_password'], $host['snmp_auth_protocol'],
						$host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
						$host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

				if (cacti_sizeof($data) > 0) {
					foreach ($data as $row) {
						if (preg_match ('#' . $step['result'] . '#', $row['value'], $matches) !== false) {
							if (strlen($matches[0]) > 0) {
								$out .= ucfirst($step['description']) . ': ' . $matches[0] . '<br/>';
							}
						} else {
							$out .= ucfirst($step['description']) . ': ' . $row['value'] . ' (cannot find specified regexp, so display all)<br/>';
						}
					}
				} else {
					$out .= "I don't know, how to get the information about " . $step['description'] . "<br/>";
				}
			} elseif ($step['method'] == 'table') {
				$ind_des = explode (',', $step['table_items']);
				foreach ($ind_des as $a) {
					list ($i,$d) = explode ('-', $a);
					$oid_suff[] = $i;
					$desc[] = $d;
				}

				$out .= '<table class="cactiTable"><tr>';
				foreach ($desc as $d) {
					$out .= '<th>' . $d . ' </th>';
				}

				$out .= '</tr>';

				foreach ($oid_suff as $i) {

					$data[$i] = @cacti_snmp_walk($host['hostname'], $host['snmp_community'],
						$step['oid'] . '.' . $i, $host['snmp_version'],
						$host['snmp_username'], $host['snmp_password'], $host['snmp_auth_protocol'],
						$host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
						$host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);
					$last = $i;
				}

				// display columns as rows only
				for ($f = 0; $f < count($data[$last]);$f++) {
					$out .= "<tr>";

					foreach ($oid_suff as $i) {
						$out .= "<td>" . $data[$i][$f]['value'] . " </td>";
					}
					$out .= "</tr>";
				}

				$out .= '</table>';
			}
		} else {
			$out .= "I don't know, how to get the information about device<br/>";
		}
	}

	$out .= '<br/><br/>';

	return ($out);
}
