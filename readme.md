# Storidian
## Assuredly self-hosted

![Storidian Logo](resources/images/storidian-logo.png)

> **Status:** Early Development - The foundation is being laid!

## What is Storidian?

Storidian is an open-source, self-hosted file storage application that puts you in complete control of your digital assets. Built with security and simplicity as core principles, Storidian empowers users to create personal cloud storage solutions without compromising on privacy or functionality.

## Our Mission

We believe your data belongs to you alone. Storidian provides a robust alternative to corporate cloud services that monetize your information. By creating powerful, accessible self-hosting tools, we aim to build a future where data sovereignty is the norm, not the exception.

## Current Progress

The project is in early active development. The foundational architecture is being established:

- [x] Docker-based development environment
- [x] Laravel backend setup
- [x] Vue.js frontend integration with Vite
- [x] Hot module replacement (HMR) for development
- [ ] Core file management functionality
- [ ] User authentication system
- [ ] Storage backend abstraction
- [ ] File sharing capabilities

## Planned Features

* **Complete File Management:** Upload, organize, share, and sync files across all your devices
* **Enhanced Security:** End-to-end encryption, granular access controls, and comprehensive audit logging
* **Intuitive Interface:** Modern, responsive design that prioritizes usability without sacrificing power
* **Seamless Integration:** Open APIs and extension capabilities for integration with your existing tools
* **Resource Efficient:** Optimized performance even on modest hardware, making self-hosting accessible
* **Version Control:** Track changes, restore previous versions, and maintain file history
* **Collaborative Tools:** Secure sharing and collaborative editing with permissions you control

## Tech Stack

* **Backend:** Laravel (PHP)
* **Frontend:** Vue.js 3 with Vite
* **Styling:** Bootstrap 5 + SCSS
* **Icons:** Lucide
* **Container:** Docker with Caddy web server

## Development Setup

### Prerequisites

- Docker and Docker Compose

### Quick Start

1. Clone the repository
2. Copy the environment file:
   ```bash
   cp .env.example .env
   ```
3. Build and start Docker:
   ```bash
   docker compose up --build
   ```

The first run will install all dependencies, generate keys, and run migrations automatically.

### Configuration

Set these in your `.env` file as needed:

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_PORT` | 80 | HTTP port for the application |
| `VITE_PORT` | 5173 | Vite dev server port |
| `VITE_DEV_HOST` | localhost | HMR host (use your IP for remote access) |

## Contributing

We welcome contributions! As an early-stage project, there's plenty of opportunity to shape the direction of Storidian.

As an open-source project, we value:
* Transparent governance and decision-making
* Quality code that's well-tested and maintainable
* Inclusive community where diverse perspectives are welcomed
* Practical solutions that address real user needs

## License

MIT License - see [LICENSE](LICENSE) for details.

---

**Storidian**: Your files. Your control. Your peace of mind.
