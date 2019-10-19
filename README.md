# DownloadStationMonitor

For old php code, check out [README.MD](https://github.com/gary89/DownloadStationMonitor/tree/master/php).

## Running in Docker
Package is published as `gary89/download-station-monitor` to [hub.docker.com](http://hub.docker.com).

Set up the environment variables, the external port and start the container.

## Running without Docker

#### Requirements
* Node.js v8 (or higher)
* Synology DSM running DownloadStation application

#### Running without docker on pc
Use `start.sample.bat` or `start.sample.sh` as a template and create the appropiate shell script.

#### Running without docker on Synology DSM
* Install Node.js v8
* copy files to DSM `eg. /volume1/node/DownloadStationMonitor`
* SSH into DSM into target folder
* run `npm install`
* execute `cp start.sample.sh start.sh` to create proper start script
* modify the start script
* test if its working with executing `sh start.sh`

#### Scheduling automatic start on Synology DSM startup
* Open `Control Panel`, then select `Task Scheduler`
* Click on `Create` button, then select `Triggered task`, `User-defined script`
* Give it a name like `Download station monitor`
* select the correct user
* event should be `Boot-up`
* Thick in `Enabled` checkbox
* Switch to `Taks settings` tab
* Paste the following code into `User-defined script` field (modify path to the correct one)
    * `cd /volume1/node/DownloadStationMonitor`
    * `sh start.sh`
* Click on `OK` button
