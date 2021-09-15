<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2019-2020 Petr Macek                                      |
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

function plugin_snver_install () {

	api_plugin_register_hook('snver', 'host_edit_bottom', 'plugin_snver_host_edit_bottom', 'setup.php');
	plugin_snver_setup_database();
}


function plugin_snver_setup_database() {

	$data = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false);
	$data['columns'][] = array('name' => 'organization', 'type' => 'varchar(200)', 'NULL' => false);
	$data['primary'] = 'id';
	$data['type'] = 'InnoDB';
	$data['comment'] = 'snver organizations';
	api_plugin_db_table_create ('snver', 'plugin_snver_organizations', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false,'auto_increment' => true);
	$data['columns'][] = array('name' => 'org_id', 'type' => 'int(11)', 'NULL' => false);
	$data['columns'][] = array('name' => 'snmp', 'type' => 'enum("get","walk")', 'default' => 'get', 'NULL' => false);
	$data['columns'][] = array('name' => 'query_type', 'type' => 'varchar(30)', 'NULL' => false);
	$data['columns'][] = array('name' => 'rule_num', 'type' => 'int(3)', 'NULL' => false);
	$data['columns'][] = array('name' => 'oid', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'result', 'type' => 'varchar(255)', 'NULL' => false);
	$data['primary'] = 'id';
	$data['type'] = 'InnoDB';
	$data['comment'] = 'snver data2';
	api_plugin_db_table_create ('snver', 'plugin_snver_steps', $data);

	db_execute ("insert into plugin_snver_steps (org_id,query_type,rule_num,oid,result) values (12356,'serial',1,'1.3.6.1.4.1.12356.100.1.1.1.0','.*')");
	db_execute ("insert into plugin_snver_steps (org_id,query_type,rule_num,oid,result) values (12356,'version',1,'1.3.6.1.4.1.12356.101.4.1.1.0','.*')");
	db_execute ("insert into plugin_snver_steps (org_id,query_type,rule_num,oid,result) values (14988,'serial',1,'1.3.6.1.4.1.14988.1.1.7.3.0','.*')");
	db_execute ("insert into plugin_snver_steps (org_id,query_type,rule_num,oid,result) values (14988,'SW version',1,'1.3.6.1.4.1.14988.1.1.4.4.0','.*')");
	db_execute ("insert into plugin_snver_steps (org_id,query_type,rule_num,oid,result) values (14988,'Firmware version',1,'1.3.6.1.4.1.14988.1.1.7.4.0','.*')");
	db_execute ("insert into plugin_snver_steps (org_id,query_type,rule_num,oid,result) values (14988,'SW version',1,'1.3.6.1.4.1.14988.1.1.17.1.1.4.1','.*')");
	db_execute ("insert into plugin_snver_steps (org_id,query_type,rule_num,oid,result) values (14988,'hw',1,'SNMPv2-SMI::mib-2.47.1.1.1.1.2.65536','([a-zA-Z0-9_-]){1,20}$')");
	db_execute ("insert into plugin_snver_steps (org_id,query_type,rule_num,oid,result,snmp) values (8072,'Description',0,'Synology has OrgID 6574, but uses 8072','','walk')");
	db_execute ("insert into plugin_snver_steps (org_id,query_type,rule_num,oid,result) values (8072,'serial',1,'SNMPv2-SMI::enterprises.6574.1.5.2.0','.*')");
	db_execute ("insert into plugin_snver_steps (org_id,query_type,rule_num,oid,result) values (8072,'version',1,'SNMPv2-SMI::enterprises.6574.1.5.3.0','.*')");
	db_execute ("insert into plugin_snver_steps (org_id,query_type,rule_num,oid,result) values (8072,'hw model',1,'SNMPv2-SMI::enterprises.6574.1.5.1.0','.*')");
	db_execute ("insert into plugin_snver_steps (org_id,query_type,rule_num,oid,result,snmp) values (8072,'hw disks',1,'SNMPv2-SMI::enterprises.6574.2.1.1.3','.*','walk')");
	db_execute ("insert into plugin_snver_steps (org_id,query_type,rule_num,oid,result,snmp) values (11,'Description',0,'3Com comware/H3C','','walk')");
	db_execute ("insert into plugin_snver_steps (org_id,query_type,rule_num,oid,result,snmp) values (25506,'Description',0,'3Com comware/H3C','','walk')");
	db_execute ("insert into plugin_snver_steps (org_id,query_type,rule_num,oid,result,snmp) values (11,'Serial numbers',1,'1.3.6.1.2.1.47.1.1.1.1.11','.*','walk')");
	db_execute ("insert into plugin_snver_steps (org_id,query_type,rule_num,oid,result,snmp) values (25506,'Serial numbers',1,'1.3.6.1.2.1.47.1.1.1.1.11','.*','walk')");
}


