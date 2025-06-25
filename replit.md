# OdiHost - Web Hosting Platform

## Overview

OdiHost is a web hosting platform that allows users to create and host simple web projects using a browser-based code editor. Users authenticate via Google OAuth, create projects with HTML/CSS/JavaScript files, and access their hosted sites through wildcard subdomains.

## System Architecture

### Backend Architecture
- **Language**: PHP (no frameworks)
- **Authentication**: Google OAuth 2.0
- **Data Storage**: PostgreSQL database with file-based project storage
- **Web Server**: Built-in PHP development server (php -S)
- **Session Management**: PHP sessions with security headers

### Frontend Architecture
- **Code Editor**: Monaco Editor (Microsoft's VS Code editor for web)
- **UI Framework**: Custom CSS with modern design principles
- **JavaScript**: Vanilla JS for interactivity
- **Responsive Design**: Mobile-first approach

### File Structure
```
/users/{username}/{project}/
├── index.html
├── style.css
├── script.js
└── metadata.json
```

## Key Components

### Authentication System
- **Google OAuth Integration**: Users authenticate using Google accounts only
- **Two-step Registration**: After OAuth, users choose a unique username
- **Username Validation**: Real-time availability checking with suggestions
- **Session Security**: HTTP-only cookies, secure flags, strict mode

### Project Management
- **Dashboard Interface**: Grid-based project overview
- **CRUD Operations**: Create, read, update, delete projects via API
- **File Management**: Support for HTML, CSS, and JavaScript files
- **Live Preview**: Real-time preview via iframe

### Code Editor
- **Monaco Editor**: Full-featured code editor with syntax highlighting
- **Multi-tab Interface**: Switch between HTML, CSS, and JavaScript files
- **Auto-save**: Automatic saving with 1-second debounce
- **File Upload**: Support for additional file uploads

### Hosting System
- **Wildcard Subdomains**: `{username}.odivax.com/{project}`
- **Custom Domain Support**: Framework for custom domain mapping
- **Static File Serving**: Direct file serving for hosted projects
- **Security Headers**: X-Content-Type-Options, X-Frame-Options, XSS Protection

## Data Flow

### User Registration Flow
1. User clicks "Login with Google"
2. Google OAuth redirects to callback.php
3. System exchanges code for access token
4. Retrieves user profile from Google API
5. Checks if user exists, if not redirects to username selection
6. Creates user directory and metadata file
7. Establishes session and redirects to dashboard

### Project Creation Flow
1. User submits project name via dashboard
2. System creates project slug and directory structure
3. Initializes default HTML, CSS, and JavaScript files
4. Updates user metadata with new project
5. Project becomes accessible via subdomain

### File Editing Flow
1. Editor loads project files via API
2. Monaco Editor displays file content with syntax highlighting
3. User edits code with real-time auto-save
4. Changes are immediately reflected in live preview
5. Files are saved to project directory

## External Dependencies

### Third-party Services
- **Google OAuth 2.0**: User authentication and profile data
- **Monaco Editor CDN**: Code editor functionality
- **Google Fonts**: Typography (if used)

### PHP Extensions Required
- **cURL**: For Google OAuth API calls
- **JSON**: For metadata handling
- **Sessions**: For user state management
- **File System**: For project storage

## Deployment Strategy

### Development Environment
- **Replit Integration**: Configured for Replit hosting
- **PHP Built-in Server**: Development server on port 5000
- **File Permissions**: 0755 for directories, appropriate file permissions

### Production Considerations
- **Wildcard DNS**: Requires *.odivax.com DNS configuration
- **HTTPS**: Force HTTPS in production (currently commented out)
- **File Security**: Prevents access to .json and .htaccess files
- **Compression**: Gzip compression for static assets

### Security Features
- **Input Sanitization**: Username and file path validation
- **Directory Traversal Protection**: Restricted file access
- **Session Security**: Secure session configuration
- **CSRF Protection**: Implemented where needed

## Changelog

```
Changelog:
- June 25, 2025. Initial setup with JSON-based storage
- June 25, 2025. Migrated to PostgreSQL database for improved data management
```

## User Preferences

```
Preferred communication style: Simple, everyday language.
```

## Architecture Decisions

### File-based Storage vs Database
**Problem**: Need persistent data storage for user metadata and project information
**Solution**: JSON file-based storage in user directories
**Rationale**: Simplifies deployment, reduces dependencies, suitable for MVP scale
**Trade-offs**: Limited scalability but easier maintenance and backup

### Monaco Editor Integration
**Problem**: Need a professional code editing experience in the browser
**Solution**: Microsoft's Monaco Editor via CDN
**Rationale**: Industry-standard editor with excellent syntax highlighting and features
**Trade-offs**: External dependency but provides superior user experience

### Wildcard Subdomain Routing
**Problem**: Need to serve user projects on custom URLs
**Solution**: PHP-based subdomain detection and file serving
**Rationale**: Allows clean URLs without complex server configuration
**Trade-offs**: Requires wildcard DNS setup but provides professional hosting experience

### Google OAuth Only
**Problem**: Need secure user authentication
**Solution**: Google OAuth 2.0 as the sole authentication method
**Rationale**: Reduces complexity, leverages trusted provider, no password management
**Trade-offs**: Limits users to Google accounts but improves security and UX