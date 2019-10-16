const http = require('http');

const get = (url) => new Promise((resolve, reject) => {
    console.log('GET', url);
    http.get(url, (res) => {
        try {
            res.setEncoding('utf8');
            let rawData = '';
            res.on('data', (chunk) => { rawData += chunk; });
            res.on('end', () => {
                resolve(rawData);
            });
            res.on('error', err => {
                reject(e);
            });
        } catch (e) {
            reject(e);
        }
    });
});

const login = (force) => new Promise(async (resolve, reject) => {
    if (session.sid && !force) {
        resolve();
        return;
    }

    const result = await get(`${session.synoUrl}/auth.cgi?api=SYNO.API.Auth&version=2&method=login&account=${session.user}&passwd=${session.pass}&session=DownloadStation&format=sid`);

    const response = JSON.parse(result);

    if (response.success) {
        session.sid = response.data.sid;
        resolve();
    } else {
        reject(response);
    }
});

const getTasks = () => new Promise(async (resolve) => {
    await login();

    const result = await get(`${session.synoUrl}/DownloadStation/task.cgi?api=SYNO.DownloadStation.Task&version=1&method=list&_sid=${session.sid}&additional=transfer,detail,tracker`);

    const tasks = JSON.parse(result);

    resolve(tasks.data.tasks);
});

const getConfig = () => new Promise(async (resolve) => {
    await login();

    const result = await get(`${session.synoUrl}/DownloadStation/info.cgi?api=SYNO.DownloadStation.Info&version=1&method=getconfig&_sid=${session.sid}`);

    const config = JSON.parse(result);

    resolve({
        upload: config.data.bt_max_upload,
        download: config.data.bt_max_download
    });
});

const setConfig = (upload, download) => new Promise(async (resolve, reject) => {
    await login();

    const result = await get(`${session.synoUrl}/DownloadStation/info.cgi?api=SYNO.DownloadStation.Info&version=1&` +
        `method=setserverconfig&bt_max_download=${download}&bt_max_upload=${upload}&_sid=${session.sid}`);

    const response = JSON.parse(result);

    if (response.error) {
        reject(response);
    } else {
        resolve(response);
    }
});

const synoUrl = `${(process.env.SYNO_PROTOCOL || 'http')}://${(process.env.SYNO_IP || '127.0.0.1')}:${(process.env.SYNO_PORT || 5000)}/webapi`;

const session = {
    sid: null,
    user: process.env.SYNO_USERNAME,
    pass: process.env.SYNO_PASSWORD,
    synoUrl,
    twofactor: process.env.SYNO_TWOFACTOR
};

module.exports = function (fastify) {
    fastify.get('/tasks', async (_, reply) => {
        try {
            const tasks = await getTasks();
            reply.send(tasks);
        } catch (e) {
            reply.status(500).send(e);
        }
    });

    fastify.get('/config', async (_, reply) => {
        try {
            const config = await getConfig();
            reply.send(config);
        } catch (e) {
            reply.status(500).send(e);
        }
    });

    fastify.post('/config', async (request, reply) => {
        try {
            const config = JSON.parse(request.body);
            const resp = await setConfig(config.upload, config.download);
            reply.send(resp);
        } catch (e) {
            reply.status(500).send(e);
        }
    });
};
