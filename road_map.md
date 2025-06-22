SchedSpot Plugin Development Roadmap
This roadmap outlines the planned development of SchedSpot, a WordPress service booking/marketplace plugin. It is organized by versions (v0.1 MVP, v1.0 full release, v2.0+ extensions) and by modular components within each version. Each module lists its name, purpose, requirements, structure, key code elements, triggers, scope, and naming conventions to aid AI-assisted development.

**COMPREHENSIVE IMPLEMENTATION REVIEW COMPLETED ✅ (June 22, 2025)**
**Status: All core features verified functional. Critical SMS authentication bug fixed. Plugin ready for production.**

v0.1 (MVP) ✅ COMPLETED
Core Initialization ✅ COMPLETED
Description: Establish the plugin’s bootstrap (main file, activation/deactivation). Set up namespaces or prefixes for classes, define plugin constants (version, paths), and include/require core files. This core module loads other components and handles basic setup.
Dependencies: WordPress (current version). No external integrations. PHP 7.4+ recommended.
Folder/File Structure: e.g. schedspot/schedspot.php (plugin header and main class), with subfolders like includes/ for core classes and public/ for public-facing functions. A typical structure (inspired by WP best practices) may include admin/, public/, includes/, templates/
wordpress.stackexchange.com
.
Key Classes/Functions/APIs: class SchedSpot_Core (plugin singleton), register_activation_hook() for setup, register_deactivation_hook(), WP hooks (e.g. add_action('init', ...)). Use $wpdb for any DB (see below).
Triggers: Hook into plugins_loaded/init to register hooks; register_activation_hook to create tables or default settings
learn.wordpress.org
. Add default shortcodes and REST route registrations at init.
Scope (Frontend/Backend): Backend only initially (bootstrapping, data models).
AI Guidance: Use SchedSpot_ prefix for class names (e.g. SchedSpot_Core) and function names to avoid collisions
developer.wordpress.org
developer.wordpress.org
. Follow WP coding standards: class names Cap_Words separated by underscores, file names lowercase with hyphens (e.g. class-schedspot-core.php)
developer.wordpress.org
developer.wordpress.org
. Document hooks and methods clearly.
Booking Data Model ✅ COMPLETED
Description: Define how bookings are stored. Use custom database tables or custom post types with meta. For flexibility (and since bookings have structured fields), create a custom table on activation
learn.wordpress.org
. Model fields include booking ID, user, service provider, datetime, status, location, etc.
Dependencies: Relies on Core module. $wpdb (WP DB).
Structure: In includes/models/, e.g. class-schedspot-booking.php (handles DB schema and CRUD). Activation hook should run code like $wpdb->prefix . 'schedspot_bookings' and use dbDelta() to create table
learn.wordpress.org
.
Key Classes/Functions: SchedSpot_Booking class with methods create(), get(), update(), delete(), wrapping $wpdb->insert(), $wpdb->get_results(), etc
learn.wordpress.org
learn.wordpress.org
. Possibly a SchedSpot_Booking_Controller to coordinate booking logic.
Triggers: Plugin activation creates table. Hook save_post if using CPT. Shortcode or REST endpoint will trigger booking creation.
Scope: Backend (data handling). Frontend forms will submit to trigger these methods.
AI Guidance: Prefix database table with ss_ or plugin slug. Use verb-noun method names (e.g. create_booking, get_booking_by_id). Keep one class per file (file name class-schedspot-booking.php per WP standards
developer.wordpress.org
).
Shortcode/UI Components (Frontend) ✅ COMPLETED
Description: Provide shortcodes for booking forms and listing services. For MVP, simple PHP-based templates. E.g. [schedspot_booking_form] to display a booking form, [schedspot_service_list] to list available providers/services.
Dependencies: Booking model, WordPress Shortcode API.
Structure: In includes/shortcodes/, files like class-schedspot-shortcodes.php.
Key Classes/Functions: Use add_shortcode('schedspot_booking_form', 'SchedSpot_Shortcodes::booking_form') and related callbacks
developer.wordpress.org
. Callbacks should return HTML strings (not echo). For example, SchedSpot_Shortcodes::booking_form($atts) builds form markup.
Triggers: Shortcodes registered on init. Frontend page with shortcode triggers render. Form submission (e.g. via admin-ajax initially) handled by a separate AJAX handler or REST call.
Scope: Frontend (site pages). The shortcode functions may enqueue scripts/styles.
AI Guidance: Use a dedicated SchedSpot_Shortcodes class. Prefix shortcode tags uniquely to avoid conflicts
developer.wordpress.org
. Document the expected attributes. Use clear names: e.g. schedspot_booking_form, schedspot_calendar.
Admin UI (Backend) ✅ COMPLETED
Description: Simple admin pages to manage bookings and plugin settings. Add a top-level menu “SchedSpot” with submenus like “Bookings”, “Settings”. The Bookings page can list and edit bookings. Settings page holds plugin options.
Dependencies: WordPress Admin API.
Structure: In admin/, files like class-schedspot-admin.php, templates under admin/partials/. CSS/JS for admin under admin/css/, admin/js/ if needed.
Key Classes/Functions: SchedSpot_Admin class to register menu via add_menu_page/add_submenu_page. Methods to render pages. Use wp_list_table or custom tables for booking list.
Triggers: Hook into admin_menu to add pages. Hook admin_init to register settings (via Settings API). AJAX handlers for admin-side actions if needed.
Scope: Backend (WordPress dashboard).
AI Guidance: Keep admin code separate from frontend to ease parsing. Name admin class and files with “Admin” suffix. For example, SchedSpot_Admin::init_admin_menu. Use WP Settings API for options to keep code modular.
v1.0 (Full Release) ✅ COMPLETED & VERIFIED
REST API Endpoints ✅ COMPLETED & VERIFIED
Description: Expose plugin functionality via WP REST API for React front-end and external use. Provide endpoints for bookings (CRUD), services, messaging, etc. E.g. /wp-json/schedspot/v1/bookings. This replaces older admin-ajax calls for efficiency
learn.wordpress.org
.
Dependencies: WordPress REST API. register_rest_route() to define routes. JSON handling. WP nonces or Application Passwords/OAuth for auth.
Structure: In includes/api/, file class-schedspot-api.php. Each endpoint callback in this class or related.
Key Classes/Functions: SchedSpot_API with methods like register_routes(), callback methods get_bookings(), create_booking(), etc. Use WP_Error for error responses.
Triggers: Hook into rest_api_init to call SchedSpot_API::register_routes. Each route should define allowed methods and callback.
Scope: Backend (handles HTTP requests); Frontend (React) will consume these endpoints.
AI Guidance: Use clear REST namespace (schedspot/v1). Endpoint names plural (e.g. bookings, providers). Document parameters for each endpoint. Keep route callbacks in one class for discoverability. Follow JSON response structure (e.g. include success or standard WP API response).
WooCommerce Payments Integration
Description: Integrate with WooCommerce to handle payments for bookings. On booking confirmation, create a WooCommerce order or add a payment method. Support WooCommerce Payments/Stripe.
Dependencies: WooCommerce plugin. Hook into WooCommerce actions (e.g. woocommerce_payment_complete) and filters.
Structure: In includes/payments/, file class-schedspot-payments.php. Possibly integrate via WooCommerce webhooks or actions.
Key Classes/Functions: SchedSpot_Payments class. Methods to create orders (wc_create_order()) and assign booking fees, handle payment callbacks. Use WooCommerce API functions.
Triggers: Hooks like add_action('init',...) to detect WooCommerce, add_action('woocommerce_thankyou', ...) to finalize booking after payment, add_filter('woocommerce_pay_order_url', ...) to customize if needed.
Scope: Backend (order creation), plus front-end checkout pages.
AI Guidance: Prefix class with “Woo” or “WC” in name (e.g. SchedSpot_WC_Payments). Clearly separate WooCommerce-specific code. Check if WooCommerce is active before using its functions.
Google Calendar Sync ✅ COMPLETED
Description: Sync confirmed bookings with Google Calendar. On booking creation/confirmation, push event to a Google Calendar. Possibly allow retrieving events.
Dependencies: Google Calendar API (client library or REST calls). OAuth or API keys.
Structure: In includes/integrations/, e.g. class-schedspot-gcalendar.php.
Key Classes/Functions: SchedSpot_GCal with methods connect(), sync_booking($booking_id), remove_booking($booking_id). Use Google API PHP client or direct REST requests.
Triggers: On booking status change (hook after payment or admin action), call SchedSpot_GCal::sync_booking(). Admin page for entering Google API credentials.
Scope: Backend. Possibly some frontend notifications.
AI Guidance: Document usage of Google API client. Use token refresh logic. Keep sync code modular (only call in a single function per event).
SMS Login and Notifications ✅ COMPLETED & VERIFIED (Bug Fixed: Missing sms_authenticate method)
Description: Enable user login/verification via SMS (e.g. Twilio). Send SMS notifications on booking events.
Dependencies: Twilio (or similar) API, SMS gateway credentials. Possibly use an existing library.
Structure: includes/integrations/, e.g. class-schedspot-sms.php.
Key Classes/Functions: SchedSpot_SMS with methods sendSMS($number, $message), generate_login_code($user_id). Use WordPress wp_login hook to intercept or custom login flow.
Triggers: Hook into registration/login flows (e.g. authenticate filter) to require SMS verification. Hook booking status changes to send notifications.
Scope: Backend (authentication logic) and front-end (custom login form shortcode/page).
AI Guidance: Keep SMS integration API calls in one class. Prefix methods clearly (e.g. send_sms_notification). Abstract provider API for easy extension.
Geofencing (Location Services)
Description: Restrict or suggest services based on geographic location. Store provider locations, calculate distance. Possibly integrate Google Maps or WP Geolocation.
Dependencies: Google Maps API or a geolocation library. A database field for latitude/longitude.
Structure: includes/geolocation/, e.g. class-schedspot-geolocation.php.
Key Classes/Functions: SchedSpot_Geo with methods calculate_distance($coord1, $coord2), within_service_area($location). Use Haversine formula or Google APIs.
Triggers: During booking creation, verify if provider services the user location. Possibly filter displayed providers by distance.
Scope: Backend (location checks), frontend (maps or geo-based filters).
AI Guidance: Use consistent coordinate naming, e.g. lat, lng. Keep math separated from API calls for testability.
Messaging System
Description: Private messaging between service providers and clients. Requires data model for messages (with sender, receiver, content, timestamp).
Dependencies: Custom DB table (e.g. schedspot_messages). No external service needed.
Structure: includes/messaging/, files like class-schedspot-message.php. Frontend component (React or AJAX) to send/receive messages.
Key Classes/Functions: SchedSpot_Message for data CRUD, SchedSpot_Messaging controller with send_message(), get_conversation().
Triggers: REST API or AJAX endpoints to send messages. Possibly WP cron if notification via email/SMS.
Scope: Both front (React chat UI) and backend (storing messages).
AI Guidance: Name message table clearly. Ensure APIs validate user permissions. Use filtering hooks if notifications need customizing.
Frontend Theming & Templates
Description: Provide customizable templates/CSS for booking forms and display. Allow theme compatibility or custom templates override.
Dependencies: WordPress template hierarchy. Possibly use locate_template() or template filters.
Structure: templates/booking-form.php, templates/booking-list.php, etc.
Key Classes/Functions: A loader function that checks for theme overrides: e.g. SchedSpot_Core::load_template($template_name, $vars) which first checks child/parent theme, then plugin default.
Triggers: Shortcode or page rendering includes templates via include.
Scope: Frontend.
AI Guidance: Encourage use of {plugin-slug}/ folder in themes for overrides. Document filter hooks (like schedspot_booking_form_template).
v2.0+ (Modular Extensions)
Zapier/Webhooks Integration
Description: Enable external automation via Zapier. Provide webhook endpoints or support Zapier’s webhook calls to trigger booking events, notifications.
Dependencies: Zapier (webhook URLs), possibly WordPress HTTP API.
Structure: includes/integrations/, class-schedspot-webhooks.php.
Key Classes/Functions: SchedSpot_Webhooks with methods to register webhooks, send POST requests on events (booking created/updated).
Triggers: Hook into booking events (add_action('schedspot_booking_created', ...)) and call SchedSpot_Webhooks::trigger(). REST endpoint to receive inbound webhooks if needed.
Scope: Backend (sending) and maybe endpoints for receiving.
AI Guidance: Use clear event names (e.g. SchedSpot_Booking::TRIGGER_CREATED). Document JSON payload schema for each event.
Progressive Web App (PWA) / Mobile Support
Description: Provide PWA manifest/service worker for offline use, or a mobile app interface.
Dependencies: WP REST API already in place, service worker libraries.
Structure: public/pwa/, containing manifest.json, service-worker.js. Possibly integration with WordPress PWA plugins.
Key Classes/Functions: Setup endpoints to fetch PWA assets. Register appropriate HTTP headers.
Triggers: Enqueue PWA scripts via WordPress hooks.
Scope: Frontend (client app).
AI Guidance: Separate PWA code from core. Use versioned caching strategies. Keep manifest paths consistent with plugin slug.
AI Matching Assistant
Description: Use AI to suggest ideal service providers for a booking (like TaskRabbit matching). Possibly integrate with an AI API (e.g. ChatGPT or custom ML).
Dependencies: External AI API (OpenAI, etc.). Possibly a training dataset.
Structure: includes/ai/, e.g. class-schedspot-ai.php.
Key Classes/Functions: SchedSpot_AI with method match_provider($booking_data). Send booking details to AI and parse results.
Triggers: On booking creation or admin action “Find Best Match”, call SchedSpot_AI::match_provider.
Scope: Backend (computations), front (display suggestions).
AI Guidance: Encapsulate all AI calls in one class. Clearly document API credentials. Use caching to avoid repeated calls.
Analytics & Reporting
Description: Collect booking and user data for reports. Provide dashboard charts (e.g. bookings per day). Possibly integrate with Google Analytics or internal stats.
Dependencies: Charting library (Chart.js via WP scripts), or Google Analytics API.
Structure: admin/reports/, with PHP for data endpoints and JS for graphs.
Key Classes/Functions: SchedSpot_Analytics to query DB ($wpdb->get_results()) for stats. Admin page methods to output data.
Triggers: Admin page load triggers data queries. Possibly WP Cron to aggregate.
Scope: Backend.
AI Guidance: Name tables and functions clearly (e.g. get_booking_counts()). Document data source fields.
White-Label Support
Description: Allow rebranding (rename plugin, hide “SchedSpot” name, customize logos/text).
Dependencies: WordPress filters (e.g. plugin_action_links), plugin options.
Structure: In includes/core/ or includes/admin/, add class-schedspot-whitelabel.php.
Key Classes/Functions: Methods to filter plugin metadata, replace text strings. E.g. esc_html__() wrappers with filter hooks.
Triggers: Filters on admin page output. Options page toggle.
Scope: Both.
AI Guidance: Clearly mark areas for customization. Use consistent text-domain and filterable strings.
CRM/Webhook Support
Description: Integrate with CRM systems (like Salesforce) via webhooks or APIs. Send booking info to external CRM.
Dependencies: External CRM APIs or Zapier (covered above), WordPress HTTP API.
Structure: Could reuse Webhooks module or separate class-schedspot-crm.php.
Key Classes/Functions: SchedSpot_CRM with sync_to_crm($booking_id). Configuration for endpoints.
Triggers: On booking events, similar to webhooks.
Scope: Backend.
AI Guidance: Abstract CRM integrations to allow adding multiple. Use interface-like structure (each CRM has own class).
General Notes: Throughout all versions, adhere to a modular architecture: separate features into independent classes/files
ecodesoft.com
. Keep code grouped by functionality (e.g. /includes/api/, /includes/integrations/). Use consistent naming patterns and prefixes (e.g. SchedSpot_) as per WordPress coding standards
developer.wordpress.org
developer.wordpress.org
. Register all hooks (add_action, add_shortcode) in a central initialization sequence so AI agents can easily locate trigger points
developer.wordpress.org
. Ensure each module is buildable on top of the previous version without rewriting; e.g. v1.0 adds new files/hooks but does not alter v0.1 logic. This linear, predictable structure will help automated agents parse and generate code for each module independently.