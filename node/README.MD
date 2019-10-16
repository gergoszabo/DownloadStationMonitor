# Download Station Monitor - node variant

### Requirements
* Node 8+
* Synology box on LAN (DDNS might work)

### How to run it locally
* install dependencies by executing `npm install`
* set up environment variables properly
* execute `npm run start`

### Setting up env variables
* SYNO_PROTOCOL: protocol used for connecting synology box (`http` (default) or `https`)
* SYNO_IP: ip address or dns name of synology box (for example `127.0.0.1` (default))
* SYNO_PORT: port of synology box to connect (default is `5000`)
* SYNO_USERNAME: username for the synology box (for example `dsuser`)
* SYNO_PASSWORD: passworf for the synology box
* SYNO_TWOFACTOR: `true` if two factor authentication is enabled, otherwise `false` (default `false`)
* NODE_PORT: port number that application listens on (default `3000`)