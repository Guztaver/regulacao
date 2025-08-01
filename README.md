# Lista da regulação

A comprehensive Laravel + Vue.js application for managing patients, medical entries, and entry document attachments with advanced filtering capabilities and SUS integration.

Made with ❤️ by [Gustavo M.](https://github.com/guztaver)

## Features

### Dashboard
- **Modal-based Patient Creation** - Clean popup interface for adding new patients with SUS number support
- **Modal-based Entry Creation** - Streamlined entry creation with patient selection
- **Advanced Filtering System** - Date ranges, patient names, entry IDs with real-time updates
- **Configurable Display Limits** - Choose 5, 10, 25, 50, or 100 entries per view
- **Real-time Updates** - Automatic refresh after create/update/delete operations

### Patient Management
- **Complete Patient Information** - Name, email, phone, and SUS (Sistema Único de Saúde) number
- **Patient List View** - Comprehensive table with search and filtering capabilities
- **Individual Patient View** - Detailed patient information with statistics and quick actions
- **Patient Editing** - Update patient information including SUS number

### Entry Document Management
- **Entry File Upload** - Support for images, PDFs, DOC, and text files (max 10MB) per entry
- **Entry Document Categories** - Medical Records, Exam Results, Prescriptions, Insurance, Others
- **Entry Document Viewing** - Download and preview capabilities for entry documents
- **Entry Document Organization** - Description fields and type categorization per entry
- **Secure Storage** - Files stored securely with unique naming

### Completed Entries Management
- **Dedicated Completed View** - Separate interface for finished procedures
- **Uncomplete Functionality** - Mark completed entries as active again
- **Safe Deletion** - Delete with confirmation dialogs
- **Advanced Filtering Popup** - Comprehensive filter options in modal
- **Visual Filter Indicators** - See active filters at a glance

## Tech Stack

- **Backend**: Laravel 11.x with Eloquent ORM
- **Frontend**: Vue 3 with Composition API
- **UI**: Shadcn/ui components with Tailwind CSS
- **Database**: MySQL/PostgreSQL
- **API**: RESTful endpoints with JSON responses
- **Docker**: Optimized multi-stage builds (5-8 min build time)

## Quick Start

### 🚀 Fast Docker Build (Recommended)
```bash
# Clone repository
git clone <repository-url>
cd regulacao-list

# Fast production build (5-8 minutes)
./scripts/fast-build.sh

# Or use make command
make build-fast
```

### 📦 Traditional Installation

1. **Clone and install dependencies**
   ```bash
   git clone <repository-url>
   cd regulacao-list
   composer install
   npm install
   ```

2. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database setup**
   ```bash
   php artisan migrate
   ```

4. **Build assets**
   ```bash
   npm run build
   ```

5. **Start development server**
   ```bash
   php artisan serve
   ```

## Usage

### Getting Started
1. Create a user account through registration
2. Access the dashboard at `/dashboard`
3. Start by creating patients using the "Add Patient" button (include SUS number if available)
4. Upload entry documents for better record keeping
5. Create entries for patients using the "Add Entry" button

### Managing Patients
- **Patient List**: View all patients at `/patients` with search capabilities
- **Patient Details**: Click "View" on any patient to see detailed information
- **SUS Integration**: Store and search by SUS (Sistema Único de Saúde) numbers
- **Document Upload**: Attach identity documents, medical records, exam results, etc.
- **Patient Statistics**: View entry counts and completion statistics

### Managing Entries
- **Active Entries**: View and manage ongoing procedures on the Dashboard
- **Completed Entries**: Access completed procedures at `/entries/completed`
- **Filtering**: Use the filter popup to search by date, patient name, or entry ID
- **Actions**: Complete, uncomplete, or delete entries as needed

### Document Features
- **Categorized Storage**: Organize documents by type (Identity, Medical Records, etc.)
- **Secure Upload**: Files stored with unique names and proper validation
- **Download/Preview**: Easy access to uploaded documents
- **File Management**: Delete documents with confirmation prompts

## API Endpoints

### Entries
- `GET /api/entries` - List active entries (supports filtering)
- `POST /api/entries` - Create new entry
- `GET /api/entries/completed` - List completed entries (supports filtering)
- `PUT /api/entries/{id}/complete` - Toggle entry completion status
- `DELETE /api/entries/{id}` - Delete entry

### Patients
- `GET /api/patients` - List all patients (supports search and filtering)
- `POST /api/patients` - Create new patient (with SUS number support)
- `GET /api/patients/{id}` - Get patient details with statistics
- `PUT /api/patients/{id}` - Update patient information
- `DELETE /api/patients/{id}` - Delete patient

### Entry Documents
- `GET /api/entries/{id}/documents` - List entry documents
- `POST /api/entries/{id}/documents` - Upload new document to entry
- `GET /api/entries/{id}/documents/{docId}` - Get entry document details
- `GET /api/entries/{id}/documents/{docId}/download` - Download entry document
- `DELETE /api/entries/{id}/documents/{docId}` - Delete entry document
- `GET /api/entry-document-types` - Get available entry document types

### Filter Parameters
### Entry Filter Parameters
All entry endpoints support these query parameters:
- `date_from` - Start date filter (YYYY-MM-DD)
- `date_to` - End date filter (YYYY-MM-DD)
- `patient_name` - Search by patient name (partial match)
- `entry_id` - Find specific entry by ID
- `limit` - Number of results (1-1000, default: 10)

### Patient Filter Parameters
Patient endpoints support these query parameters:
- `search` - Search by name, email, or SUS number (partial match)
- `limit` - Number of results (1-1000, default: 50)

### Document Upload Parameters
Document upload requires:
- `file` - The document file (max 10MB)
- `document_type` - Document category (identity, cpf, sus_card, medical_record, exam_result, prescription, insurance, other)
- `description` - Optional description text

## Security Features

- Authentication required for all routes
- CSRF protection on forms
- Input validation (client and server-side)
- SQL injection prevention via Eloquent ORM
- XSS protection through data sanitization

## Development

### Test Data (Optional)
For development/testing purposes, you can generate sample data:

```bash
# Creates demo patients, entries, and entry documents (optional)
php artisan db:seed --class=CompletedEntriesSeeder

# Creates test user (optional)
php artisan db:seed
```

**Warning**: Do not run seeders in production - they create test data only.

### File Storage Setup
The application uses Laravel's public disk for document storage:

```bash
# Create symbolic link for file access
php artisan storage:link
```

Ensure your storage directory has proper write permissions.

### Building Assets
```bash
# Development
npm run dev

# Production build
npm run build

# Watch for changes
npm run dev -- --watch
```

## Clean Installation State

This application starts with a clean database:
- No pre-populated patients, entries, or entry documents
- Ready for production use with SUS integration
- All test/demo data removed
- Seeders are optional and clearly marked for development only
- Document storage configured for secure file management

## SUS Integration

The application includes full support for the Brazilian Sistema Único de Saúde (SUS):
- **SUS Number Field**: 15-digit SUS card numbers
- **Validation**: Unique SUS numbers per patient
- **Search Capability**: Find patients by SUS number
- **Optional Field**: SUS number is not required but recommended
- **Formatted Display**: SUS numbers displayed with proper spacing (XXX XXXX XXXX XXXX)

## Document Types

The system supports the following document categories:
- **Documento de Identidade** - Identity documents
- **CPF** - Brazilian tax ID documents
- **Cartão SUS** - SUS card documentation
- **Prontuário Médico** - Medical records
- **Resultado de Exame** - Exam results and lab reports
- **Receita Médica** - Medical prescriptions
- **Plano de Saúde** - Insurance documentation
- **Outros** - Other document types

## File Security

- Files stored with UUID-based names to prevent direct access
- Mime type validation on upload
- File size limits (10MB maximum)
- Secure download through application routes
- Automatic file cleanup when documents are deleted

## Deployment

This application supports multiple deployment platforms:

### Option 1: Docker (Recommended for Production)

Complete containerization with development and production configurations:

```bash
# Development setup
make setup
make dev

# Production deployment
make prod-build
```

See [DOCKER.md](DOCKER.md) for comprehensive Docker documentation including:
- Multi-stage builds
- Docker Compose configurations
- GitHub Actions CI/CD
- Production optimizations

### Option 2: Railway (Nixpacks)

One-click deployment with automatic dependency detection:

```bash
# Install Railway CLI
npm install -g @railway/cli

# Deploy to Railway
railway login
railway init
railway up
```

See [RAILWAY.md](RAILWAY.md) for detailed Railway deployment guide including:
- Nixpacks configuration
- Database setup
- Environment variables
- Scaling and monitoring

### Option 3: Fly.io (Global Edge)

Global deployment with edge computing capabilities:

```bash
# Install Fly.io CLI
curl -L https://fly.io/install.sh | sh

# Automated deployment
./fly-deploy.sh

# Or manual deployment
flyctl auth login
flyctl launch
flyctl deploy
```

See [FLY.md](FLY.md) for complete Fly.io deployment guide including:
- Global scaling
- Multi-region deployment
- Performance optimization
- Cost optimization

### Option 4: Traditional Hosting

Standard LAMP/LEMP stack deployment:

1. Upload files to web server
2. Configure environment variables
3. Run migrations: `php artisan migrate --force`
4. Build assets: `npm run build`
5. Configure web server (Apache/Nginx)

## Deployment Documentation

- **[DOCKER.md](DOCKER.md)** - Complete Docker setup with CI/CD
- **[RAILWAY.md](RAILWAY.md)** - Railway platform deployment
- **[FLY.md](FLY.md)** - Fly.io global edge deployment

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly (using Docker, Railway, Fly.io, or traditional setup)
5. Submit a pull request

## License

This project is open-sourced software licensed under the MIT license.

---

Made with ❤️ by [Gustavo M.](https://github.com/guztaver)