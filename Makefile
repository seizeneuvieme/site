.DEFAULT_GOAL: help
.PHONY: install code-analysis test unit-test functional-test php-cs-fixer-dry-run php-cs-fixer php-stan vendor

-include .env

# See https://www.thapaliya.com/en/writings/well-documented-makefiles/
help: ## Display this help
	@awk 'BEGIN {FS = ":.* ##"; printf "\n\033[1mUsage:\033[0m\n  make \033[32m<target>\033[0m\n"} /^[a-zA-Z_-]+:.* ## / { printf "  \033[33m%-25s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

##@ Installation
install: vendor ## Install all necessary things

vendor: composer.lock ## Run composer install
	composer install --no-scripts

##@ Continuous integration
code-analysis: php-stan php-cs-fixer-dry-run ## Execute code analysis (PHPStan / PHP CS Fixer)

php-cs-fixer-dry-run: ## Execute PHP CS Fixer DRY RUN
	php vendor/bin/php-cs-fixer fix --dry-run --diff --config=.php_cs.php -vvv

php-cs-fixer: ## Fix files with PHP CS Fixer
	php vendor/bin/php-cs-fixer fix -vvv --config=.php_cs.php

php-stan: ## Execute PHPStan analysis
	php vendor/bin/phpstan analyse --memory-limit=-1

##@ Tests
test: unit-test functional-test ## Run all tests

unit-test: ## Run unit tests R="something"
	php vendor/bin/phpunit --verbose --stop-on-failure --testsuite unit

functional-test: ## Run functional tests R="something"
	php vendor/bin/phpunit --verbose --stop-on-failure --testsuite functional