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
- **ACCOMPLI: ZÉRO chemins absolus** - Conversion complète et audit exhaustif pour éliminer TOUS les chemins absolus
- Corrigé les fonctions getProjectInfo() et getApiBase() dans browser.js et files.js pour utiliser des chemins relatifs intelligents
- Unifié la détection d'environnement (seulement .replit.dev = Replit, suppression confusion localhost)
- Optimisé chat.html avec chemins relatifs fixes : themePath = './theme', apiPath = '../../api'
- **CRITIQUE: Corrigé iframe Browser** - Remplacé src="/extensions/webview/browser.html" par "browser.html"
- **CRITIQUE: Corrigé explorateur de fichiers** - Remplacé fetch("/api/files/list") par getApiBase()
- **CRITIQUE: Corrigé navigation Browser** - Amélioré cleanUrl() et isValidUrl() pour préserver chemins relatifs
- **RÉSULTAT FINAL** : ZÉRO chemins absolus - Compatible universellement (Replit, XAMPP, LAMP, MAMP, hébergement mutualisé)
- Successfully implemented and tested intelligent "Help" system in chat interface with 3 commands: "help" (main menu), "help chat" (detailed chat guide), "help ide" (complete IDE guide)
- Created help action files (showHelpMenu.php, showChatHelp.php, showIdeHelp.php) with smart content extraction from existing .md documentation files
- Enhanced interpreter logic with exact pattern matching for help commands, resolving previous matching conflicts
- Confirmed multi-language support (French/English) for all help commands with 9 pattern variations
- **NEW: Created SGC-AgentOne Plug & Play deployment system** - Universal compatibility package for all Apache/PHP environments
- Renamed deployment/shared-hosting to deployment/plugandplay with enhanced documentation covering XAMPP, LAMP, MAMP, and web hosting
- Added automatic installation script (install.php) with environment diagnostics and file creation
- Updated documentation for universal compatibility: local development (XAMPP/LAMP/MAMP) and web hosting deployment
- **NEW: Implemented 8th view - Browser** - Complete web browser integration with project preview capabilities
- Created browser.html (web interface), browser.css (SGC-Commander styling), browser.js (navigation logic)
- Added centralized navigation system (navigation.js) with cross-iframe communication via postMessage
- Integrated Browser button in main navigation with iframe-based view switching
- Features: address bar, web navigation, local project preview, bookmarks, quick access to dev resources
- Enhanced security: frame-busting detection, error handling with "open in new tab" fallback for blocked sites
- Fixed routing issues and asset path resolution for proper browser view integration
- **NEW: Restructured file hierarchy** - Simplified webview file organization from `extensions/vscode/src/webview/` to `extensions/webview/`
- Updated router.php to serve chat interface from the new simplified path structure
- Maintained all functionality while reducing directory depth and improving maintainability
- Confirmed all assets (CSS themes, JavaScript modules, HTML templates) load correctly from new locations
- **NEW: Enhanced Browser view display** - Fixed iframe height issue in chat interface by implementing flex layout structure
- Modified JavaScript in chat.html to use consistent flex-based layout for browser view matching other tabs
- Improved user experience with properly sized web navigation area that fills available space dynamically
- **FIXED: Deployment path consistency** - Corrected all deployment files to use new `extensions/webview/` path instead of old `extensions/vscode/src/webview/`
- Updated install.php, index.php, and INSTALLATION.md (9 total corrections) ensuring universal compatibility with XAMPP, LAMP, MAMP, and shared hosting
- Resolved installation issues where deployment scripts searched for chat.html in incorrect directory structure
- **FIXED: CSS styling on XAMPP/shared hosting** - Corrected relative paths in chat.html for theme CSS files
- Changed CSS links from `extensions/webview/theme/` to `theme/` for proper loading on XAMPP, LAMP, MAMP, and shared hosting environments
- Ensured visual consistency across all deployment environments with complete SGC-Commander theme application
- **FINAL: Achieved TRUE 100% Plug & Play status** - Created all required files for universal deployment without any installation steps
- Added .htaccess (Apache configuration), index.php (intelligent entry point), data/ directory (secure database storage) at root level
- Implemented automatic environment detection and redirection system for seamless access across all platforms
- Removed deployment/ directory as system now works universally: Replit preview, XAMPP/local hosting, and shared web hosting
- **COMPLETED: Universal path detection** - All CSS and API calls now use dynamic detection for perfect compatibility regardless of folder name or hosting environment