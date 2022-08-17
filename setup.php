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
        	'snver_records' => array(
                	'friendly_name' => 'How many changes store',
                	'description'   => 'How many history (changed) records keep for each device',
                	'method'        => 'drop_array',
                	'array'         => array(
                        	'1'    => 'Only last state',
                        	'5'   => '5 records',
                        	'10'   => '10 records',
                	),
                	'default'       => '5',
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
        	'snver_email_notify' => array(
                	'friendly_name' => 'Send email on SNVer information change',
                	'description'   => 'If SNVer find change, send email',
                	'method'        => 'checkbox',
                	'default'       => 'off',
		),
        	'snver_email_notify_exclude' => array(
                	'friendly_name' => 'Excluded notification Host IDs',
                	'description'   => 'Some devices report hw changes too often. You can exclude these host from email notification. Insert Host IDs, comma separator',
                	'method'        => 'textbox',
                	'max_length'	=> '500',
                	'default'       => '',
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
	$snver_records = read_config_option('snver_records');	

	if ($number_of_hosts > 0) {
		// not tested 
   		$hosts1 = db_fetch_assoc ("(SELECT h1.id as id,last_check as xx FROM host AS h1 LEFT JOIN plugin_snver_history AS h2 
   			ON h1.id=h2.host_id WHERE h1.disabled != 'on' AND h1.status BETWEEN 2 AND 3 AND h2.last_check IS NULL)
   			LIMIT " . $number_of_hosts);
   			
   		$returned = cacti_sizeof($hosts1);	

 		// old hosts  		
   		$hosts2 = db_fetch_assoc ("select h1.host_id as id,h1.last_check as xx 
   			from plugin_snver_history as h1 join host on host.id=h1.host_id 
   			where host.disabled != 'on' and host.status between 2 and 3 
   				and h1.last_check = (select max(h2.last_check) 
   					from plugin_snver_history as h2 where h1.host_id = h2.host_id) 
   					having now() > date_add(xx, interval " . $snver_history . " day)
   					limit " . ($number_of_hosts-$returned) );
   		
   		
		$hosts = array_merge($hosts1,$hosts2);

	    	if (cacti_sizeof($hosts) > 0)      {
        		foreach ($hosts as $host)       {

        			$data_act = plugin_snver_get_info($host['id']);

				$data_his = db_fetch_row_prepared ('SELECT * FROM plugin_snver_history 
					WHERE host_id = ? ORDER BY last_check DESC LIMIT 1', array($host['id']));

				if ($data_his) {

                			$data_his = stripslashes($data_his['data']);

                			if (strcmp ($data_his, $data_act) === 0) {	// only update last check
        					db_execute ('UPDATE plugin_snver_history set last_check = now() 
        						WHERE host_id = ' . $host['id'] . ' ORDER BY last_check DESC LIMIT 1');
    					} else {

        					db_execute ("INSERT INTO plugin_snver_history (host_id,data,last_check) VALUES (" .
        					$host['id'] . ",'" . addslashes($data_act) . "', now())");

 	     					db_execute ('DELETE FROM plugin_snver_history WHERE host_id = ' . $host['id'] . ' order by last_check LIMIT ' .  $snver_records);
						
						$excluded = explode(',', read_config_option('snver_email_notify_exclude'));
						
						if (read_config_option('snver_email_notify')) {
							if (in_array($host['id'], $excluded)) {
 	 		     					cacti_log('Plugin SNVer - host changed (id:' . $host['id'] . '),  excluded from notification');
							} else {

        							$emails = db_fetch_cell_prepared ('SELECT emails FROM plugin_notification_lists 
	        							LEFT JOIN host 
        								ON plugin_notification_lists.id = host.thold_host_email
        								WHERE host.id = ?', array($host['id']));

       								 send_mail($emails, 
									read_config_option('settings_from_email'),
									'Plugin SNVer - HW changed', 
									'I have found any HW/serial number change on Host ' . $host['id'] . ':<br/>' . PHP_EOL .
									$data_act . '<br/><br/>' . PHP_EOL . 'Older data:<br/>' . PHP_EOL . $data_his, '', '', true); 

 	 	     						cacti_log('Plugin SNVer - host changed (id:' . $host['id'] . '), sending email notification');								
							}
        							
        					} else { // only log
 	 	     					cacti_log('Plugin SNVer - host changed (id:' . $host['id'] . '),  only logging');
        					}
                			}
        			}
        			else {
       					db_execute ("INSERT INTO plugin_snver_history (host_id,data,last_check) VALUES (" .
        					$host['id'] . ",'" . addslashes($data_act) . "', now())");
        			}
       			
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
	$data['columns'][] = array('name' => 'data', 'type' => 'text', 'NULL' => true);
	$data['columns'][] = array('name' => 'last_check', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
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
	// uptime is problem for history - db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method,table_items) VALUES (14823,'APs','.1.3.6.1.4.1.14823.2.3.3.1.2.1.1','.*','table','1-mac,2-name,3-ip,4-serial,6-model,9-uptime')");
	db_execute ("INSERT INTO plugin_snver_steps (org_id,description,oid,result,method,table_items) VALUES (14823,'APs','.1.3.6.1.4.1.14823.2.3.3.1.2.1.1','.*','table','1-mac,2-name,3-ip,4-serial,6-model')");
	
	
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

                if (cacti_version_compare($oldv, '0.4', '<=')) {

			$data = array();
			$data['columns'][] = array('name' => 'host_id', 'type' => 'int(11)', 'NULL' => false);
			$data['columns'][] = array('name' => 'data', 'type' => 'varchar(255)', 'NULL' => true);
			$data['columns'][] = array('name' => 'last_check', 'type' => 'timestamp', 'NULL' => true, 'default' => '0000-00-00 00:00:00');
			$data['primary'] = 'host_id';
			$data['type'] = 'InnoDB';
			$data['comment'] = 'snver history';
			api_plugin_db_table_create ('snver', 'plugin_snver_history', $data);
		}

                if (cacti_version_compare($oldv, '0.5', '<=')) {
			db_execute('ALTER TABLE plugin_snver_history MODIFY COLUMN data text');
		}
                if (cacti_version_compare($oldv, '0.6', '<=')) {
			db_execute('ALTER TABLE plugin_snver_history DROP primary key');
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



