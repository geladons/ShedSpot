# SchedSpot Developer Documentation

## Plugin Overview

SchedSpot is a comprehensive WordPress service booking and marketplace plugin that combines appointment scheduling with a multi-vendor marketplace (Amelia + TaskRabbit style). Version 1.7.5.

## Architecture & Conventions

### Language & Standards
- **Backend**: PHP following WordPress Coding Standards (PSR-4, WP CS)
- **Frontend**: JavaScript/React with WordPress components
- **API**: REST API with namespace `/wp-json/schedspot/v1/`
- **Prefix**: All PHP classes/functions/hooks prefixed with "SchedSpot_" or "ss_"
- **File naming**: Lowercase with hyphens (e.g., `class-schedspot-booking.php`)

### Directory Structure
```
schedspot/
├── admin/                  # Admin interface components
├── assets/                 # CSS/JS assets
├── includes/               # Core functionality
│   ├── api/               # REST API endpoints
│   ├── integrations/      # External service integrations
│   ├── messaging/         # Messaging system
│   ├── models/            # Data models
│   └── shortcodes/        # Frontend shortcodes
├── public/                # Public-facing components
├── templates/             # Template files
└── schedspot.php          # Main plugin file
```

## Core Classes

### Main Plugin Class
- **File**: `schedspot.php`
- **Class**: `SchedSpot_Core`
- **Purpose**: Main plugin initialization and component management
- **Key Methods**:
  - `instance()`: Singleton pattern implementation
  - `includes()`: Load required files
  - `init_components()`: Initialize all plugin components
  - `get_effective_user_role()`: Handle role switching for admins

### Installation & Setup
- **File**: `includes/class-schedspot-install.php`
- **Class**: `SchedSpot_Install`
- **Purpose**: Plugin installation, database setup, and initial configuration
- **Key Methods**:
  - `install()`: Main installation routine
  - `create_tables()`: Create custom database tables
  - `create_roles()`: Set up custom user roles and capabilities
  - `create_pages()`: Create default pages with shortcodes

### Database Schema

#### Custom Tables
1. **schedspot_bookings**: Core booking data
2. **schedspot_services**: Available services
3. **schedspot_worker_services**: Worker-service relationships
4. **schedspot_worker_availability**: Worker schedule data
5. **schedspot_payments**: Payment transaction records
6. **schedspot_service_areas**: Geofencing data
7. **schedspot_messages**: Internal messaging system

## Models

### SchedSpot_Booking
- **File**: `includes/models/class-schedspot-booking.php`
- **Purpose**: Booking data management and operations
- **Key Methods**:
  - `create_booking($data)`: Create new booking with validation
  - `get_bookings($args)`: Retrieve bookings with filtering
  - `check_booking_conflict()`: Prevent double-booking
  - `update($data)`: Update booking with status tracking

### SchedSpot_Service
- **File**: `includes/models/class-schedspot-service.php`
- **Purpose**: Service management and worker assignment
- **Key Methods**:
  - `create_service($data)`: Create new service
  - `get_services($args)`: Retrieve services with filtering
  - `assign_worker($worker_id, $custom_price)`: Assign worker to service
  - `get_workers()`: Get workers assigned to service

### SchedSpot_Worker
- **File**: `includes/models/class-schedspot-worker.php`
- **Purpose**: Worker profile and data management
- **Key Methods**:
  - `update_profile($data)`: Update worker profile with validation
  - `get_services()`: Get services offered by worker
  - `get_statistics()`: Calculate worker performance metrics
  - `update_profile_completion()`: Track profile completion percentage

## REST API Endpoints

### Namespace: `/wp-json/schedspot/v1/`

#### Bookings
- `GET /bookings` - List bookings with filtering
- `POST /bookings` - Create new booking
- `GET /bookings/{id}` - Get specific booking
- `PUT /bookings/{id}` - Update booking
- `DELETE /bookings/{id}` - Delete booking
- `POST /availability/check` - Check worker availability

