# SchedSpot WordPress Plugin

A comprehensive WordPress service booking and marketplace plugin that combines appointment scheduling with a multi-vendor marketplace.

## Version 0.1.0 (MVP)

### Features

- **Service Booking System**: Complete booking management with date/time selection
- **User Roles**: Customer, Worker, and Administrator roles with specific capabilities
- **Admin Dashboard**: Comprehensive backend management interface
- **Frontend Shortcodes**: Easy-to-use shortcodes for booking forms and service listings
- **Database Management**: Custom tables for efficient booking and service data storage
- **Responsive Design**: Mobile-friendly interface for all components

### Installation

1. Upload the `schedspot` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'SchedSpot' in your WordPress admin to configure settings

### Shortcodes

- `[schedspot_booking_form]` - Display the booking form
- `[schedspot_service_list]` - Show available services
- `[schedspot_dashboard]` - User dashboard (requires login)

### Database Tables

The plugin creates the following custom tables:
- `wp_schedspot_bookings` - Store booking information
- `wp_schedspot_services` - Service catalog
- `wp_schedspot_worker_services` - Worker-service relationships
- `wp_schedspot_worker_availability` - Worker availability schedules

### User Roles

- **SchedSpot Customer**: Can create bookings and view their booking history
- **SchedSpot Worker**: Can manage bookings, set availability, and view earnings
- **Administrator**: Full access to all plugin features and settings

### Settings

Access plugin settings via **SchedSpot > Settings** in your WordPress admin:

- **General**: Timezone, date/time formats, currency
- **Booking**: Default slot length, minimum notice, auto-approval
- **Payment**: System fees and commission rates

### Development

This plugin follows WordPress coding standards and uses:
- PSR-4 autoloading structure
- WordPress hooks and filters for extensibility
- Modular architecture for easy maintenance
- Comprehensive documentation

### File Structure

```
schedspot/
├── schedspot.php (main plugin file)
├── includes/
│   ├── class-schedspot-install.php
│   ├── models/
│   │   └── class-schedspot-booking.php
│   └── shortcodes/
│       └── class-schedspot-shortcodes.php
├── admin/
│   └── class-schedspot-admin.php
└── public/
    └── class-schedspot-public.php
```

### Hooks and Filters

The plugin provides numerous hooks for customization:

**Actions:**
- `schedspot_booking_created` - Fired when a new booking is created
- `schedspot_booking_updated` - Fired when a booking is updated
- `schedspot_booking_deleted` - Fired when a booking is deleted

**Filters:**
- `schedspot_booking_statuses` - Modify available booking statuses

### Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

### Support

For support and documentation, visit the plugin settings page in your WordPress admin.

### Changelog

#### Version 0.1.0
- Initial MVP release
- Core booking functionality
- Admin interface
- Frontend shortcodes
- User role management
- Basic styling and responsive design

### License

GPL v2 or later

### Credits

Developed following WordPress best practices and coding standards.
