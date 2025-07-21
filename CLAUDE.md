# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Architecture Overview

This is a Laravel 12 application using Livewire and Volt for frontend interactivity. The project is built on the Laravel Livewire starter kit structure with the following key components:

- **Framework**: Laravel 12 with Livewire/Flux UI components
- **Frontend**: TailwindCSS 4 with Vite build system  
- **Authentication**: Built-in Laravel auth with Livewire components
- **Database**: SQLite for development
- **Testing**: Pest PHP testing framework
- **External SDK**: `chengkangzai/laravel-waha-saloon-sdk` for WhatsApp API integration

## Key Dependencies

- `chengkangzai/laravel-waha-saloon-sdk`: WhatsApp API integration via WAHA (WhatsApp HTTP API)
- `livewire/flux`: UI component library
- `livewire/volt`: Single-file Livewire components

## Development Commands

### Running the application
```bash
composer dev    # Starts server, queue, logs, and vite concurrently
php artisan serve    # Development server only
```

### Building assets
```bash
npm run build    # Production build
npm run dev      # Development build with watching
```

### Testing
```bash
composer test    # Runs config:clear and artisan test
php artisan test # Direct test execution
vendor/bin/pest  # Direct Pest execution
```

### Code quality
```bash
./vendor/bin/pint    # Laravel Pint code formatting
```

## Project Structure Notes

- **Livewire Components**: Located in `app/Livewire/` with corresponding views in `resources/views/livewire/`
- **Volt Components**: Single-file components using Livewire Volt syntax
- **Authentication**: Full auth system with email verification, password reset, and profile management
- **Database**: Uses SQLite with migrations in `database/migrations/`
- **Configuration**: Standard Laravel config files in `config/`

## Testing Structure

- **Feature Tests**: `tests/Feature/` - includes auth flow and dashboard tests
- **Unit Tests**: `tests/Unit/` - for isolated component testing
- **Pest Configuration**: Uses Pest PHP with Laravel plugin
- **Test Database**: In-memory SQLite for testing

## Environment Setup

The application expects standard Laravel environment variables. Key areas for AQI notifier functionality will require:
- WAQI API token configuration
- WhatsApp API credentials for WAHA SDK
- Queue configuration for background processing