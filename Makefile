.PHONY: up down build logs api mobile lint fix test sync

COMPOSE = docker compose

up:
	$(COMPOSE) up -d --build

down:
	$(COMPOSE) down

build:
	$(COMPOSE) build --no-cache

logs:
	$(COMPOSE) logs -f

api:
	$(COMPOSE) exec api sh

mobile:
	$(COMPOSE) exec mobile sh

lint:
	$(COMPOSE) exec mobile npm run lint

fix:
	$(COMPOSE) exec mobile npm run fix

test:
	$(COMPOSE) exec mobile npm run test
	$(COMPOSE) exec api php bin/phpunit

sync:
	$(COMPOSE) exec mobile npx cap sync