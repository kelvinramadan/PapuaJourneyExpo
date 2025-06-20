# Docker Setup for Windows

This guide helps you run PapuaJourneyExpo with Docker on Windows.

## Prerequisites

1. **Docker Desktop for Windows**
   - Download from: https://www.docker.com/products/docker-desktop/
   - Ensure WSL 2 backend is enabled (recommended)

2. **Git for Windows**
   - Download from: https://git-scm.com/download/win
   - During installation, choose "Checkout as-is, commit Unix-style line endings"

## Setup Instructions

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd PapuaJourneyExpo
   ```

2. **Ensure correct line endings**
   ```bash
   # This should already be configured via .gitattributes
   git config core.autocrlf input
   ```

3. **Build and run the containers**
   ```bash
   docker compose up --build
   ```

## Troubleshooting

### "exec format error" or "no such file or directory"
This is usually caused by incorrect line endings. The fix is already implemented in our Dockerfile, but if you still encounter issues:

1. Rebuild the containers:
   ```bash
   docker compose down
   docker compose build --no-cache
   docker compose up
   ```

2. If the problem persists, manually fix line endings:
   ```bash
   # In Git Bash or WSL
   dos2unix docker-entrypoint.sh
   ```

### Port conflicts
If you get port binding errors, the default ports might be in use:
- Web app: 8090 → http://localhost:8090
- ChromaDB: 8000 → http://localhost:8000
- phpMyAdmin: 8081 → http://localhost:8081
- MySQL: 3306

To change ports, edit `docker-compose.yml` and modify the port mappings.

### Slow performance
Docker on Windows can be slower than on Linux. To improve performance:
1. Use WSL 2 backend (recommended)
2. Exclude project folder from Windows Defender scanning
3. Allocate more resources in Docker Desktop settings

## Accessing the Application

Once running, access:
- Main application: http://localhost:8090
- phpMyAdmin: http://localhost:8081
  - Username: root
  - Password: root_password

## Stopping the Application

```bash
# Stop containers
docker compose stop

# Stop and remove containers
docker compose down

# Stop and remove everything (including volumes)
docker compose down -v
```