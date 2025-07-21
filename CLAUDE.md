# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the `wink/controller-generator` package - a Laravel package that generates production-ready controllers from database schemas and existing models. The focus is on enterprise legacy database modernization with consistent, maintainable code patterns.

## Architecture

The project is designed around a Laravel package structure that generates three types of controllers:

- **API Controllers**: RESTful JSON API endpoints with Laravel API Resource integration
- **Web Controllers**: Traditional web applications with view rendering and Blade templates  
- **Resource Controllers**: Hybrid controllers supporting both API and web with content negotiation

### Core Components

- **Generators**: `AbstractControllerGenerator`, `ApiControllerGenerator`, `WebControllerGenerator`, `ResourceControllerGenerator`
- **Analyzers**: `ModelAnalyzer`, `RouteAnalyzer`, `ValidationAnalyzer` for introspecting existing code
- **Templates**: Stub files for different controller types and FormRequest classes
- **Commands**: Artisan commands for generating controllers (`wink:generate-controllers`, `wink:controllers:api`, etc.)

The package integrates with `wink/model-generator` outputs and follows Laravel conventions for routing, middleware, validation, and authorization.

## Key Features

- Automatic FormRequest class generation with database constraint-based validation
- Laravel API Resource integration for response transformation
- Support for filtering, search, and pagination
- Security features including CSRF protection, authorization policies, and input sanitization
- OpenAPI 3.0 documentation generation
- Integration with existing Laravel features (policies, middleware, etc.)

## Development Notes

This is currently a planning/specification phase project - the PRD document outlines the complete feature set and technical specifications for implementation. The package will support Laravel 10+ and follow PSR-12 coding standards.

The project aims for 80% reduction in controller scaffolding time while maintaining 100% PSR-12 compliance and zero security vulnerabilities in generated code.