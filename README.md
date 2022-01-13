# plugin_snver for Cacti

## Try find serial number, version and important information about device:

![SNVer](https://github.com/xmacan/plugin_snver/blob/master/img/snver.png)


Now supporting Mikrotik, Synology, 3com, Aruba, QNAP, ...

## Author
Petr Macek (petr.macek@kostax.cz)


## Installation
Copy directory uptime to plugins directory  
Check file permission (Linux/unix - readable for www server)  
Enable plugin (Console -> Plugin management)  

## How to use?
You will see information about serial numbers and version on each supported device

## Upgrade    
Copy and rewrite files  
Check file permission (Linux/unix - readable for www server)  
Disable and deinstall old version (Console -> Plugin management)  
Install and enable new version (Console -> Plugin management)   
    
## Possible Bugs or any ideas?
If you find a problem, let me know via github or https://forums.cacti.net
   

## Changelog
	--- 0.3
		Add SNVer to device edit links, click-only calling
		Fix working with php snmp module
	--- 0.2
		Add Entity MIB
	--- 0.1
		Beginning