#### Services
- `GET /services` - List all services
- `POST /services` - Create new service (admin only)
- `GET /services/{id}` - Get specific service
- `PUT /services/{id}` - Update service (admin only)
- `DELETE /services/{id}` - Delete service (admin only)

#### Workers
- `GET /workers` - List workers
- `GET /workers/{id}` - Get worker details
- `GET /workers/{id}/availability` - Get worker availability
- `PUT /workers/{id}/availability` - Update worker availability
- `GET /workers/{id}/profile` - Get worker profile
- `PUT /workers/{id}/profile` - Update worker profile
- `GET /workers/{id}/services` - Get worker services
- `PUT /workers/{id}/services` - Update worker services

#### Payments
- `POST /payments/create-order` - Create payment order
- `POST /payments/process` - Process payment
- `GET /payments/orders/{id}` - Get payment order
- `PUT /payments/orders/{id}/status` - Update payment status

#### Messaging
- `GET /messages` - List messages
- `POST /messages` - Send new message
- `GET /messages/{id}` - Get specific message
- `PUT /messages/{id}` - Update message (mark as read)
- `GET /conversations/{user_id}` - Get conversation with user

## Shortcodes

### Core Shortcodes
- `[schedspot_booking_form]` - Service booking form
- `[schedspot_dashboard]` - User dashboard (role-based)
- `[schedspot_messages]` - Messaging interface
- `[schedspot_profile]` - Profile management
- `[schedspot_services]` - Services listing
- `[schedspot_workers_grid]` - Workers grid display

### Shortcode Attributes
Each shortcode supports various attributes for customization:
- **booking_form**: `service_id`, `worker_id`, `show_workers`, `style`
- **dashboard**: `view` (default view to display)
- **services**: `limit`, `category`, `columns`
- **workers_grid**: `limit`, `service_id`

## Admin Interface

### Menu Structure
- **SchedSpot** (main menu)
  - Dashboard - Overview and quick stats
  - Bookings - Booking management
  - Services - Service management
  - Workers - Worker management
  - Settings - Plugin configuration
  - Role Switcher - Admin role switching for testing

### Admin Classes
- `SchedSpot_Admin_Core` - Main admin functionality
- `SchedSpot_Admin_Bookings` - Booking management interface
- `SchedSpot_Admin_Services` - Service management interface
- `SchedSpot_Admin_Workers` - Worker management interface
- `SchedSpot_Admin_Settings` - Settings management
- `SchedSpot_Admin_Analytics` - Analytics and reporting

## Integrations

### Geolocation (SchedSpot_Geolocation)
- **File**: `includes/integrations/class-schedspot-geolocation.php`
- **Purpose**: Location-based worker filtering and service areas
- **Features**:
  - Distance calculation using Haversine formula
  - Circular and polygon service areas
  - Google Maps API integration
  - Worker filtering by location

### Google Calendar (SchedSpot_GCal)
- **File**: `includes/integrations/class-schedspot-gcalendar.php`
- **Purpose**: Two-way calendar synchronization
- **Features**: Sync bookings to Google Calendar

### SMS Integration (SchedSpot_SMS)
- **File**: `includes/integrations/class-schedspot-sms.php`
- **Purpose**: SMS notifications for bookings
- **Features**: Send SMS confirmations and reminders

### WooCommerce (SchedSpot_WooCommerce)
- **File**: `includes/integrations/class-schedspot-woocommerce.php`
- **Purpose**: E-commerce integration for payments
- **Features**: Product creation and order management

## User Roles & Capabilities

### Custom Roles
1. **schedspot_customer**
   - `schedspot_create_booking`
   - `schedspot_view_own_bookings`
   - `schedspot_cancel_own_booking`
   - `schedspot_send_messages`
   - `schedspot_read_messages`

