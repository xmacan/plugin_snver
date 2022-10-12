# plugin_snver for Cacti

## Try find serial number, version and important information about device:

![SNVer](https://github.com/xmacan/plugin_snver/blob/master/img/snver.png)

A lot of vendors support SNMP Entity MIB (HPE, Synology, Cisco, Mikrotik, Fortinet, ...).
There are information about serial numbers, part numbers, versions, ..
For few vendors plugin I added vendor specific oids (Aruba, Mikrotik, Synology, ..)

## Author
Petr Macek (petr.macek@kostax.cz)


## Installation
Copy directory snver to plugins directory (keep lowercase)
Check file permission (Linux/unix - readable for www server)  
Import ent.sql (described in data/README.md)
Enable plugin (Console -> Plugin management)  
Configure plugin (Console -> Settings -> SNVer tab

## How to use?
You will see information about serial numbers and version on each supported device
You can use sperated SNVer tab or link on edit device page

## Upgrade    
Copy and rewrite files  
Check file permission (Linux/unix - readable for www server)  
Disable and deinstall old version (Console -> Plugin management)  
Import ent.sql (described in data/README.md)
Install and enable new version (Console -> Plugin management)   
    
## Possible Bugs or any ideas?
If you find a problem, let me know via github or https://forums.cacti.net
   

## Changelog
	--- 0.6
		Add search in history
		Add notification email
		Add more history records
		Add exclude for email notification
		Add mac address query
		Poller function speedup
	--- 0.5
		Fix lenght of stored history
		Fix php warning about $config variable
	--- 0.4
		Add history
		Fix unfiltered variable
	--- 0.3
		Add jquery select
		Add SNVer to device edit links, click-only calling
		Add SNVer tab for users without console permission
		Fix working with php snmp module
		Fix php warning - entity mib may not provide all information
		Fix detecting when host down or no snmp community
		Fix for missing function in PHP 5.4
	--- 0.2
		Add Entity MIB
	--- 0.1
		Beginning

