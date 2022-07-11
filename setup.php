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
	api_plugin_register_hook('snver', 'top_header_tabs', 'snver_show_tab', 'setup.php');
	api_plugin_register_hook('snver', 'top_graph_header_tabs', 'snver_show_tab', 'setup.php');
	api_plugin_register_hook('snver', 'host_device_remove', 'plugin_snver_device_remove', 'setup.php');
	api_plugin_register_hook('snver', 'config_settings', 'plugin_snver_config_settings', 'setup.php');
	api_plugin_register_hook('snver', 'poller_bottom', 'plugin_snver_poller_bottom', 'setup.php');

	api_plugin_register_realm('snver', 'snver.php,snver_tab.php,', 'Plugin SNVer - view', 1);

	plugin_snver_setup_database();
}

function plugin_snver_config_settings() {

        global $tabs, $settings, $config;

        $tabs['snver'] = 'SNVer';

        $settings['snver'] = array(
        	'snver_hosts_processed' => array(
                	'friendly_name' => 'Run periodically and store SNVer history',
                	'description'   => 'If enabled, every poller run SNVer detects information about several devices and store results.',
                	'method'        => 'drop_array',
                	'array'         => array(
                        	'0'    => 'Disabled',
                        	'10'   => '10 devices',
                        	'50'   => '50 devices',
                        	'100'  => '100 devices',
                	),
                	'default'       => '0',
		),
		'snver_history' => array(
                	'friendly_name' => 'Recheck after',
                	'description'   => 'The shortest possible interval after which new testing will occur',
                	'method'        => 'drop_array',
                	'array'         => array(
                        	'1'    => '1 day',
                        	'7'    => '7 days',
                        	'30'   => '30 days',
                        	'100'  => '100 days',
                	),
                	'default'       => '30',
		),
        );

}


function plugin_snver_poller_bottom () {
	global $config;

	include_once('./plugins/snver/functions.php');

    	list($micro,$seconds) = explode(" ", microtime());
    	$start = $seconds + $micro;

    	$now = time();
    	$done = 0;

	$number_of_hosts = read_config_option('snver_hosts_processed');
	$snver_history = read_config_option('snver_history');

	if ($number_of_hosts > 0) {
	
   		$hosts = db_fetch_assoc ('SELECT * FROM host AS h1 LEFT JOIN plugin_snver_history AS h2 ON
    					 h1.id = h2.host_id 
    					 WHERE (h2.last_check IS NULL OR now() > date_add(h2.last_check, interval ' . $snver_history . ' day)) 
    					 limit ' . $number_of_hosts);

	    	if (cacti_sizeof($hosts) > 0)      {
        		foreach ($hosts as $host)       {
				
        			db_execute ("REPLACE INTO plugin_snver_history (host_id,data,last_check) VALUES (" .
        			$host['id'] . ",'" . addslashes(plugin_snver_get_info($host['id'])) . "', now())");
				$done++;
			}

		}
	}

	list($micro,$seconds) = explode(" ", microtime());
	$total_time = $seconds + $micro - $start;

	cacti_log('SNVer STATS: hosts processed/max: ' . $done . '/' . $number_of_hosts . '. Duration: ' . round($total_time,2));
}




function plugin_snver_device_remove($device_id) {

	db_execute_prepared('DELETE FROM plugin_snver_history WHERE host_id = ?', array($device_id));
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

	$data = array();
	$data['columns'][] = array('name' => 'host_id', 'type' => 'int(11)', 'NULL' => false);
	$data['columns'][] = array('name' => 'data', 'type' => 'varchar(512)', 'NULL' => true);
	$data['columns'][] = array('name' => 'last_check', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['primary'] = 'host_id';
	$data['type'] = 'InnoDB';
	$data['comment'] = 'snver history';
	api_plugin_db_table_create ('snver', 'plugin_snver_history', $data);



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

function snver_show_tab () {
        global $config;
        if (api_user_realm_auth('snver.php')) {
                $cp = false;
                if (basename($_SERVER['PHP_SELF']) == 'snver.php')
                $cp = true;
                print '<a href="' . $config['url_path'] . 'plugins/snver/snver_tab.php"><img src="' . $config['url_path'] . 'plugins/snver/images/tab_snver' . ($cp ? '_down': '') . '.gif" alt="snver" align="absmiddle" border="0"></a>';
        }
}


function plugin_snver_uninstall ()	{

	if (sizeof(db_fetch_assoc("SHOW TABLES LIKE 'plugin_snver_steps'")) > 0 ) {
		db_execute("DROP TABLE `plugin_snver_steps`");
        }
	if (sizeof(db_fetch_assoc("SHOW TABLES LIKE 'plugin_snver_organizations'")) > 0 ) {
		db_execute("DROP TABLE `plugin_snver_organizations`");
        }
	if (sizeof(db_fetch_assoc("SHOW TABLES LIKE 'plugin_snver_history'")) > 0 ) {
		db_execute("DROP TABLE `plugin_snver_history`");
        }
}

function plugin_snver_upgrade_database() {

        global $config;

        $info = parse_ini_file($config['base_path'] . '/plugins/snver/INFO', true);
        $info = $info['info'];

        $current = $info['version'];
        $oldv    = db_fetch_cell('SELECT version FROM plugin_config WHERE directory = "snver"');

        if (!cacti_version_compare($oldv, $current, '=')) {

                if (cacti_version_compare($oldv, '0.4', '<')) {

			$data = array();
			$data['columns'][] = array('name' => 'host_id', 'type' => 'int(11)', 'NULL' => false);
			$data['columns'][] = array('name' => 'data', 'type' => 'varchar(255)', 'NULL' => true);
			$data['columns'][] = array('name' => 'last_check', 'type' => 'timestamp', 'NULL' => true, 'default' => '0000-00-00 00:00:00');
			$data['primary'] = 'host_id';
			$data['type'] = 'InnoDB';
			$data['comment'] = 'snver history';
			api_plugin_db_table_create ('snver', 'plugin_snver_history', $data);
		}
	}
}




function plugin_snver_version()	{
	global $config;

	$info = parse_ini_file($config['base_path'] . '/plugins/snver/INFO', true);
	return $info['info'];
}


function plugin_snver_check_config () {
	
	plugin_snver_upgrade_database();
	return true;
}


function plugin_snver_device_edit_top_links (){
	print "<br/><span class='linkMarker'>* </span><a id='snver_info' data-snver_id='" . get_request_var('id') . "' href=''>" . __('SNVer') . "</a>";
}


function plugin_snver_host_edit_bottom () {
	global $config;
	print get_md5_include_js($config['base_path'].'/plugins/snver/snver.js');
}



