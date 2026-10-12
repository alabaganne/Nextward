# Internly - Internship Management Platform

## About Internly

Internly is a comprehensive internship management platform designed for the Tunisian market. It connects students, companies, and university supervisors to streamline the internship application and management process.

### Key Features

- **Multi-User Platform**: Supports students, companies, university supervisors, and company supervisors
- **Internship Management**: Post, browse, and manage internship opportunities
- **Application System**: Complete application workflow with cover letters and attachments
- **Real-time Communication**: Built-in messaging system between all user types
- **Advanced Filtering**: Search internships by field, company, city, and keywords
- **Dashboard Analytics**: Role-based dashboards with relevant statistics
- **File Management**: Support for attachments in applications and messages
- **Notification System**: Real-time notifications for important events

### User Types

1. **Students** - Browse internships, apply with cover letters, track applications, communicate with companies
2. **Companies** - Post internships, review applications, manage company supervisors, communicate with students
3. **University Supervisors** - Oversee student progress, monitor applications, communicate with stakeholders
4. **Company Supervisors** - Supervise specific internships, review applications, mentor students

## Tech Stack

### Backend
- **Laravel 13** - PHP web framework
- **PHP 8.3+** - Server-side language
- **MySQL/PostgreSQL** - Database
- **Laravel Breeze-style auth** - Authentication controllers and pages
- **Pusher** - Real-time messaging and notifications
- **Laravel Echo** - WebSocket client

### Frontend
- **Vue.js 3** - Progressive JavaScript framework
- **Inertia.js 3** - Modern monolith approach (SPA-like experience)
- **Tailwind CSS 4** - Utility-first CSS framework
- **Vite** - Asset compilation
- **Vuex 4** - State management
- **Axios** - HTTP client

## Prerequisites

Before running this application, make sure you have the following installed:

- **PHP 8.3+** with extensions: BCMath, Ctype, cURL, DOM/XML, Mbstring, OpenSSL, PDO (MySQL, and SQLite for the test suite), Tokenizer
  - Ubuntu: `sudo apt install php8.5-curl php8.5-xml php8.5-mbstring php8.5-mysql php8.5-sqlite3 php8.5-bcmath`
- **Composer** - PHP dependency manager
- **Node.js 20.19+ (or 22.12+) & NPM** - For frontend asset compilation
- **MySQL/PostgreSQL** - Database server
- **Pusher Account** - For real-time features (optional for development)

## Installation & Setup

### 1. Clone the Repository

```bash
git clone <repository-url>
cd Internly
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node.js Dependencies

```bash
npm install
```

### 4. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` file with your database and Pusher credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stagi_tn
DB_USERNAME=your_username
DB_PASSWORD=your_password

PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret
PUSHER_APP_CLUSTER=your_pusher_cluster
```

### 5. Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### 6. Storage Setup

```bash
php artisan storage:link
```

### 7. Compile Frontend Assets

For development:
```bash
npm run dev
```

For production:
```bash
npm run build
```

### 8. Start the Development Server

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## Development

### Running Tests

```bash
php artisan test
```

### Code Quality

```bash
# Run PHP CS Fixer (if configured)
./vendor/bin/php-cs-fixer fix

# Run PHPStan (if configured)
./vendor/bin/phpstan analyse
```

### Database

```bash
# Create a new migration
php artisan make:migration create_table_name

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Seed database
php artisan db:seed
```

## Project Structure

```
Internly/
├── app/
│   ├── Http/Controllers/     # Application controllers
│   ├── Models/              # Eloquent models
│   ├── Policies/            # Authorization policies
│   └── Notifications/       # Notification classes
├── database/
│   ├── migrations/          # Database migrations
│   ├── seeders/            # Database seeders
│   └── factories/          # Model factories
├── resources/
│   ├── js/
│   │   ├── Pages/          # Vue page components
│   │   ├── Components/     # Reusable Vue components
│   │   └── Layouts/        # Page layouts
│   └── css/               # Stylesheets
├── routes/
│   └── web.php            # Web routes
└── tests/                 # Application tests
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For support and questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation in the `.cursor/rules/` directory

---

**Built with ❤️ for the Tunisian internship ecosystem**

## Demo

[Open the demo](https://internly.alabaganne.com).

![Internly screenshot](https://raw.githubusercontent.com/alabaganne/Portfolio/main/public/projects/internly-demo.png)