2. **schedspot_worker**
   - `schedspot_manage_bookings`
   - `schedspot_view_own_bookings`
   - `schedspot_accept_booking`
   - `schedspot_decline_booking`
   - `schedspot_complete_booking`
   - `schedspot_manage_availability`
   - `schedspot_manage_services`
   - `schedspot_send_messages`
   - `schedspot_read_messages`

### Administrator Capabilities
- `schedspot_manage_all_bookings`
- `schedspot_manage_workers`
- `schedspot_manage_customers`
- `schedspot_manage_services`
- `schedspot_view_analytics`
- `schedspot_manage_settings`

## Hooks & Filters

### Action Hooks
- `schedspot_init` - Plugin initialization complete
- `schedspot_installed` - Plugin installation complete
- `schedspot_booking_created` - New booking created
- `schedspot_booking_status_changed` - Booking status updated
- `schedspot_worker_profile_updated` - Worker profile updated
- `schedspot_service_created` - New service created

### Filter Hooks
- `schedspot_booking_statuses` - Modify available booking statuses
- `schedspot_service_categories` - Modify service categories
- `schedspot_available_workers` - Filter workers by criteria
- `schedspot_booking_validation` - Add custom booking validation

## Frontend Assets

### CSS Files
- `frontend-enhanced.css` - Main frontend styles
- `booking-form.css` - Booking form styles
- `booking-wizard.css` - Booking wizard styles
- `dashboard.css` - Dashboard styles
- `messaging.css` - Messaging interface styles
- `profile.css` - Profile management styles

### JavaScript Files
- `frontend.js` - Main frontend functionality
- `booking-form.js` - Booking form interactions
- `booking-wizard.js` - Multi-step booking wizard
- `dashboard.js` - Dashboard functionality
- `messaging.js` - Real-time messaging
- `profile.js` - Profile management

## Development Guidelines

### Code Standards
1. Follow WordPress Coding Standards
2. Use proper PHPDoc documentation
3. Implement proper error handling with WP_Error
4. Sanitize all input data
5. Use prepared statements for database queries
6. Implement proper nonce verification for forms

### Security Considerations
1. Capability checks for all admin functions
2. Nonce verification for AJAX requests
3. Input sanitization and validation
4. Output escaping for display
5. Role-based access control

### Testing
1. Write PHPUnit tests for core classes
2. Test REST endpoints with proper permissions
3. Validate role-based access control
4. Test geofencing functionality
5. Verify payment processing

## Configuration Options

### Plugin Settings
- `schedspot_enable_geofencing` - Enable location-based filtering
- `schedspot_google_maps_api_key` - Google Maps API key
- `schedspot_enable_messaging` - Enable internal messaging
- `schedspot_auto_approve_bookings` - Auto-approve new bookings
- `schedspot_commission_rate` - Platform commission percentage
- `schedspot_minimum_notice` - Minimum booking notice period

### Database Options
All plugin options are prefixed with `schedspot_` and stored in WordPress options table.

## Troubleshooting

### Common Issues
1. **Database tables not created**: Check `dbDelta()` requirements
2. **REST API not working**: Verify permalink structure
3. **Geolocation not working**: Check Google Maps API key
4. **Role switching issues**: Verify user capabilities

### Debug Information
The admin dashboard includes a debug panel showing:
- Plugin version and status
- Database table status
- API endpoint availability
- Integration status
- Recent error logs

## Future Development

### Extensibility
The plugin is designed to be modular and extensible:
1. New integrations can be added to `/integrations/` folder
2. Custom shortcodes can extend the core shortcode system
3. Additional payment gateways can be integrated
4. Custom booking fields can be added via hooks

### Performance Considerations
1. Database queries are optimized with proper indexing
2. Assets are conditionally loaded based on shortcode presence
3. Caching is implemented for frequently accessed data
4. AJAX is used for dynamic content updates

This documentation provides a comprehensive overview of the SchedSpot plugin architecture and implementation details for developers working on the project.