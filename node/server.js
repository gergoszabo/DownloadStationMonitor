const fastify = require('fastify')({
    logger: true
});
const syno = require('./syno');
const static = require('./static');
const path = require('path');
const fastifyStatic = require('fastify-static');

fastify.register(fastifyStatic, {
    root: path.join(__dirname, 'node_modules', 'lit-html'),
    decorateReply: false,
    prefix: '/node_modules/lit-html/'
});

static(fastify);
syno(fastify);

fastify.listen(process.env.NODE_PORT || 3000, '0.0.0.0', function (err, address) {
    if (err) {
        fastify.log.error(err);
        process.exit(1);
    };
    fastify.log.info(`server listening on ${address}`);
});