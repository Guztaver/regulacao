# Regulação List

A modern Laravel + Vue.js application for managing patient entries and medical procedures with advanced filtering capabilities.

## Features

### Dashboard
- **Modal-based Patient Creation** - Clean popup interface for adding new patients
- **Modal-based Entry Creation** - Streamlined entry creation with patient selection
- **Advanced Filtering System** - Date ranges, patient names, entry IDs with real-time updates
- **Configurable Display Limits** - Choose 5, 10, 25, 50, or 100 entries per view
- **Real-time Updates** - Automatic refresh after create/update/delete operations

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

## Installation

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
3. Start by creating patients using the "Add Patient" button
4. Create entries for patients using the "Add Entry" button

### Managing Entries
- **Active Entries**: View and manage ongoing procedures on the Dashboard
- **Completed Entries**: Access completed procedures at `/entries/completed`
- **Filtering**: Use the filter popup to search by date, patient name, or entry ID
- **Actions**: Complete, uncomplete, or delete entries as needed

## API Endpoints

### Entries
- `GET /api/entries` - List active entries (supports filtering)
- `POST /api/entries` - Create new entry
- `GET /api/entries/completed` - List completed entries (supports filtering)
- `PUT /api/entries/{id}/complete` - Toggle entry completion status
- `DELETE /api/entries/{id}` - Delete entry

### Patients
- `GET /api/patients` - List all patients
- `POST /api/patients` - Create new patient

### Filter Parameters
All entry endpoints support these query parameters:
- `date_from` - Start date filter (YYYY-MM-DD)
- `date_to` - End date filter (YYYY-MM-DD)
- `patient_name` - Search by patient name (partial match)
- `entry_id` - Find specific entry by ID
- `limit` - Number of results (1-100, default: 10)

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
# Creates demo patients and entries (optional)
php artisan db:seed --class=CompletedEntriesSeeder

# Creates test user (optional)
php artisan db:seed
```

**Warning**: Do not run seeders in production - they create test data only.

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
- No pre-populated patients or entries
- Ready for production use
- All test/demo data removed
- Seeders are optional and clearly marked for development only

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open-sourced software licensed under the MIT license.