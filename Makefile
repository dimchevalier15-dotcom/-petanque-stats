.PHONY: up down build logs api mobile lint fix test sync up-prod setup-jwt deploy

COMPOSE = docker compose

up:
	$(COMPOSE) up -d --build

up-prod:
	$(COMPOSE) -f docker-compose.prod.yml up -d --build

setup-jwt:
	./scripts/setup-jwt.sh

deploy:
	./scripts/deploy.sh

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