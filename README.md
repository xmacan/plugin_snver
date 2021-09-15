# plugin_snver for Cacti

## Try find serial number and version of device
For example Synology NAS:  
sysObejctID: OID: .1.3.6.1.4.1.8072.3.2.10  
Organization: net-snmp (id: 8072)  
Description: Synology has OrgID 6574, but uses 8072  
Hw disks: HUA722020ALA330  
Hw disks: HUA722020ALA330  
Hw disks: HUA722020ALA330  
Hw disks: HUA722020ALA330  
Hw disks: WD2003FZEX-00SRLA0  
Hw disks: HUA722020ALA330  
Hw disks: HUA722020ALA330  
Hw disks: HUA722020ALA330  
Hw disks: HUA722020ALA330  
Hw disks: HUA722020ALA330  
Hw model: RS2211+  
Serial: B2J7changed  
Version: DSM 6.2-25556  

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
	--- 0.1
		Beginning


