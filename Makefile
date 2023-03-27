.PHONY: coverage cc fix-cc cs deps qa install lint phpstan rector test

build:
	$(COMPOSER) install --no-dev

all: qa phpstan cc test

app/cert:
	mkdir app/cert

app/cert/privkey.pem:
	openssl ecparam -name secp384r1 -genkey -param_enc named_curve -out app/cert/privkey.pem

app/cert/cert.pem:
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
	vendor/bin/codesniffer --runtime-set php_version 80100 app bin tests

deps:
	composer install

qa: cs

phpstan: deps
	NETTE_TESTER_RUNNER=1 vendor/bin/phpstan analyse -c phpstan.neon

run:
	php -S [::]:8090 -t www/

temp/code-checker:
	composer create-project nette/code-checker temp/code-checker --no-interaction

test: deps
	vendor/bin/tester -p phpdbg -c ./tests/php.ini ./tests
