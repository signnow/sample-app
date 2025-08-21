.PHONY: build up setup down clear setup-env

build:
	@docker compose build --no-cache --force-rm

up:
	@docker compose up -d

setup:
	@docker exec -it signnow-sample-app composer install --ignore-platform-reqs --no-dev
	@test -f .env || cp .env.src .env
	@docker exec -it signnow-sample-app php artisan key:generate
	@make setup-env

down:
	@docker-compose down -v

clear:
	@docker exec -it signnow-sample-app php artisan cache:clear
	@docker exec -it signnow-sample-app php artisan config:clear
	@docker exec -it signnow-sample-app php artisan route:clear
	@docker exec -it signnow-sample-app php artisan view:clear
	@docker exec -it signnow-sample-app php artisan optimize:clear

setup-env:
	@echo "\nPlease configure your sample application with required information:"; \
	read -p "signNow API host: " sn_api_host; \
	read -p "signNow application's basic token: " sn_basic_token; \
	read -p "Your signer's email: " sn_signer_emal; \
	read -p "Your login to signNow account: " sn_user; \
	read -p "Your password to signNow account: " sn_password; \
	echo "\n" >> .env; \
	echo "SIGNNOW_API_HOST=$$sn_api_host" >> .env; \
	echo "SIGNNOW_API_BASIC_TOKEN=$$sn_basic_token" >> .env; \
	echo "SIGNNOW_API_USERNAME=$$sn_user" >> .env; \
	echo "SIGNNOW_API_PASSWORD=$$sn_password" >> .env; \
	echo "SIGNNOW_SIGNER_EMAIL=$$sn_signer_emal" >> .env; \
	echo "\n" >> .env; \
	echo "Setup completed."
