FROM node:12-alpine

ADD ["server.js", "syno.js", "static.js", "package.json", "./"]

ADD ["./frontend", "/frontend"];

RUN npm install

ENV SYNO_USERNAME=dsuser SYNO_PASSWORD=password123 SYNO_PROTOCOL=http SYNO_IP=127.0.0.1 SYNO_PORT=5000 SYNO_TWOFACTOR=false NODE_PORT=3000

CMD ["npm", "run", "start"]