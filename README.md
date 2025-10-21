# PHP Media Server

A lightweight, web-based media playback server designed for direct display applications. This server is built to power digital signage displays, video walls, information kiosks, and presentation systems by serving media content directly to connected monitors or display hardware.

## About

The PHP Media Server is specifically designed for scenarios where you need to display media content on physical screens - from simple desktop monitors to large-scale video walls. Unlike traditional web applications, this server is intended to run on hardware that's directly connected to display devices.

### Typical Use Cases
- **Digital Signage**: Retail displays, restaurant menus, corporate announcements
- **Video Walls**: Multi-screen installations for events, control rooms, or public spaces  
- **Information Kiosks**: Interactive displays in lobbies, museums, or public facilities
- **Presentation Systems**: Conference rooms, classrooms, or auditorium displays
- **Broadcasting**: TV stations, streaming setups, or live event displays

### Hardware Architecture
The server runs on any computer (Windows, Linux, or macOS) that's connected to your display hardware:

## Features

- **Cross-Platform Compatibility**: Works seamlessly on Windows and Ubuntu/Linux
- **File Upload Management**: Upload media files through a web interface
- **Preset Configuration**: Configure and manage media presets
- **Real-Time File Monitoring**: Automatically refreshes when media folder contents change
- **File Management**: View, select, and delete media files
- **Responsive Web Interface**: Clean, user-friendly interface for media management

### Remote Management
While the media displays on the connected monitor, you can manage the server remotely from any device on the network:  
Upload new media files from your phone or computer  
Change preset configurations without touching the display hardware  
Monitor system status and file changes in real-time  
No need to physically access the display computer for content updates  

## Usage  

### Basic Operation
Access the Interface: Navigate to http://your-server/php-media-server/  
Upload Files: Use the upload form at the bottom of the file list  
Manage Presets: Click "Presets" to configure preset assignments  
Select Media: Click on any file name to select/play it  
Delete Files: Click the "X" button next to any file to delete it  
### File Upload  
Supports common media formats (video, audio, images)  
Maximum file size configurable via PHP settings  
Real-time upload progress and error handling  
Automatic page refresh after successful upload  
### Preset Management  
Configure up to any number of presets  
Assign media files to preset slots  
Update preset configurations through the web interface  
### API Endpoints  
Endpoint	Method	Description  
index.php	GET	Main interface  
upload.php	POST	Handle file uploads  
check_changes.php	GET	JSON API for file monitoring  

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

## Quick Start
 
### Built-in PHP Server (Recommended)
```  
git clone https://github.com/Gryphon4200/PHP-Media-Playback-Server.git  
cd PHP-Media-Playback-Server  
php -S 0.0.0.0:8080  
  
Access via: http://localhost:8080  
```  

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

*Start the server when windows loads*  
Copy StartServer.ps1 and StartDisplay.ps1 to c:\ProgramData\Microsoft\Windows\Start Menu\Programs\Startup\  
Edit these two scripts to fit your needs.  

### Ubuntu 
*Simple way*  
In Settings -> System -> Users you can set a user to auto login.  
In Settings -> Power set Power Mode to Performance and Power Saving -> Screen Blank to Never  

*Advanced Way*  
You can enable automatic login to the GUI desktop in Ubuntu by editing the GDM configuration file.  
Run:  
```
sudo nano /etc/gdm3/custom.conf
```  
And uncomment (or add) the line: 
```
[daemon]
AutomaticLoginEnable = true
AutomaticLogin = DevTeam
```
Then save and reboot the system. This will automatically log the DevTeam user in after the reboot.

*Setup the server to start when the system boots.*
```
sudo vi /etc/systemd/system/mediaserver.service

[Unit]
Description=Media Playback Server
After=network.target
Wants=network.target

[Service]
ExecStart=php -S 0.0.0.0:8080 -t '/Server/'
KillMode=process
Restart=on-failure

[Install]
WantedBy=default.target
RequiredBy=network.target
```

## Installation

### Clone the Repository
```
git clone https://github.com/Gryphon4200/Media-Playback-Server.git
cd Media-Playback-Server
```

## Configure the Application
Create or edit config.json:
```
{
    "path": "./",
    "debug": false,
    "1": "default_media1.jpg",
    "2": "default_media2.mp4",
    "3": "default_media3.mp3"
}
```
Path Options:

"./" - Relative path (recommended for portability)  
"/absolute/path/to/media/" - Absolute path  
"C:\\Server\\" - Windows absolute path  
