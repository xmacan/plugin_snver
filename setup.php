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

function plugin_snver_install () {

	// only for jquery script
	api_plugin_register_hook('snver', 'host_edit_bottom', 'plugin_snver_host_edit_bottom', 'setup.php');
	api_plugin_register_hook('snver', 'device_edit_top_links', 'plugin_snver_device_edit_top_links', 'setup.php');

	api_plugin_register_realm('snver', 'snver.php,', 'Plugin SNVer - view', 1);

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
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'oid', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'result', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'method', 'type' => 'enum("get","walk","info","table")', 'default' => 'get', 'NULL' => false);
	$data['columns'][] = array('name' => 'table_items', 'type' => 'varchar(100)', 'default' => null, 'NULL' => true);

	$data['primary'] = 'id';
	$data['type'] = 'InnoDB';
	$data['comment'] = 'snver data2';
	api_plugin_db_table_create ('snver', 'plugin_snver_steps', $data);


	// vendor specific

	// fortinet
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method) VALUES (12356,'serial','1.3.6.1.4.1.12356.100.1.1.1.0','.*','get')");
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method) VALUES (12356,'version','1.3.6.1.4.1.12356.101.4.1.1.0','.*','get')");

	// mikrotik
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method) VALUES (14988,'serial','1.3.6.1.4.1.14988.1.1.7.3.0','.*','get')");
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method) VALUES (14988,'SW version','1.3.6.1.4.1.14988.1.1.4.4.0','.*','get')");
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method) VALUES (14988,'Firmware version','1.3.6.1.4.1.14988.1.1.7.4.0','.*','get')");
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method) VALUES (14988,'SW version','1.3.6.1.4.1.14988.1.1.17.1.1.4.1','.*','get')");
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method) VALUES (14988,'hw','SNMPv2-SMI::mib-2.47.1.1.1.1.2.65536','([a-zA-Z0-9_-]){1,20}$','get')");

	// net-snmp - synology
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method) VALUES (8072,'Info - Synology has OrgID 6574, but uses 8072','','','info')");
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method) VALUES (8072,'serial','SNMPv2-SMI::enterprises.6574.1.5.2.0','.*','get')");
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method) VALUES (8072,'version','SNMPv2-SMI::enterprises.6574.1.5.3.0','.*','get')");
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method) VALUES (8072,'hw model','SNMPv2-SMI::enterprises.6574.1.5.1.0','.*','get')");

	// 3Com/H3C
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method) VALUES (25506,'3Com/H3C/HPE','','','info')");
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method) VALUES (11,'3Com/H3C/HPE','','','info')");

	// Aruba/HPE
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method) VALUES (14823,'Serial numbers','1.3.6.1.4.1.14823.2.3.3.1.2.1.1.4','.*','walk')");
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method) VALUES (14823,'version','1.3.6.1.4.1.14823.2.3.3.1.1.4.0','.*','get')");
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method) VALUES (14823,'hw model','1.3.6.1.4.1.14823.2.3.3.1.2.1.1.6','.*','walk')");

	// QNAP
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method,table_items) VALUES (24681,'hw disks','1.3.6.1.4.1.24681.1.3.11.1','.*','table','2-name,5-type,3-temp,7-smart')");

	// Aruba instant AP cluster
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method,table_items) VALUES (14823,'APs','.1.3.6.1.4.1.14823.2.3.3.1.2.1.1','.*','table','1-mac,2-name,3-ip,4-serial,6-model,9-uptime')");
	
	
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


function plugin_snver_device_edit_top_links (){
	print "<br/><span class='linkMarker'>* </span><a id='snver_info' data-snver_id='" . get_request_var('id') . "' href=''>" . __('SNVer') . "</a>";
}


function plugin_snver_host_edit_bottom () {
	global $config;
	print get_md5_include_js($config['base_path'].'/plugins/snver/snver.js');
}

