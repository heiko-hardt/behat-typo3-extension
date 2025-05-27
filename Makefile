include .docker/.env
CMD_DOCKER_COMPOSE = docker compose -p ${COMPOSE_PROJECT_NAME} -f .docker/compose.yaml --env-file .docker/.env
version ?= 13

.PHONY: help url up build qa down clean term prep url

help:
	@echo "# Target informations ###############################################################"
	@echo
	@$(MAKE) -s url
	@echo
	@echo "$$ make up                   | start docker compose environment"
	@echo "$$ make build                | update composer dependencies"
	@echo "$$ make qa                   | run quality assurance"
	@echo "$$ make down                 | remove generated content"
	@echo "$$ make clean                | remove generated content"
	@echo
	@echo "$$ make term                 | start terminal in web container"
	@echo "$$ make prep version=[13]    | prepare environment for TYPO3 version [x]"
	@echo "$$ make qa:bdd               | quality assurance, complete"
	@echo "$$ make qa:bdd:suite.minimum | quality assurance, suite: minimum"
	@echo "$$ make qa:bdd:suite.website | quality assurance, suite: website"
	@echo

url:
	@echo "Start browsing web: http://localhost:8801"
	@echo "          selenium: http://localhost:7901/?autoconnect=1&resize=scale&password=secret"

up:
	@${CMD_DOCKER_COMPOSE} up -d
	@$(MAKE) -s url

build:
	@${CMD_DOCKER_COMPOSE} exec -u developer web /usr/local/bin/php /usr/local/bin/composer update --no-interaction --optimize-autoloader

qa:
	@${CMD_DOCKER_COMPOSE} exec -u developer web /usr/local/bin/php /usr/local/bin/composer run qa

down:
	@${CMD_DOCKER_COMPOSE} down -v

term:
	@${CMD_DOCKER_COMPOSE} exec -u developer web /bin/bash

qa\:bdd:
	@echo "Running quality assurance ..."
	@mkdir -p public
	@${CMD_DOCKER_COMPOSE} exec -u developer web /bin/bash -c ".run/bin/behat -c tests/Acceptance/behat.yaml --format pretty"

qa\:bdd\:suite.minimum:
	@echo "Running suite: Frontend.Minimum ..."
	@mkdir -p public
	@${CMD_DOCKER_COMPOSE} exec -u developer web /bin/bash -c ".run/bin/behat -c tests/Acceptance/behat.yaml --suite Frontend.Minimum --format pretty"

qa\:bdd\:suite.website:
	@echo "Running suite: Frontend.Website ..."
	@mkdir -p public
	@${CMD_DOCKER_COMPOSE} exec -u developer web /bin/bash -c ".run/bin/behat -c tests/Acceptance/behat.yaml --suite Frontend.Website --format pretty"

clean:
	@rm -rf .reports .run/bin .run/public .run/vendor public composer.lock

prep:
	@echo "Preparing TYPO3 v.${version} environment ..."
	@$(MAKE) -s clean
	@cp .resources/TYPO3.v.${version}/compose.yaml .docker/ 
	@cp .resources/TYPO3.v.${version}/composer.json ./ 
	@cp .resources/TYPO3.v.${version}/suite.minimum.yaml tests/Acceptance/Features/Frontend.Minimum/suite.yaml
	@cp .resources/TYPO3.v.${version}/suite.website.yaml tests/Acceptance/Features/Frontend.Website/suite.yaml
	@mkdir public
	@echo "... done. Please rebuild the container."
