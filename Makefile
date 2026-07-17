.PHONY: setup test lint phpcs coverage

setup:
	mkdir -p _libraries &&\
	git clone --depth 1 -b snapshot/nightly https://github.com/Icinga/icinga-php-library.git _libraries/ipl &&\
	git clone --depth 1 -b snapshot/nightly https://github.com/Icinga/icinga-php-thirdparty.git _libraries/vendor &&\
	git clone --depth 1 https://github.com/Icinga/icingaweb2.git _icingaweb2 &&\
	git clone --depth 1 https://github.com/Icinga/icingadb-web.git _icingaweb2/modules/icingadb
test:
	ICINGAWEB_LIBDIR=_libraries ./vendor/bin/phpunit
coverage:
	ICINGAWEB_LIBDIR=_libraries ./vendor/bin/phpunit --coverage-html reports/
lint:
	phplint application/ library/
phpcs:
	phpcs
