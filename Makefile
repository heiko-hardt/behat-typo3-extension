include .docker/.env

XDEBUG = 1 #  1 = enable, 0 = disable
version ?= 13

help:
	@echo "# Target informations ###############################################################"
	@echo
	@$(MAKE) -s url
	@echo
	@echo "$$ make clean                | remove generated content"
	@echo "$$ make prep version=[13]    | prepare environment for TYPO3 version [x]"
	@echo "$$ make qa:bdd               | quality assurance, complete"
	@echo "$$ make qa:bdd:suite.minimum | quality assurance, suite: minimum"
	@echo "$$ make qa:bdd:suite.website | quality assurance, suite: website"
	@echo

url:
	@echo "Start browsing web: http://localhost:8801"
	@echo "          selenium: http://localhost:7901/?autoconnect=1&resize=scale&password=secret"

qa\:bdd:
	@echo "Running quality assurance ..."
	@mkdir -p public
	@XDEBUG_SESSION=$XDEBUG php .run/bin/behat -c tests/Acceptance/behat.yaml --format pretty

qa\:bdd\:suite.minimum:
	@echo "Running suite: Frontend.Minimum ..."
	@mkdir -p public
	@XDEBUG_SESSION=$XDEBUG php .run/bin/behat -c tests/Acceptance/behat.yaml --suite Frontend.Minimum --format pretty

qa\:bdd\:suite.website:
	@echo "Running suite: Frontend.Website ..."
	@mkdir -p public
	@XDEBUG_SESSION=$XDEBUG .run/bin/behat -c tests/Acceptance/behat.yaml --suite Frontend.Website --format pretty

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