function plugin_snver_uninstall ()	{

	if (sizeof(db_fetch_assoc("SHOW TABLES LIKE 'plugin_snver_steps'")) > 0 ) {
		db_execute("DROP TABLE `plugin_snver_steps`");
        }
	if (sizeof(db_fetch_assoc("SHOW TABLES LIKE 'plugin_snver_organizations'")) > 0 ) {
		db_execute("DROP TABLE `plugin_snver_organizations`");
        }
}


function plugin_snver_version()	{
	global $config;

	$info = parse_ini_file($config['base_path'] . '/plugins/snver/INFO', true);
	return $info['info'];
}


function plugin_snver_check_config () {
	return true;
}

function plugin_snver_host_edit_bottom ()	{

	if (!api_user_realm_auth('setup.php')) {
    		print __('Permission denied', 'plugin_snver') . '<br/><br/>';
                return false;
	}

	if (db_fetch_cell ('SELECT count(*) from plugin_snver_organizations') < 100) {
		print __('Please import SQL data from file plugins/snver/data/ent.sql');
		return false;
	}

	$host = db_fetch_row_prepared ('SELECT * FROM host WHERE id = ?', array(get_request_var('id')));

	if (!$host) {
		return false;
	}
	
	// find organization

	$string = @cacti_snmp_get($host['hostname'], $host['snmp_community'],
                '.1.3.6.1.2.1.1.2.0', $host['snmp_version'],
                $host['snmp_username'], $host['snmp_password'], $host['snmp_auth_protocol'],
                $host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
                $host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

	print 'sysObejctID: ' . $string . '<br/>';

	if (strpos($string, '::') !== false) {	// for SNMPv2-MIB::sysObjectID.0 = OID: SNMPv2-SMI::enterprises.311.1.1.3.1.3
		
		$pos1 = strpos($string, '::enterprises.');
		$pos2 = strpos($string, '.', $pos1+15);
		
		$id_org = substr($string, $pos1+14, $pos2-$pos1-14);
	} else {	// for .1.3.6.1.2.1.1.2.0 = OID: .1.3.6.1.4.1.311.1.1.3.1.3
		$pos1 = strpos($string, '.1.3.6.1.4.1.');
		$pos2 = strpos($string, '.', $pos1+14);
		$id_org = substr($string, $pos1+13, $pos2-$pos1-13);
	}

	$org = db_fetch_cell_prepared ('SELECT organization FROM plugin_snver_organizations WHERE id = ?',
		array($id_org));

	print "Organization: $org (id: $id_org) <br/>";

	$desc = db_fetch_cell_prepared ('SELECT oid FROM plugin_snver_steps WHERE rule_num = 0 AND org_id = ?',
		array($id_org));

	if ($desc) {
		print "Description: $desc<br/>";
	}

	$steps = db_fetch_assoc_prepared ('SELECT * FROM plugin_snver_steps WHERE org_id = ? AND rule_num > 0 ORDER BY query_type, rule_num',
		array($id_org));

	foreach ($steps as $step) {
		if (cacti_sizeof($step)) {
			if ($step['snmp'] == 'get') {
				$data = @cacti_snmp_get($host['hostname'], $host['snmp_community'],
					$step['oid'], $host['snmp_version'],
					$host['snmp_username'], $host['snmp_password'], $host['snmp_auth_protocol'],
					$host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
					$host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

				if (preg_match ('#' . $step['result'] . '#', $data, $matches) !== false) {
					print ucfirst($step['query_type']) . ': ' . $matches[0] . '<br/>';
				} else {
					print ucfirst($step['query_type']) . ': ' . $data . ' (cannot find specified regexp, so display all)<br/>';
				}
			} else { // walk
				$data = @cacti_snmp_walk($host['hostname'], $host['snmp_community'],
						$step['oid'], $host['snmp_version'],
						$host['snmp_username'], $host['snmp_password'], $host['snmp_auth_protocol'],
						$host['snmp_priv_passphrase'], $host['snmp_priv_protocol'],
						$host['snmp_context'], $host['snmp_port'], $host['snmp_timeout']);

				if (cacti_sizeof($data) > 0) {
					foreach ($data as $row) {
						if (preg_match ('#' . $step['result'] . '#', $row['value'], $matches) !== false) {
							if (strlen($matches[0]) > 0) {
								print ucfirst($step['query_type']) . ': ' . $matches[0] . '<br/>';
							}
						} else {
							print ucfirst($step['query_type']) . ': ' . $row['value'] . ' (cannot find specified regexp, so display all)<br/>';
						}
					}
				} else {
					print "I don't know, how to get the information about " . $step['query_type'] . "<br/>";
				}
			}
		} else {
			print "I don't know, how to get the information about device<br/>";
		}
	}

	print '<br/><br/>';

}

