-include .docker/.env
# Get System User
export CMD_DOCKER_USER_NAME := $(shell id -un)
export CMD_DOCKER_USER_ID := $(shell id -u)
export CMD_DOCKER_GROUP_NAME := $(shell id -gn)
export CMD_DOCKER_GROUP_ID := $(shell id -g)
# Prepare docker composer command
CMD_DOCKER_COMPOSE = docker compose -p ${COMPOSE_PROJECT_NAME} -f .docker/compose.yaml --env-file .docker/.env
# Initialize version variable
version ?= 13

.PHONY: help url up build qa down clean term prep

help:
	@echo "# Target informations ###############################################################"
	@echo
	@$(MAKE) -s url
	@echo
	@echo "$$ make url                  | show published urls"
	@echo
	@echo "$$ make up                   | start docker compose environment"
	@echo "$$ make build                | update composer dependencies"
	@echo "$$ make qa                   | run quality assurance"
	@echo "$$ make down                 | remove generated content"
	@echo "$$ make clean                | remove generated content"
	@echo
	@echo "$$ make term                 | start terminal in web container"
	@echo "$$ make prep version=[13]    | prepare environment for TYPO3 version [x]"
	@echo

url:
	@echo "Start browsing web: http://localhost:8801"
	@echo "          selenium: http://localhost:7901/?autoconnect=1&resize=scale&password=secret"

up:
	@${CMD_DOCKER_COMPOSE} up --detach --quiet-pull
	@$(MAKE) -s url

build:
	@${CMD_DOCKER_COMPOSE} exec -u ${CMD_DOCKER_USER_NAME} web /usr/local/bin/php /usr/local/bin/composer update --no-interaction --optimize-autoloader

qa:
	@${CMD_DOCKER_COMPOSE} exec -u ${CMD_DOCKER_USER_NAME} web /usr/local/bin/php /usr/local/bin/composer run qa

down:
	@${CMD_DOCKER_COMPOSE} down -v

clean:
	@rm -rf .reports .run/bin .run/public .run/vendor public
	@rm -rf tests/Acceptance/behat.yaml tests/Acceptance/Features/Frontend.Minimum/suite.yaml tests/Acceptance/Features/Frontend.Website/suite.yaml
	@rm -rf composer.json composer.lock

term:
	@${CMD_DOCKER_COMPOSE} exec -u ${CMD_DOCKER_USER_NAME} web /bin/bash

prep:
	@echo "Preparing TYPO3 v.${version} environment ..."
	@$(MAKE) -s down # shutdown current environment
	@$(MAKE) -s clean # cleanup filesystem
	@cp .resources/TYPO3.v.${version}/.docker/compose.yaml .docker/
	@cp .resources/TYPO3.v.${version}/.docker/.env .docker/
	@cp .resources/TYPO3.v.${version}/composer.json ./
	@cp .resources/TYPO3.v.${version}/acceptance/behat.yaml tests/Acceptance/
	@cp .resources/TYPO3.v.${version}/acceptance/suite.minimum.yaml tests/Acceptance/Features/Frontend.Minimum/suite.yaml
	@cp .resources/TYPO3.v.${version}/acceptance/suite.website.yaml tests/Acceptance/Features/Frontend.Website/suite.yaml
	@mkdir public
	@echo "... done. Environment may now boot in TYPO3 v.${version} environment"
