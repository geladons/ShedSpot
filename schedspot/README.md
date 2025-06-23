# SchedSpot WordPress Plugin

A comprehensive WordPress service booking and marketplace plugin that combines appointment scheduling with a multi-vendor marketplace.

## Version 2.0+ (Full Production Release)

### Features

- **Service Booking System**: Complete booking management with date/time selection and payment processing
- **User Roles**: Customer, Worker, and Administrator roles with specific capabilities and role-switching
- **Admin Dashboard**: Comprehensive backend management interface with role switcher
- **Frontend Shortcodes**: Easy-to-use shortcodes for booking forms, service listings, and messaging
- **Database Management**: Custom tables for efficient booking, service, and message data storage
- **Responsive Design**: Mobile-friendly interface for all components
- **Payment Integration**: Full WooCommerce integration with order management and commission tracking
- **Messaging System**: Real-time messaging between clients and workers with file attachments
- **Geolocation Services**: Location-based service matching with Google Maps integration
- **SMS Notifications**: Twilio integration for booking confirmations and notifications
- **Google Calendar Sync**: Two-way calendar synchronization for booking management
- **Advanced Settings**: Comprehensive configuration options for all features
- **Role Switching**: Admin role switching for testing different user experiences
- **Enhanced Worker Frontend**: Complete settings management from frontend dashboard
- **Bidirectional Navigation**: Seamless navigation between booking and dashboard interfaces

### Installation

1. Upload the `schedspot` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'SchedSpot' in your WordPress admin to configure settings

### Shortcodes

- `[schedspot_booking_form]` - Display the booking form with payment integration
- `[schedspot_service_list]` - Show available services with booking links
- `[schedspot_dashboard]` - User dashboard (requires login) with role-specific features
- `[schedspot_messages]` - Messaging interface for client-worker communication

### Database Tables

The plugin creates the following custom tables:
- `wp_schedspot_bookings` - Store booking information with payment tracking
- `wp_schedspot_services` - Service catalog with pricing and categories
- `wp_schedspot_worker_services` - Worker-service relationships with custom pricing
- `wp_schedspot_worker_availability` - Worker availability schedules
- `wp_schedspot_messages` - Private messaging between clients and workers
- `wp_schedspot_service_areas` - Geolocation service area definitions

### User Roles

- **SchedSpot Customer**: Can create bookings and view their booking history
- **SchedSpot Worker**: Can manage bookings, set availability, and view earnings
- **Administrator**: Full access to all plugin features and settings

### Settings

Access plugin settings via **SchedSpot > Settings** in your WordPress admin:

- **General**: Timezone, date/time formats, currency
- **Booking**: Default slot length, minimum notice, auto-approval
- **Payment**: System fees, commission rates, WooCommerce integration
- **Calendar**: Google Calendar sync configuration
- **SMS**: Twilio integration for notifications
- **Messaging**: File attachments, retention policies
- **Email**: Notification templates and sender settings
- **Geolocation**: Google Maps API and service area settings
- **Advanced**: Debug mode, caching, rate limiting, data cleanup

Additional admin features:
- **Role Switcher**: Test different user experiences without logging out
- **Analytics Dashboard**: Comprehensive booking and revenue statistics

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
