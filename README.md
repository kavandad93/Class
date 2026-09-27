Kadad Class

Kadad Class is an online classroom platform by Kadad, designed for hosting online classes with authentication, member management, chat, and real-time video/audio communication using Cloudflare RealtimeKit.

«The main and live version of Kadad Class is deployed at "class.kadad.ir" (https://class.kadad.ir).

The GitHub repository contains the project's source code and development history. The actual production service is hosted and operated at class.kadad.ir.»

Status

Final Release — V1.0.1

This release follows an internal security review and includes additional security hardening and improvements for the final version.

Features

- User registration and login
- Student, teacher, and administrator roles
- Class creation and management
- Class code-based joining
- Class member access control
- Join requests with teacher/administrator approval
- Class chat
- Video, audio, and screen sharing using Cloudflare RealtimeKit
- Microphone, camera, and screen-sharing permission management for students
- Persian RTL user interface
- Deployment using cPanel Git Version Control

Security

- Database queries using PDO prepared statements
- Passwords securely stored using "password_hash"
- Session ID regeneration upon login
- CSRF protection for state-changing operations
- Class-level and user-level access control
- HTML output escaping to reduce XSS risks
- Sensitive Cloudflare credentials stored outside "public_html"
- CSRF protection for the chat endpoint

«The project should still be reviewed in its actual production environment, independently of source-code review, particularly regarding web-server configuration, TLS, security headers, and hosting configuration.»

Project Structure

- "app/" — Laravel application logic
- "routes/" — Laravel routes
- "database/" — Database migrations and SQL files
- "login/" — Login, registration, and logout
- "panel/" — User and class management panels
- "join/" — Class joining and session interface
- "room/" — Room interface and chat API
- "realtime/" — Cloudflare RealtimeKit token generation and management
- "includes/" — Bootstrap and shared functions
- "assets/" — Public assets

Production Environment

- Production Domain: "class.kadad.ir"
- PHP: 8.1
- Database: MySQL 5.7
- Hosting: cPanel
- Deployment: cPanel Git Version Control
- Repository: GitHub

Live Production Instance

The primary production instance of Kadad Class is available at:

https://class.kadad.ir

This is the main deployed version of the project and the environment used for actual online classes.

Configuration

Sensitive information such as database credentials and Cloudflare API tokens must never be committed to Git.

In the current production environment, sensitive configuration is stored outside the document root:

/home2/kadad/data.php

License

This project is released under the MIT License. The complete license text is available in the "LICENSE" file.

Version

"V1.0.1" — Final Security Release
