# PapuaJourneyExpo (Omaki Platform)

**A Tourism Marketplace Platform for Papua, Indonesia**

PapuaJourneyExpo is a comprehensive web platform that connects tourists with local businesses (UMKM) in Papua, featuring an AI-powered chatbot assistant for tourism information.

## 🌟 Features

- **Multi-User System**: Three distinct user types with specialized dashboards
  - Admin: Platform administration and monitoring
  - UMKM: Business portal for listing tourism services and products
  - Users: Tourist portal for browsing destinations and services
- **AI-Powered Chatbot**: RAG (Retrieval-Augmented Generation) chatbot using Google Gemini
- **Tourism Marketplace**: Browse and discover local businesses and services
- **Article Management**: Tourism articles and information system
- **User Authentication**: Secure session-based authentication system
- **Docker Support**: Full containerized deployment option

## 🏗️ Architecture Overview

### Technology Stack
- **Backend**: PHP 8.0
- **Database**: MySQL/MariaDB
- **AI/ML**: Python 3.9, Google Gemini API, ChromaDB
- **Frontend**: HTML, CSS, JavaScript, Bootstrap
- **Containerization**: Docker & Docker Compose

### Project Structure
```
PapuaJourneyExpo/
├── admin/              # Admin portal
│   ├── artikeladmin.php
│   ├── dashboard.php
│   ├── editorartikel.php
│   ├── navbar.php
│   ├── sidebaradmin.php
│   ├── umkmadmin.php
│   └── useradmin.php
├── config/             # Configuration files
│   └── database.php    # Database connection with env support
├── docker/             # Docker configuration
│   └── apache-config.conf
├── umkm/               # UMKM (Business) portal
│   ├── artikel_add.php
│   ├── artikel_delete.php
│   ├── artikel_edit.php
│   ├── artikel_list.php
│   ├── dashboard.php
│   ├── navbar.php
│   └── profile_edit.php
├── users/              # User/Tourist portal
│   ├── artikel.php
│   ├── artikel_detail.php
│   ├── chatbot/        # AI Chatbot system
│   │   ├── chat_style.css
│   │   ├── chatbot.php
│   │   ├── chatbot_process.php
│   │   ├── data/       # Tourism data (JSON)
│   │   └── rag_py/     # Python RAG implementation
│   ├── components/
│   │   └── navbar.php
│   ├── dashboard.php
│   ├── profile.php
│   ├── style/
│   ├── umkm.php
│   └── wisata.php
├── uploads/            # File uploads directory
│   ├── artikel_images/
│   └── profile_images/
├── docker-compose.yml  # Docker orchestration
├── Dockerfile          # Container definition
├── docker-entrypoint.sh
├── index.php           # Entry point (redirects to login)
├── login.php           # Login page
├── logout.php          # Logout handler
├── omaki_db.sql        # Database schema
└── register.php        # User registration
```

## 🚀 Quick Start with Docker

