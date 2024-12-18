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

.PHONY: coverage cc fix-cc cs deps qa install lint phpstan rector test

build:
	$(COMPOSER) install --no-dev

all: qa phpstan cc test

app/cert:
	mkdir app/cert

app/cert/privkey.pem: app/cert
	openssl ecparam -name secp384r1 -genkey -param_enc named_curve -out app/cert/privkey.pem

app/cert/cert.pem: app/cert/privkey.pem
	openssl req -new -x509 -sha256 -nodes -days 3650 \
      -subj "/CN=SBC PDU management/C=CZ/ST=South Moravian Region/L=Boskovice/O=Roman Ondráček" \
      -key app/cert/privkey.pem -out app/cert/cert.pem

cert: app/cert app/cert/privkey.pem app/cert/cert.pem

clean:
	rm -rf log/*.html log/*.log temp/cache/ temp/proxies/ tests/tmp/

coverage: deps
	vendor/bin/tester -p phpdbg -c ./tests/php.ini --coverage ./coverage.html --coverage-src ./app ./tests

cc: temp/code-checker
	php temp/code-checker/code-checker -l --no-progress --strict-types -i "coverage.*" -i "docs/" -i "tests/temp/"

fix-cc: temp/code-checker
	php temp/code-checker/code-checker -f -l --no-progress --strict-types -i "coverage.*" -i "docs/" -i "tests/temp/"

cs: deps
	vendor/bin/codesniffer --runtime-set php_version 80300 app bin db tests

deps:
	composer install

qa: cs

phpstan: deps
	NETTE_TESTER_RUNNER=1 vendor/bin/phpstan analyse -c phpstan.neon

rector: deps
	NETTE_TESTER_RUNNER=1 vendor/bin/rector process --dry-run

run:
	php -S [::]:8090 -t www/

temp/code-checker:
	composer create-project nette/code-checker temp/code-checker --no-interaction

test: deps
	vendor/bin/tester -p phpdbg -c ./tests/php.ini ./tests
