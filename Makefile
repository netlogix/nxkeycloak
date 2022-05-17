.PHONY: clean deps test

clean:
	rm -rf .Build/

deps:
	composer install

update:
	composer update -W

test:
	XDEBUG_MODE=coverage .Build/bin/phpunit -c phpunit.xml
	XDEBUG_MODE=coverage .Build/bin/phpunit -c phpunit_functional.xml
	XDEBUG_MODE=coverage .Build/bin/phpunit-merger coverage .Build/logs/coverage/ --html=.Build/logs/html/ .Build/logs/coverage.php
	# this is currently buggy and will merge into empty results
	.Build/bin/phpunit-merger log .Build/logs/junit/ .Build/logs/junit.xml