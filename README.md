# GPSTools

A PHP-based toolkit for manipulating GPS data files (FIT, GPX, and TCX) with functionality to convert between formats.

## Overview

GPSTools provides a set of command-line utilities to work with common GPS file formats used by fitness devices and applications. It currently supports:

- Reading and parsing Garmin FIT files
- Converting FIT files to GPX format
- Working with GPS track data including position, time, cadence, and other metrics

The project is built on the Symfony framework and uses custom libraries for binary file parsing and console commands.

## Prerequisites

- PHP 7.2.5 or higher
- PHP Extensions:
  - ext-ctype
  - ext-iconv
- Composer (for dependency management)
- Java Runtime Environment (JRE) 8 or higher (for FitCSVTool.jar)
- Git (for cloning the repository and its dependencies)

## Setup Instructions

### 1. Clone the Repository

```bash
git clone https://github.com/ella7/gpstools.git
cd gpstools
```

### 2. Install Dependencies

The project uses Composer to manage PHP dependencies, including custom packages from GitHub:

```bash
composer install
```

Note: This will automatically clone the required dependencies from:
- https://github.com/ella7/console-command.git
- https://github.com/ella7/php-binary-reader.git

### 3. Environment Configuration

Copy the environment template:

```bash
cp .env .env.local
```

Adjust any settings in `.env.local` as needed. The primary settings are:
- `APP_ENV`: Set to `dev` for development or `prod` for production
- `APP_SECRET`: A secret key for security purposes (already set by default)

### 4. Verify Installation

Run the following command to verify that the application is working correctly:

```bash
bin/console list
```

You should see the available commands, including `gpstools:fit2gpx`.

## Usage

### Converting FIT to GPX

To convert a Garmin FIT file to GPX format:

```bash
bin/console gpstools:fit2gpx --fitpath=/path/to/your/file.fit
```

If you run the command without arguments, it will prompt you for the FIT file path:

```bash
bin/console gpstools:fit2gpx
```

The output GPX file will be created in the same directory as the input file, with the same name but a `.gpx` extension.

## Development

### Running Tests

```bash
bin/phpunit
```

### Using Codex

This repository includes Codex support for an optimized development experience:

1. **Connect to Codex**: Clone this repository in Codex by entering the repository URL in the Codex interface.

2. **Configure Setup Script**: You need to manually add the setup script through the Codex UI:
   - In the Codex interface, find the "Setup script" section
   - Add `bash .codex/setup.sh` to the setup commands
   - The script will run immediately after the repo is cloned

3. **Benefits**:
   - Preconfigured PHP 8.3 environment
   - All required extensions installed automatically
   - Dependencies managed through Composer
   - Ready-to-use development environment

The setup script handles installation checks and only installs missing components, making environment initialization fast and efficient. Note that internet access is disabled after the setup script runs.

### Configuration

- Configuration files are stored in the `config/` directory
- Templates for output formats are in the `templates/` directory

## License

This project is proprietary software.
