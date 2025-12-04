CONTAINER_FPM = php_fpm
CONTAINER_COMPOSER = php_composer
CONTAINER_KAFKA = kafka
CONTAINER_NGINX = nginx


install:
	docker compose pull
	docker compose --env-file ./.env.docker --env-file ./.env up -d
	@echo "PasswordBroker installed and started"