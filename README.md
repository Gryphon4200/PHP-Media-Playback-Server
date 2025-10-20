# PHP Media Server

A web-based media playback server written in PHP that provides file management, preset configuration, and media playback capabilities with real-time file monitoring.

## Features

- **Cross-Platform Compatibility**: Works seamlessly on Windows and Ubuntu/Linux
- **File Upload Management**: Upload media files through a web interface
- **Preset Configuration**: Configure and manage media presets
- **Real-Time File Monitoring**: Automatically refreshes when media folder contents change
- **File Management**: View, select, and delete media files
- **Responsive Web Interface**: Clean, user-friendly interface for media management

## Requirements

- **PHP**: 8.0 or higher
- **Operating System**: Windows or Linux/Ubuntu
- **Browser**: Modern web browser with JavaScript enabled

## Setup

1. First install the OS of your choice.
2. Install PHP <https://www.php.net/downloads.php>
3. Update php.ini:
```
  file_uploads = On
  upload_max_filesize = 500M
  post_max_size = 500M
  max_execution_time = 300
  memory_limit = 512M
```
- On Ubuntu it's located at /etc/php/#.#/cli/php.ini
- On Windows you'll need to rename or copy C:\php\php.ini-production to C:\php\php.ini
- You can also adgust these to suit your needs but the defaults are to small for videos.
- You can check what files are loaded with php --ini

## OS Tweaks
- Have the system auto login. 
- Set the power plan to never turn off the display. 

### Windows 
[AutoLogon](https://learn.microsoft.com/en-us/sysinternals/downloads/autologon)  
  
*Never turn off display*  
1. Open Settings: Press the Windows key + I on your keyboard.
2. Navigate to Power & sleep: Click on System in the left-hand menu, then select Power & sleep.
3. Adjust screen timeout: Under the "Screen" section, find the options for "On battery power" and "When plugged in".
4. Set to "Never": Click on the dropdown menus for these settings and select Never.

### Ubuntu 
*Simple way*  
In Settings -> System -> Users you can set a user to auto login.  
In Settings -> Power set Power Mode to Performance and Power Saving -> Screen Blank to Never  

*Advanced Way*  
You can enable automatic login to the GUI desktop in Ubuntu by editing the GDM configuration file.  
Run:  
```sudo nano /etc/gdm3/custom.conf```  
And uncomment (or add) the line: 
```
[daemon]
AutomaticLoginEnable = true
AutomaticLogin = DevTeam
```
Then save and reboot the system. This will automatically log the DevTeam user in after the reboot.

## Installation

### Clone the Repository
```
git clone https://github.com/Gryphon4200/Media-Playback-Server.git
cd Media-Playback-Server
```


