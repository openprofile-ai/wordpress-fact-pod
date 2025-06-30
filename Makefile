build:
	docker build -t php:8.3-cli-composer docker

bash:
	docker run -it --rm -v $(PWD):/app -w /app php:8.3-cli-composer bash
