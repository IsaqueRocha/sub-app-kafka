FROM nginx:1.19-alpine

RUN apk add --no-cache bash
RUN rm /etc/nginx/conf.d/default.conf

COPY ./nginx.conf /etc/nginx/conf.d/