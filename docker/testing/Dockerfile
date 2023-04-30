# Copyright 2022-2023 Roman Ondráček
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    https://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

FROM debian:bullseye

ENV DEBIAN_FRONTEND noninteractive

RUN echo "deb http://deb.debian.org/debian/ bullseye main contrib non-free\n\
deb http://deb.debian.org/debian/ bullseye-updates main contrib non-free\n\
deb http://deb.debian.org/debian-security bullseye-security main\n\
deb http://ftp.debian.org/debian bullseye-backports main" > /etc/apt/sources.list

RUN apt-get update \
 && apt-get install --no-install-recommends -yqq ca-certificates curl default-mysql-client git gnupg2 rsync wget zip unzip \
 && curl -sSLo /usr/share/keyrings/deb.sury.org-php.gpg https://packages.sury.org/php/apt.gpg \
 && sh -c 'echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ bullseye main" > /etc/apt/sources.list.d/php.list' \
 && curl -sL https://deb.nodesource.com/setup_lts.x | bash - \
 && apt-get update \
 && apt-get install --no-install-recommends -yqq composer nodejs \
    php8.1 php8.1-common php8.1-cgi php8.1-cli php8.1-curl php8.1-pcov php8.1-phpdbg php8.1-mbstring php8.1-mysql php8.1-xml php8.1-zip \
	php8.2 php8.2-common php8.2-cgi php8.2-cli php8.2-curl php8.2-pcov php8.2-phpdbg php8.2-mbstring php8.2-mysql php8.2-xml php8.2-zip \
    php php-common php-cgi php-cli php-curl php-json php-phpdbg php-pcov php-mbstring php-mysql php-xml php-zip \
 && npm install -g @sentry/cli --unsafe-perm \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

RUN echo "deb https://deb.debian.org/debian/ bullseye main contrib non-free\n\
deb https://deb.debian.org/debian/ bullseye-updates main contrib non-free\n\
deb https://deb.debian.org/debian-security bullseye-security main\n\
deb https://ftp.debian.org/debian bullseye-backports main" > /etc/apt/sources.list
