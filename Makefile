CONTAINER_FPM = php_fpm
CONTAINER_COMPOSER = php_composer
CONTAINER_KAFKA = kafka
CONTAINER_NGINX = nginx


docker-install:
	docker compose pull
	docker compose --env-file ./.env.docker --env-file ./.env up -d
	@echo "PasswordBroker installed and started"

docker-destroy:
	docker compose --env-file ./.env.docker --env-file ./.env down
	@echo "PasswordBroker destroyed"

docker-stop:
	docker compose --env-file ./.env.docker --env-file ./.env stop
	@echo "PasswordBroker stopped"

docker-start:
	docker compose --env-file ./.env.docker --env-file ./.env start
	@echo "PasswordBroker stopped"

podman-install: create-single-env
	podman-compose -f ./compose.yml --env-file ./.env.podman up -d
	@echo "PasswordBroker installed and started"

podman-destroy:
	podman-compose -f ./compose.yml --env-file ./.env.podman down
	@echo "PasswordBroker destroyed"

podman-stop:
	podman-compose -f ./compose.yml --env-file ./.env.podman stop
	@echo "PasswordBroker stopped"

podman-start: create-single-env
	podman-compose -f ./compose.yml --env-file ./.env.podman start
	@echo "PasswordBroker started"

create-single-env:
	 @cat ./.env <(echo -e "\n##DOCKER##\n") ./.env.docker > .env.podman