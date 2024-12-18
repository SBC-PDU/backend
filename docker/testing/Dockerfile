# Copyright 2022-2024 Roman Ondráček <mail@romanondracek.cz>
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

FROM debian:bookworm

ENV DEBIAN_FRONTEND noninteractive

RUN echo "deb http://deb.debian.org/debian/ bookworm main contrib non-free non-free-firmware\n\
deb http://deb.debian.org/debian/ bookworm-updates main contrib non-free non-free-firmware\n\
deb http://deb.debian.org/debian-security bookworm-security main\n\
deb http://deb.debian.org/debian/ bookworm-backports main" > /etc/apt/sources.list

RUN apt-get update \
 && apt-get install --no-install-recommends -yqq ca-certificates curl default-mysql-client git gnupg2 rsync wget zip unzip \
 && curl -sSLo /usr/share/keyrings/deb.sury.org-php.gpg https://packages.sury.org/php/apt.gpg \
 && sh -c 'echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ bookworm main" > /etc/apt/sources.list.d/php.list' \
 && curl -sL https://deb.nodesource.com/setup_lts.x | bash - \
 && apt-get update \
 && apt-get install --no-install-recommends -yqq composer nodejs \
    php8.3 php8.3-common php8.3-cgi php8.3-cli php8.3-curl php8.3-pcov php8.3-phpdbg php8.3-mbstring php8.3-mysql php8.3-xml php8.3-zip \
	php8.4 php8.4-common php8.4-cgi php8.4-cli php8.4-curl php8.4-pcov php8.4-phpdbg php8.4-mbstring php8.4-mysql php8.4-xml php8.4-zip \
    php php-common php-cgi php-cli php-curl php-json php-phpdbg php-pcov php-mbstring php-mysql php-xml php-zip \
 && npm install -g @sentry/cli --unsafe-perm \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

RUN echo "deb https://deb.debian.org/debian/ bookworm main contrib non-free non-free-firmware\n\
deb https://deb.debian.org/debian/ bookworm-updates main contrib non-free non-free-firmware\n\
deb https://deb.debian.org/debian-security bookworm-security main\n\
deb https://deb.debian.org/debian/ bookworm-backports main" > /etc/apt/sources.list
