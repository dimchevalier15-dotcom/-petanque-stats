.PHONY: up down build logs api mobile lint fix test sync prod-build prod-up prod-down prod-logs prod-migrate

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

prod-build:
	$(COMPOSE) -f docker-compose.prod.yml build

prod-up:
	$(COMPOSE) -f docker-compose.prod.yml up -d --build

prod-down:
	$(COMPOSE) -f docker-compose.prod.yml down

prod-logs:
	$(COMPOSE) -f docker-compose.prod.yml logs -f

prod-migrate:
	$(COMPOSE) -f docker-compose.prod.yml exec api php bin/console doctrine:migrations:migrate --no-interaction