const fs = require('fs');
const pkgJson = require('./package.json');

module.exports = function (fastify) {
    fastify.get('/', function (_, reply) {
        fs.readFile('./frontend/index.html', { encoding: 'utf8' }, (err, data) => {
            if (err) {
                reply.status(500).send(err);
                return;
            }

            reply.type('text/html').send(data);
        });
    });
    fastify.get('/style.css', function (_, reply) {
        fs.readFile('./frontend/style.css', { encoding: 'utf8' }, (err, data) => {
            if (err) {
                reply.status(500).send(err);
                return;
            }

            reply.type('text/css').send(data);
        });
    });
    fastify.get('/index.js', function (_, reply) {
        fs.readFile('./frontend/index.js', { encoding: 'utf8' }, (err, data) => {
            if (err) {
                reply.status(500).send(err);
                return;
            }

            reply.type('text/javascript').send(`
            const appVersion = '${pkgJson.version}';
            const uiReload = ${(process.env.SYNO_RELOAD || 30) * 1000};
            ${data}`);
        });
    });

    [
        'down.png',
        'error.png',
        'finished.png',
        'paused.png',
        'up.png',
        'wait.png'
    ].forEach(filename => {
        fastify.get(`/${filename}`, function (_, reply) {
            fs.readFile(`./frontend/${filename}`, (err, data) => {
                if (err) {
                    reply.status(500).send(err);
                    return;
                }

                reply.type('image/png').send(data);
            });
        });
    });
};