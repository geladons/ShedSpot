# SchedSpot - WordPress Service Booking & Marketplace Plugin

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.7.0-orange.svg)](https://github.com/schedspot/schedspot)

SchedSpot is a comprehensive WordPress plugin that combines appointment scheduling with a multi-vendor marketplace, inspired by Amelia and TaskRabbit. It enables businesses to create a complete service booking platform with worker management, payment processing, and customer communication.

## 🚀 Features

### Core Functionality
- **Service Booking System** - Complete appointment scheduling with time slot management
- **Multi-Vendor Marketplace** - Support for multiple service providers (workers)
- **User Role Management** - Customer, Worker, and Admin roles with specific capabilities
- **Payment Processing** - WooCommerce integration with deposit and final payment options
- **Real-time Messaging** - Private communication between customers and workers
- **Calendar Integration** - Google Calendar sync with OAuth 2.0 authentication

### Advanced Features
- **Geofencing** - Location-based service restrictions and distance calculations
- **SMS Notifications** - Twilio integration for booking confirmations and reminders
- **REST API** - Complete API for external integrations and mobile apps
- **Role Switching** - Admin role switching for testing different user experiences
- **Responsive Design** - Mobile-first design optimized for all devices
- **Modern UI/UX** - Professional interface with card-based layouts and animations

## 📋 Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.6 or higher
- **WooCommerce:** 4.0+ (for payment processing)

### Optional Dependencies
- **Google Maps API** (for geofencing features)
- **Twilio Account** (for SMS notifications)
- **Google Calendar API** (for calendar sync)

## 🔧 Installation

### Automatic Installation
1. Download the plugin zip file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the zip file
4. Click "Install Now" and then "Activate"

### Manual Installation
1. Upload the `schedspot` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin panel
3. Go to SchedSpot → Settings to configure the plugin

### Database Setup
The plugin automatically creates the required database tables on activation:
- `wp_schedspot_bookings` - Stores booking information
- `wp_schedspot_services` - Service definitions and pricing
- `wp_schedspot_worker_services` - Worker-service assignments
- `wp_schedspot_worker_availability` - Worker schedule management
- `wp_schedspot_messages` - Internal messaging system
- `wp_schedspot_service_areas` - Geofencing data

## 📖 Usage Guide

### Shortcodes

#### Booking Form
```php
[schedspot_booking_form]
```
Displays the main booking form with service selection, worker choice, and scheduling.

**Attributes:**
- `service_id` - Pre-select a specific service
- `worker_id` - Pre-select a specific worker
- `style` - Form style (default, compact, wizard)

#### Service Listing
```php
[schedspot_services]
```
Shows available services with descriptions and pricing.

**Attributes:**
- `category` - Filter by service category
- `limit` - Number of services to display
- `columns` - Grid columns (1-4)

#### User Dashboard
```php
[schedspot_dashboard]
```
Displays role-specific dashboard (Customer, Worker, or Admin view).

#### Worker Grid
```php
[schedspot_workers]
```
Shows available workers with profiles and ratings.

**Attributes:**
- `service_id` - Show workers for specific service
- `limit` - Number of workers to display
- `layout` - Display layout (grid, list)

#### Messages Interface
```php
[schedspot_messages]
```
Private messaging interface between customers and workers.

#### User Profile
```php
[schedspot_profile]
```
User profile management with settings and preferences.

### Page Setup
Create the following pages with their respective shortcodes:
- **Book Service** - `[schedspot_booking_form]`
- **Services** - `[schedspot_services]`
- **Dashboard** - `[schedspot_dashboard]`
- **Messages** - `[schedspot_messages]`
- **Profile** - `[schedspot_profile]`
- **Workers** - `[schedspot_workers]`

## 🔌 API Documentation

### REST API Endpoints

Base URL: `/wp-json/schedspot/v1/`

#### Bookings
- `GET /bookings` - List bookings
- `POST /bookings` - Create new booking
- `GET /bookings/{id}` - Get specific booking
- `PUT /bookings/{id}` - Update booking
- `DELETE /bookings/{id}` - Delete booking

#### Services
- `GET /services` - List all services
- `POST /services` - Create new service
- `GET /services/{id}` - Get specific service
- `PUT /services/{id}` - Update service
- `DELETE /services/{id}` - Delete service

#### Workers
- `GET /workers` - List workers
- `GET /workers/{id}` - Get worker profile
- `GET /workers/{id}/availability` - Get worker availability
- `PUT /workers/{id}/availability` - Update availability
- `GET /workers/{id}/services` - Get worker services
- `PUT /workers/{id}/services` - Update worker services

#### Messages
- `GET /messages` - List conversations
- `POST /messages` - Send message
- `GET /messages/{id}` - Get conversation
- `PUT /messages/{id}/read` - Mark as read

#### Availability
- `GET /availability` - Check availability
- `POST /availability/check` - Validate time slot

### Authentication
All API endpoints use WordPress REST API authentication:
- **Nonce-based** for same-origin requests
- **Application Passwords** for external applications
- **OAuth 2.0** for third-party integrations

### Response Format
```json
{
  "success": true,
  "data": {
    // Response data
  },
  "message": "Success message"
}
```

### Error Handling
```json
{
  "success": false,
  "error": {
    "code": "error_code",
    "message": "Error description"
  }
}
```

## 🛠️ Development Setup

### Prerequisites
- Node.js 14+ and npm
- Composer
- WordPress development environment
- Git

### Local Development
1. Clone the repository:
```bash
git clone https://github.com/schedspot/schedspot.git
cd schedspot
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Build assets:
```bash
npm run build
```

4. Watch for changes during development:
```bash
npm run dev
```

### File Structure
```
schedspot/
├── admin/                 # Admin interface classes
├── assets/               # CSS, JS, and image files
├── includes/             # Core plugin classes
│   ├── api/             # REST API endpoints
│   ├── integrations/    # Third-party integrations
│   ├── messaging/       # Messaging system
│   ├── models/          # Data models
│   └── shortcodes/      # Shortcode handlers
├── public/              # Frontend functionality
├── templates/           # Template files
│   ├── admin/          # Admin templates
│   ├── frontend/       # Frontend templates
│   └── shortcodes/     # Shortcode templates
└── schedspot.php       # Main plugin file
```

### Coding Standards
- Follow WordPress Coding Standards
- Use `SchedSpot_` prefix for all classes
- Use `schedspot_` prefix for functions and hooks
- Document all functions with PHPDoc
- Use meaningful variable and function names

### Testing
Run the debug test page to verify functionality:
```
/wp-content/plugins/schedspot/debug-test.php
```

This page checks:
- Database tables and data
- User roles and permissions
- REST API endpoints
- Asset loading
- WordPress integration

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

- **Documentation:** [Plugin Documentation](https://schedspot.com/docs)
- **Issues:** [GitHub Issues](https://github.com/schedspot/schedspot/issues)
- **Support Forum:** [WordPress.org Support](https://wordpress.org/support/plugin/schedspot)
- **Email:** support@schedspot.com

## 🏆 Credits

SchedSpot is developed and maintained by the SchedSpot Team. Special thanks to all contributors who have helped make this plugin better.

---

**Made with ❤️ for the WordPress community**
