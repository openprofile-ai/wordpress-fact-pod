# quick access to most common commands

up:
	docker compose up -d

up-build:
	docker compose up --build -d

stop:
	docker compose stop

down:
	docker compose down -v

clean-database:
	docker compose stop
	docker compose down
	docker volume rm openprofile_database
	docker compose up