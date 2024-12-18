# Backend pro centrální správu napájecích jednotek

[![Pipeline status](https://gitlab.com/sbc-pdu/central-management/backend/badges/master/pipeline.svg)](https://gitlab.com/sbc-pdu/central-management/backend/-/commits/master)
[![Apache License](https://img.shields.io/badge/license-Apache2-blue.svg)](LICENSE)

Tento repozitář obsahuje backendovou část pro centrální správu napájecích jednotek.

## Prerekvizity

- PHP 8.3
- Composer
- MySQL/MariaDB databáze
- InfluxDB 2.x databáze
- MQTT broker (např. Mosquitto)
- SMTP server

## Instalace

1. Naklonujte si tento repozitář pomocí příkazu:
	```bash
	git clone https://gitlab.com/sbc-pdu/central-management/backend.git
	```
2. Nainstalujte závislosti pomocí příkazu:
	```bash
	composer install
	```
3. Vytvořte soubor `app/config/local.neon` s konfigurací:
	```neon
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
	parameters:
		database:
			host: localhost                                # Adresa MySQL/MariaDB serveru
			dbname: sbc_pdu                                # Název databáze
			user: sbc_pdu                                  # Uživatelské jméno pro připojení k MySQL/MariaDB serveru
			password: password                             # Heslo pro připojení k MySQL/MariaDB serveru
			serverVersion: 10.11.6-MariaDB-0+deb12u1			 # Verze MySQL/MariaDB serveru
		influxdb:
			url: http://localhost:8086                     # Adresa InfluxDB serveru
			token: ''                                      # Token pro připojení k InfluxDB
			bucket: 'sbc_pdu'                              # Název bucketu v InfluxDB
			org: 'sbc_pdu'                                 # Název organizace v InfluxDB
			precision: \InfluxDB2\Model\WritePrecision::MS # Přesnost zápisu data do InfluxDB
			timeout: 10                                    # Timeout pro připojení k InfluxDB
		mqtt:
			host: localhost                                # Adresa MQTT brokeru
			port: 1883                                     # Port MQTT brokeru
			clientId: 'sbc-pdu_management'                 # ID klienta pro připojení k MQTT brokeru
			useTls: false                                  # Použít TLS pro připojení k MQTT brokeru
			username: sbc_pdu                              # Uživatelské jméno pro připojení k MQTT brokeru
			password: password                             # Heslo pro připojení k MQTT brokeru
		sentry:
			dsn: ''                                        # DSN pro Sentry
		smtp:
			host: localhost                                # Adresa SMTP serveru
			port: 25                                       # Port pro SMTP server (typicky 25 (plaintext/STARTTLS), 465 (TLS) nebo 587 (STARTTLS))
			username: ''                                   # Uživatelské jméno pro SMTP server
			password: ''                                   # Heslo pro SMTP server
			secure: null                                   # null, 'ssl' (TLS) nebo 'tls' (STARTTLS)
			from: ''                                       # Adresa odesílatele e-mailů (např. sbc-pdu@example.com)
	```
4. Vygenerujte privátní klíč a certifikát pro podpis JWT tokenů:
	```bash
	openssl ecparam -name secp384r1 -genkey -param_enc named_curve -out app/cert/privkey.pem
	openssl req -new -x509 -sha256 -nodes -days 3650 \
      -subj "/CN=SBC PDU management/C=CZ/ST=South Moravian Region/L=Boskovice/O=Roman Ondráček" \
      -key app/cert/privkey.pem -out app/cert/cert.pem
    ```
5. Proveďte migraci databáze pomocí příkazu:
	```bash
	php bin/console migrations:migrate --no-interaction
	```
6. Vytvořte výchozího uživatele `admin@romanondracek.cz`:`admin` pomocí příkazu:
	```bash
	php bin/console fixtures:load --append --no-interaction
	```
7. Spusťte server pomocí příkazu:
	```bash
	php -S localhost:8090 -t www/
	```
8. Spusťte MQTT klienta pomocí příkazu:
	```bash
	php bin/console mqtt
	```
9. REST API je dostupné na adrese `http://localhost:8090/api/v1/`.
