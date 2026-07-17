# Development

## PHPUnit

Install phpunit with composer and see the Makefile for dependency setup.

```bash
# Downloads the Icinga Web dependencies
make setup

# Composer in a container, but can also be done without
podman run -ti --rm -v $(pwd):/app --entrypoint bash docker.io/composer:latest

# Install phpbench development dependency
composer install

# Run tests
make test

# Generate coverage report
make coverage
```

## PHP Lint

```bash
# Composer in a container, but can also be done without
podman run -ti --rm -v $(pwd):/app --entrypoint bash docker.io/composer:latest

# Install phpbench development dependency
composer install

# Run linter
make lint
```
