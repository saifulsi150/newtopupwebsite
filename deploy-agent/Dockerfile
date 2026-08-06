FROM node:20-alpine

WORKDIR /app

RUN apk add --no-cache git docker-cli

COPY package*.json ./
RUN npm install --omit=dev

COPY server.js ./

ENV PORT=8099
EXPOSE 8099

CMD ["node", "server.js"]
