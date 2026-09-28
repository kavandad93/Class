# 🎓 Kadad Class

> **A modern online classroom platform by Kadad** — built for online classes, real-time communication, and collaborative learning.

<p align="center">
  <a href="https://class.kadad.ir"><img src="https://img.shields.io/badge/Live%20Project-class.kadad.ir-111827?style=for-the-badge" alt="Live Project"></a>
  <img src="https://img.shields.io/badge/Release-V1.0.1-2563eb?style=for-the-badge" alt="Version">
  <img src="https://img.shields.io/badge/License-MIT-16a34a?style=for-the-badge" alt="MIT License">
</p>

---

## 🌐 Live Project

### **The main and live version of Kadad Class is available at:**

**👉 [class.kadad.ir](https://class.kadad.ir)**

This GitHub repository contains the project's source code and development history. The **actual production service** is hosted and operated at **class.kadad.ir**.

> **Important:** The GitHub repository is the source-code repository. For the real, deployed Kadad Class experience, visit **[class.kadad.ir](https://class.kadad.ir)**.

---

## ✨ Features

- 🔐 User registration and login
- 👥 Student, teacher, and administrator roles
- 🏫 Class creation and management
- 🔑 Class code-based joining
- 🛡️ Class member access control
- ✅ Join requests with teacher/administrator approval
- 💬 Class chat
- 🎥 Real-time video communication
- 🎙️ Real-time audio communication
- 🖥️ Screen sharing
- 🎛️ Microphone, camera, and screen-sharing permissions
- 🌐 Persian RTL user interface
- ☁️ Cloudflare RealtimeKit integration
- 🚀 cPanel Git-based deployment

---

## 🛡️ Security

Kadad Class includes several security measures, including:

- PDO prepared statements for database queries
- Secure password hashing with `password_hash`
- Session ID regeneration on login
- CSRF protection for state-changing operations
- Class-level and user-level access control
- HTML output escaping to reduce XSS risks
- Protection for the chat endpoint
- Sensitive Cloudflare credentials stored outside `public_html`

> **Production security note:** Source-code security measures do not replace an independent review of the production environment. Web-server configuration, TLS, security headers, hosting configuration, and deployment settings should be reviewed separately.

---

## 🧩 Project Structure

| Directory | Purpose |
|---|---|
| `app/` | Laravel application logic |
| `routes/` | Laravel routes |
| `database/` | Database migrations and SQL files |
| `login/` | Login, registration, and logout |
| `panel/` | User and class management panels |
| `join/` | Class joining and session interface |
| `room/` | Room interface and chat API |
| `realtime/` | Cloudflare RealtimeKit token generation and management |
| `includes/` | Bootstrap and shared functions |
| `assets/` | Public assets |

---

## ⚙️ Production Environment

| Component | Configuration |
|---|---|
| 🌐 Production Domain | [class.kadad.ir](https://class.kadad.ir) |
| 🐘 PHP | 8.1 |
| 🗄️ Database | MySQL 5.7 |
| ☁️ Hosting | cPanel |
| 🚀 Deployment | cPanel Git Version Control |
| 📦 Source | GitHub |

---

## 🔧 Configuration

Sensitive information such as database credentials and Cloudflare API tokens **must never be committed to Git**.

In the current production environment, sensitive configuration is stored outside the document root:

`/home2/kadad/data.php`

Keep credentials, API tokens, and other secrets outside publicly accessible directories.

---

## 🎮 More from Kadad

> ### **Looking for more games and entertainment?**
>
> **[Kadad.ir](https://kadad.ir)** is the place to discover lots of **games, tools, and entertainment** from Kadad.
>
> 🌍 The website is currently **in Persian**. English-speaking visitors can use **Google Translate** to translate the website and explore its content.

---

## 📌 Version

**V1.0.1 — Final Security Release**

This release follows an internal security review and includes security hardening and improvements for the final version.

---

## 📄 License

This project is released under the **MIT License**.

See the [LICENSE](LICENSE) file for the complete license text.

---

<p align="center">
  <strong>Kadad Class</strong><br>
  Online classes. Real-time communication. Simple learning.
</p>