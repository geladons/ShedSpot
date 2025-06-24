# SchedSpot - WordPress Service Booking & Marketplace Plugin

[![WordPress Plugin Version](https://img.shields.io/badge/WordPress-1.6.1-blue.svg)](https://wordpress.org/plugins/schedspot)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Tested up to](https://img.shields.io/badge/WordPress-6.3-blue.svg)](https://wordpress.org)

> **A comprehensive service booking and marketplace plugin for WordPress that connects customers with service providers for on-demand bookings.**

SchedSpot transforms your WordPress site into a powerful service marketplace, enabling customers to easily book services while providing workers with professional tools to manage their business.

## 🚀 Features

### **Core Booking System**
- **📅 Advanced Scheduling** - Flexible appointment booking with time slot management
- **👥 Worker Selection** - Auto-assign or manual worker selection with availability checking
- **🎯 Service Management** - Comprehensive service catalog with categories and pricing
- **💰 Payment Integration** - Secure payments via WooCommerce with deposit options
- **📱 Mobile Responsive** - Optimized for all devices and screen sizes

### **User Management**
- **🔐 Multi-Role Support** - Customer, Worker, and Admin roles with specific capabilities
- **👤 Profile Management** - Detailed user profiles with skills, ratings, and portfolios
- **⭐ Rating System** - Customer reviews and worker rating management
- **📊 Dashboard Interface** - Role-based dashboards with real-time data

### **Communication & Notifications**
- **💬 Real-time Messaging** - Built-in messaging system between customers and workers
- **📧 Email Notifications** - Automated booking confirmations and reminders
- **📱 SMS Integration** - Twilio-powered SMS notifications and alerts
- **🔔 Push Notifications** - Real-time updates for booking status changes

### **Advanced Features**
- **🗺️ Geolocation Services** - Location-based matching with Google Maps integration
- **📅 Calendar Sync** - Google Calendar integration for workers
- **📈 Analytics Dashboard** - Comprehensive reporting and business insights
- **🔌 REST API** - Complete API for external integrations and mobile apps
- **🌐 Multi-language Ready** - Translation-ready with internationalization support

## 📦 Installation

### **Automatic Installation**
1. Log in to your WordPress admin panel
2. Navigate to **Plugins > Add New**
3. Search for "SchedSpot"
4. Click **Install Now** and then **Activate**

### **Manual Installation**
1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/schedspot/`
3. Activate the plugin through the WordPress admin panel

### **Requirements**
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- WooCommerce (for payment processing)

## 🛠️ Quick Setup

### **1. Initial Configuration**
```php
// Navigate to SchedSpot > Settings in your WordPress admin
// Configure basic settings:
- Default timezone and date formats
- Payment methods and commission rates
- Email and SMS notification settings
```

### **2. Create Your First Service**
```php
// Go to SchedSpot > Services > Add New
- Service name and description
- Category and pricing structure
- Duration and requirements
- Worker assignments
```

### **3. Add Workers**
```php
// Navigate to SchedSpot > Workers > Add New
- Create worker accounts
- Set up profiles and skills
- Configure availability schedules
- Assign services and pricing
```

### **4. Display Booking Form**
```php
// Add to any page or post:
[schedspot_booking_form]

// With specific service:
[schedspot_booking_form service_id="123"]

// With worker selection:
[schedspot_booking_form show_worker_selection="true"]
```

## 📋 Shortcodes

### **Booking Form**
```php
[schedspot_booking_form service_id="123" worker_id="456" show_payment_info="true"]
```

### **Service List**
```php
[schedspot_service_list layout="grid" columns="3" show_filters="true" category="cleaning"]
```

### **User Dashboard**
```php
[schedspot_dashboard view="auto" show_navigation="true"]
```

### **Messaging Interface**
```php
[schedspot_messages conversation_id="789" height="600px"]
```

### **Profile Management**
```php
[schedspot_profile show_worker_fields="auto" default_tab="general"]
```

## 🔧 Configuration

### **Payment Settings**
```php
// WooCommerce Integration
- Enable payment processing
- Set commission rates
- Configure deposit requirements
- Manage payout schedules
```

### **Notification Settings**
```php
// Email Configuration
- SMTP settings
- Template customization
- Automated reminders

// SMS Configuration (Twilio)
- Account SID and Auth Token
- Phone number configuration
- Message templates
```

### **Geolocation Setup**
```php
// Google Maps Integration
- API key configuration
- Default service radius
- Distance unit settings
- Map styling options
```

## 🏗️ Architecture

### **Modular Design**
SchedSpot follows a clean, modular architecture with separated concerns:

```
schedspot/
├── admin/                     # Admin interface classes
│   ├── class-schedspot-admin.php
│   ├── class-schedspot-admin-bookings.php
│   ├── class-schedspot-admin-services.php
│   ├── class-schedspot-admin-workers.php
│   ├── class-schedspot-admin-settings.php
│   └── class-schedspot-admin-analytics.php
├── includes/
│   ├── models/               # Data models
│   ├── shortcodes/          # Shortcode classes
│   ├── api/                 # REST API endpoints
│   └── integrations/        # Third-party integrations
├── assets/
│   ├── css/                 # Modular stylesheets
│   └── js/                  # Modular JavaScript files
└── templates/               # Template files
```

### **Database Schema**
```sql
-- Custom booking table
CREATE TABLE wp_schedspot_bookings (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    service_id bigint(20) NOT NULL,
    worker_id bigint(20) NOT NULL,
    client_id bigint(20),
    booking_date date NOT NULL,
    start_time time NOT NULL,
    end_time time NOT NULL,
    status varchar(20) DEFAULT 'pending',
    total_cost decimal(10,2),
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

## 🔌 API Reference

### **REST API Endpoints**

#### **Bookings**
```php
GET    /wp-json/schedspot/v1/bookings          # List bookings
POST   /wp-json/schedspot/v1/bookings          # Create booking
GET    /wp-json/schedspot/v1/bookings/{id}     # Get booking
PUT    /wp-json/schedspot/v1/bookings/{id}     # Update booking
DELETE /wp-json/schedspot/v1/bookings/{id}     # Delete booking
```

#### **Services**
```php
GET    /wp-json/schedspot/v1/services          # List services
POST   /wp-json/schedspot/v1/services          # Create service
GET    /wp-json/schedspot/v1/services/{id}     # Get service
PUT    /wp-json/schedspot/v1/services/{id}     # Update service
```

#### **Workers**
```php
GET    /wp-json/schedspot/v1/workers           # List workers
GET    /wp-json/schedspot/v1/workers/{id}      # Get worker
PUT    /wp-json/schedspot/v1/workers/{id}      # Update worker
GET    /wp-json/schedspot/v1/workers/available # Get available workers
```

### **Hooks & Filters**

#### **Actions**
```php
do_action( 'schedspot_booking_created', $booking_id );
do_action( 'schedspot_booking_confirmed', $booking_id );
do_action( 'schedspot_booking_completed', $booking_id );
do_action( 'schedspot_worker_registered', $worker_id );
```

#### **Filters**
```php
apply_filters( 'schedspot_booking_data', $data );
apply_filters( 'schedspot_service_price', $price, $service_id );
apply_filters( 'schedspot_worker_availability', $available, $worker_id );
apply_filters( 'schedspot_email_template', $template, $type );
```

## 🧪 Testing

### **Running Tests**
```bash
# PHPUnit tests
composer test

# JavaScript tests
npm test

# Integration tests
composer test:integration
```

### **Test Coverage**
- Unit tests for all core classes
- Integration tests for API endpoints
- Frontend JavaScript testing
- Database operation testing

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

### **Development Setup**
```bash
# Clone the repository
git clone https://github.com/schedspot/schedspot-plugin.git

# Install dependencies
composer install
npm install

# Set up development environment
cp .env.example .env
```

### **Code Standards**
- Follow WordPress Coding Standards
- Use PSR-4 autoloading
- Write comprehensive PHPDoc comments
- Include unit tests for new features

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

- **Documentation**: [schedspot.com/docs](https://schedspot.com/docs)
- **Support Forum**: [WordPress.org Support](https://wordpress.org/support/plugin/schedspot)
- **GitHub Issues**: [Report Issues](https://github.com/schedspot/schedspot-plugin/issues)
- **Email**: support@schedspot.com

## 🙏 Acknowledgments

- WordPress community for excellent documentation and standards
- Contributors who have helped improve this plugin
- Beta testers who provided valuable feedback

---

**Made with ❤️ for the WordPress community**
