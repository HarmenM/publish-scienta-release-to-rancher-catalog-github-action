FROM php:7.4-cli-alpine

ENV GITHUB_DEPLOY_KEY=""
ENV GITHUB_USER=""
ENV GITHUB_EMAIL=""
ENV DEST_REPOSITORY="git@github.com:ScientaNL/rancher.git"
ENV DEST_BRANCH="develop"
ENV SOURCE_RELPATH="helm"
ENV SCIENTA_VERSION=""
ENV CHART_VERSION=""
ENV CHART_SUFFIX=""
ENV COMMIT_SHA=""

RUN apk add --no-cache git openssh-client && \
  echo "StrictHostKeyChecking no" >> /etc/ssh/ssh_config

RUN apk add composer

# copy app into container
RUN mkdir /app
RUN mkdir /ouput

COPY docker-entrypoint.sh composer.json /app/
COPY bin /app/bin
COPY src /app/src

RUN chmod 700 /app/docker-entrypoint.sh

RUN composer install -d /app

ENTRYPOINT ["/app/docker-entrypoint.sh"]
