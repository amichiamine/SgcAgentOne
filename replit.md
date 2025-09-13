# Overview

SGC-AgentOne is a code automation agent system designed to process natural language commands for file operations and development tasks. The system provides both a web interface and VSCode extension, with multilingual support (French and English) for creating, updating, and managing files through pattern-based command recognition.

# User Preferences

Preferred communication style: Simple, everyday language.

# System Architecture

## Core Agent Engine
The system uses a rule-based pattern matching approach for command interpretation. Commands are processed through:
- **Pattern Recognition**: JSON-based rules map natural language patterns to specific actions
- **Action Whitelist**: Security layer ensuring only approved operations can be executed
- **Blind Execution Control**: Safety mechanism to prevent unauthorized code execution

## Frontend Architecture
The application implements a dual-interface approach:
- **Web Interface**: HTML templates with vanilla JavaScript and CSS using a "claymorphism" design system
- **VSCode Extension**: Webview-based chat interface integrated into the VSCode environment
- **Template System**: Reusable HTML/CSS/JS templates with placeholder substitution for dynamic content generation

## Security Framework
Multi-layered security implementation includes:
- **API Key Authentication**: Required for all operations with configurable origins
- **Action Whitelisting**: Restricted operation set (create file, update file, read file, execute query, create database)
- **CORS Protection**: Configurable allowed origins for web requests
- **Blind Execution Toggle**: Disabled by default to prevent arbitrary code execution

## Configuration Management
Centralized configuration system using JSON files for:
- **Application Settings**: Port, security, and feature toggles
- **Rule Definitions**: Pattern-to-action mappings for command processing
- **Template Configuration**: Dynamic content generation settings

## UI Design System
Custom "SGC-Commander" theme with:
- **Claymorphism Effects**: 3D-style button and panel effects using CSS gradients and shadows
- **HSL Color Palette**: Consistent dark theme with cyan accent colors
- **Typography**: Inter font for UI, JetBrains Mono for code display
- **Responsive Layout**: Container-based design with flexible grid system

# External Dependencies

## Frontend Dependencies
- **Google Fonts**: Inter and JetBrains Mono font families
- **Font Awesome**: Icon library for UI elements (v6.5.0)

## Development Environment
- **VSCode Integration**: Extension architecture for IDE integration
- **Replit Platform**: Configured for deployment on Replit with CORS settings for *.replit.dev domains

## Database Support
- **SQLite**: Default database type with auto-creation capabilities
- **Configurable Database**: Template system supports different database types through configuration

## Security Services
- **CORS Configuration**: Built-in support for cross-origin requests
- **API Authentication**: Custom API key system for request validation

## Deployment Systems
- **Development Server**: PHP built-in server for Replit environment
- **Plug & Play Package**: Universal deployment system for Apache/PHP environments (XAMPP, LAMP, MAMP, web hosting)

# Recent Changes

## Latest Modifications (September 2025)
- Successfully implemented and tested intelligent "Help" system in chat interface with 3 commands: "help" (main menu), "help chat" (detailed chat guide), "help ide" (complete IDE guide)
- Created help action files (showHelpMenu.php, showChatHelp.php, showIdeHelp.php) with smart content extraction from existing .md documentation files
- Enhanced interpreter logic with exact pattern matching for help commands, resolving previous matching conflicts
- Confirmed multi-language support (French/English) for all help commands with 9 pattern variations
- **NEW: Created SGC-AgentOne Plug & Play deployment system** - Universal compatibility package for all Apache/PHP environments
- Renamed deployment/shared-hosting to deployment/plugandplay with enhanced documentation covering XAMPP, LAMP, MAMP, and web hosting
- Added automatic installation script (install.php) with environment diagnostics and file creation
- Updated documentation for universal compatibility: local development (XAMPP/LAMP/MAMP) and web hosting deployment