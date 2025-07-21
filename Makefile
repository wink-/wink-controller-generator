.PHONY: help install test test-coverage format format-check analyse analyse-psalm refactor refactor-dry mutation security unused check-deps quality quality-fix ci clean

# Default target
help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-20s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Install dependencies
	composer install

update: ## Update dependencies
	composer update

# Testing
test: ## Run all tests
	composer test

test-unit: ## Run unit tests only
	composer test:unit

test-feature: ## Run feature tests only
	composer test:feature

test-coverage: ## Run tests with coverage report
	composer test:coverage

# Code Quality
format: ## Format code using Laravel Pint
	composer format

format-check: ## Check code formatting
	composer format:test

analyse: ## Run static analysis with PHPStan
	composer analyse

analyse-psalm: ## Run static analysis with Psalm
	composer analyse:psalm

analyse-all: ## Run all static analysis tools
	composer analyse:all

refactor: ## Apply automated refactoring with Rector
	composer refactor

refactor-dry: ## Show what Rector would change (dry run)
	composer refactor:dry

mutation: ## Run mutation testing
	composer mutation

mutation-ci: ## Run mutation testing for CI
	composer mutation:ci

# Security & Dependencies
security: ## Run security audit
	composer security

unused: ## Check for unused dependencies
	composer unused

check-deps: ## Check dependency requirements
	composer check-deps

# Combined targets
quality: ## Run all quality checks
	composer quality

quality-fix: ## Fix code quality issues
	composer quality-fix

ci: ## Run full CI pipeline
	composer ci

# Utility
clean: ## Clean build artifacts
	rm -rf build/
	rm -rf vendor/
	rm -f composer.lock

fresh: clean install ## Clean install

# Development
dev-setup: install ## Set up development environment
	mkdir -p build/{phpstan,rector,infection,coverage}
	@echo "Development environment ready!"

dev-check: quality ## Quick development quality check
	@echo "✅ Development checks passed!"

# Build
build-prepare: ## Prepare build directories
	mkdir -p build/{phpstan,rector,infection,coverage}

# Documentation
docs-generate: ## Generate documentation (placeholder)
	@echo "Documentation generation not yet implemented"

# Release preparation
pre-commit: quality-fix ## Run before committing
	@echo "✅ Ready to commit!"

pre-release: ci ## Run before releasing
	@echo "✅ Ready for release!"