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

	if (db_fetch_cell ('SELECT count(*) from plugin_snver_organizations') < 100) {
		print __('Please import SQL data from file plugins/snver/data/ent.sql. It is described in README.md');
		return false;
	}

	$host = db_fetch_row_prepared ('SELECT * FROM host WHERE id = ?', array($host_id));

	if (!$host) {
		return false;
	}
	
	if ($host['availability_method'] == 0 || $host['availability_method'] == 3) {
		print 'No SNMP availability method';
		return false;
	} 
	
	if (function_exists('snmp_set_oid_output_format')) {
		snmp_set_oid_output_format (SNMP_OID_OUTPUT_NUMERIC);
	}

	// find organization

	$string = @cacti_snmp_get($host['hostname'], $host['snmp_community'],
                '.1.3.6.1.2.1.1.2.0', $host['snmp_version'],
                $host['snmp_username'], $host['snmp_password'], $host['snmp_auth_protocol'],
                $host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
                $host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

	if ($string == 'U') {
		print 'Cannot determine sysObjectID, is snmp configured correctly? Maybe host down';
		return false;
	}

	print '<b>Organization:</b><br/>';
	print 'sysObjectID: ' . $string . '<br/>';

	if (strpos($string, '::') !== false) {	// for SNMPv2-MIB::sysObjectID.0 = OID: SNMPv2-SMI::enterprises.311.1.1.3.1.3 (or ::enterprises.xyz)
		$pos1 = strpos($string, '::enterprises.');
		$pos2 = strpos($string, '.', $pos1+15);
		if ($pos2 === false) {
			$pos2 = strlen($string);
		}
		$id_org = substr($string, $pos1+14, $pos2-$pos1-14);
	} else {	// for .1.3.6.1.2.1.1.2.0 = OID: .1.3.6.1.4.1.311.1.1.3.1.3
		$pos1 = strpos($string, '.1.3.6.1.4.1.');
		$pos2 = strpos($string, '.', $pos1+14);
		$id_org = substr($string, $pos1+13, $pos2-$pos1-13);
	}

	$org = db_fetch_cell_prepared ('SELECT organization FROM plugin_snver_organizations WHERE id = ?',
		array($id_org));

	print "Organization: $org (id: $id_org) <br/><br/>";

	print '<b>Entity MIB:</b><br/>';

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

                                print $data_name[$key]['value'] ? 'Name: ' . $data_name[$key]['value'] . '<br/>': '';
                                print $val['value'] ? 'Description: ' . $val['value'] . '<br/>': '';
                                print !empty($data_hardwarerev[$key]['value']) ? 'HW revision: ' . $data_hardwarerev[$key]['value'] . '<br/>': '';
                                print !empty($data_firmwarerev[$key]['value']) ? 'FW revision: ' . $data_firmwarerev[$key]['value'] . '<br/>': '';
                                print !empty($data_softwarerev[$key]['value']) ? 'SW revision: ' . $data_softwarerev[$key]['value'] . '<br/>': '';
                                print !empty($data_serialnum[$key]['value']) ? 'Serial number: ' . $data_serialnum[$key]['value'] . '<br/>': '';
                                print !empty($data_mfgname[$key]['value']) ? 'Manufact. name: ' . $data_mfgname[$key]['value'] . '<br/>': '';
                                print !empty($data_modelname[$key]['value']) ? 'Model name: ' . $data_modelname[$key]['value'] . '<br/>': '';
                                if (!empty($data_mfgdate[$key])) {
                                        $data_mfgdate[$key]['value'] = str_replace(' ','',$data_mfgdate[$key]['value']);
                                        $man_year = hexdec(substr($data_mfgdate[$key]['value'],0,4));
                                        $man_month = str_pad(hexdec(substr($data_mfgdate[$key]['value'],4,2)),2,'0',STR_PAD_LEFT);
                                        $man_day = str_pad(hexdec(substr($data_mfgdate[$key]['value'],6,2)),2,'0',STR_PAD_LEFT);
                                        if ($man_year != 0) {
                                                print 'Manufactory date: ' . $man_year . '-' . $man_month . '-' . $man_day . '<br/>';
                                        }
                                }
                                echo '<br/>';
			}
		}
	} else {
		print 'Device doesn\'t support Entity MIB<br/><br/>';
	}

	// end of entity mib

	print '<b>Vendor specific:</b><br/>';

	$steps = db_fetch_assoc_prepared ('SELECT * FROM plugin_snver_steps WHERE org_id = ? ORDER BY method',
		array($id_org));

	foreach ($steps as $step) {
		if (cacti_sizeof($step)) {
			if ($step['method'] == 'info') {
				print 'Info: ' . $step['description'] . '<br/>';
			}
			if ($step['method'] == 'get') {
				$data = @cacti_snmp_get($host['hostname'], $host['snmp_community'],
					$step['oid'], $host['snmp_version'],
					$host['snmp_username'], $host['snmp_password'], $host['snmp_auth_protocol'],
					$host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
					$host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

				if (preg_match ('#' . $step['result'] . '#', $data, $matches) !== false) {
					print ucfirst($step['description']) . ': ' . $matches[0] . '<br/>';
				} else {
					print ucfirst($step['description']) . ': ' . $data . ' (cannot find specified regexp, so display all)<br/>';
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
								print ucfirst($step['description']) . ': ' . $matches[0] . '<br/>';
							}
						} else {
							print ucfirst($step['description']) . ': ' . $row['value'] . ' (cannot find specified regexp, so display all)<br/>';
						}
					}
				} else {
					print "I don't know, how to get the information about " . $step['description'] . "<br/>";
				}
			}
			if ($step['method'] == 'table') {
				$ind_des = explode (',', $step['table_items']);
				foreach ($ind_des as $a) {
					list ($i,$d) = explode ('-', $a);
					$oid_suff[] = $i;
					$desc[] = $d;
				} 
				
				echo '<table class="cactiTable"><tr>';
				foreach ($desc as $d) {
					echo '<th>' . $d . ' </th>';
				}
				
				echo '</tr>';

				
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
					echo "<tr>";

					foreach ($oid_suff as $i) {

						echo "<td>" . $data[$i][$f]['value'] . " </td>";
					}
					echo "</tr>";
				}
				
				echo '</table>';
			}
			
		} else {
			print "I don't know, how to get the information about device<br/>";
		}
	}

	print '<br/><br/>';
}