### Prerequisites
- Docker and Docker Compose installed
- Google Gemini API key

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd PapuaJourneyExpo
   ```

2. **Create environment file for Gemini API**
   ```bash
   # Create the .env file in the chatbot directory
   echo "GEMINI_API_KEY=your_api_key_here" > users/chatbot/rag_py/.env
   ```

3. **Build and start containers**
   ```bash
   docker compose up -d
   ```

4. **Access the application**
   - Main Application: http://localhost:8090
   - phpMyAdmin: http://localhost:8081
   - ChromaDB: http://localhost:8000

### Default Credentials
- **phpMyAdmin**: root / root_password
- **Test Users**: Check the database after initialization

## 🛠️ Manual Installation (Without Docker)

### Requirements
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.4+
- Python 3.9+
- Apache/Nginx web server

### Installation Steps

1. **Setup Database**
   ```bash
   mysql -u root -p
   CREATE DATABASE omaki_db;
   mysql -u root -p omaki_db < omaki_db.sql
   ```

2. **Configure Database Connection**
   Edit `config/database.php` if needed (uses environment variables by default)

3. **Install Python Dependencies**
   ```bash
   cd users/chatbot/rag_py
   pip install -r requirements.txt
   ```

4. **Setup Gemini API Key**
   ```bash
   echo "GEMINI_API_KEY=your_api_key_here" > users/chatbot/rag_py/.env
   ```

5. **Run ChromaDB**
   ```bash
   docker run -d --name chromadb -p 8000:8000 chromadb/chroma
   ```

6. **Initialize Embeddings**
   ```bash
   cd users/chatbot/rag_py
   python embed.py
   ```

7. **Configure Web Server**
   Point your web server document root to the project directory

## 📚 Database Schema

### Main Tables
- **users**: User accounts and authentication
- **umkm**: Business profiles and information
- **artikel**: Articles/products listed by businesses
- **wisata**: Tourism destinations
- **chat_conversations**: Chatbot conversation history
- **chat_conversation_sessions**: Chatbot session management

### User Types
1. **admin**: Platform administrators
2. **umkm**: Business owners
3. **user**: Tourists/customers

## 🤖 AI Chatbot System

### Architecture
- **RAG Implementation**: Uses ChromaDB for vector storage and Google Gemini for generation
- **Data Source**: JSON files in `/users/chatbot/data/jayapura/`
- **Languages**: Responds in Indonesian (Bahasa Indonesia)
- **Model**: Google Gemini 2.5 Flash

### Chatbot Features
- Tourism information about Jayapura
- Restaurant and food recommendations
- Transportation options
- Cultural experiences
- Interactive conversation with context awareness

### Updating Chatbot Knowledge
1. Add/modify JSON files in `/users/chatbot/data/jayapura/`
2. Re-run embedding generation:
   ```bash
   cd users/chatbot/rag_py
   python embed.py
   ```

## 🔧 Configuration

### Environment Variables
The application supports the following environment variables:
- `DB_HOST`: Database host (default: localhost)
- `DB_NAME`: Database name (default: omaki_db)
- `DB_USER`: Database username
- `DB_PASSWORD`: Database password
- `CHROMADB_HOST`: ChromaDB host (default: localhost)
- `CHROMADB_PORT`: ChromaDB port (default: 8000)

### File Uploads
- Maximum file size: 10MB (configurable in Apache config)
- Supported formats: Images (JPG, PNG, GIF)
- Upload directories must have write permissions

## 🚢 Docker Deployment

### Docker Services
1. **web**: PHP application with Apache
2. **mysql**: MariaDB database
3. **chromadb**: Vector database for chatbot
4. **phpmyadmin**: Database management UI

### Docker Commands
```bash
# Start all services
docker compose up -d

# View logs
docker compose logs -f

# Stop all services
docker compose down

# Rebuild after changes
docker compose build --no-cache

# Access container shell
docker compose exec web bash
```

### Volumes
- `mysql_data`: Persistent database storage
- `chromadb_data`: Persistent vector embeddings
- `./uploads`: Shared upload directory

## 🔒 Security Considerations

1. **Authentication**: Session-based with 8-hour timeout
2. **Password Hashing**: Uses PHP's password_hash()
3. **SQL Injection**: Prevented using prepared statements
4. **XSS Protection**: htmlspecialchars() on outputs
5. **File Upload Validation**: MIME type checking
6. **API Keys**: Store in .env files (not in repository)

## 📝 Development Guidelines

### Adding New Features
1. Follow existing PHP procedural style
2. Include appropriate navigation components
3. Check user authentication at the start of protected pages
4. Update database schema in `omaki_db.sql`
5. Use prepared statements for all database queries

### Code Style
- PHP: Procedural style with mysqli
- JavaScript: Vanilla JS for chatbot interface
- CSS: Custom styles with Bootstrap framework
- Python: PEP 8 compliant for RAG system

## 🐛 Troubleshooting

### Common Issues

1. **ChromaDB Connection Error**
   - Ensure ChromaDB container is running
   - Check CHROMADB_HOST environment variable
   - Verify port 8000 is not blocked

2. **Chatbot Not Responding**
   - Verify Gemini API key is set correctly
   - Check Python dependencies are installed
   - Ensure embeddings are initialized

3. **Database Connection Failed**
   - Verify MySQL is running
   - Check database credentials
   - Ensure database is imported correctly

4. **File Upload Issues**
   - Check directory permissions (777 for uploads)
   - Verify Apache upload limits
   - Ensure proper MIME types

## 📄 License

This project is proprietary software. All rights reserved.

## 🤝 Contributing

Please follow these guidelines:
1. Create feature branches from `main`
2. Follow existing code style
3. Test thoroughly before submitting
4. Update documentation as needed

## 📞 Support

For issues and questions:
- Create an issue in the repository
- Contact the development team

---

**Note**: Remember to never commit sensitive information like API keys or passwords. Always use environment variables for configuration.