<?php
/**
 * SchedSpot Admin Services Management
 *
 * Handles all service-related admin functionality
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Admin_Services Class.
 *
 * @class SchedSpot_Admin_Services
 * @version 1.0.0
 */
class SchedSpot_Admin_Services {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize services admin functionality.
     *
     * @since 1.0.0
     */
    public function init() {
        // No hooks needed here - this class is called by SchedSpot_Admin
    }

    /**
     * Services page callback.
     *
     * @since 1.0.0
     */
    public function services_page() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
        $service_id = isset( $_GET['service_id'] ) ? absint( $_GET['service_id'] ) : 0;

        // Handle form submissions
        if ( isset( $_POST['schedspot_service_action'] ) ) {
            $this->handle_service_form_submission();
        }

        switch ( $action ) {
            case 'add':
                $this->render_add_service_form();
                break;
            case 'edit':
                $this->render_edit_service_form( $service_id );
                break;
            case 'delete':
                $this->handle_delete_service( $service_id );
                break;
            default:
                $this->render_services_list();
                break;
        }
    }

    /**
     * Render services list.
     *
     * @since 1.0.0
     */
    private function render_services_list() {
        $services = SchedSpot_Service::get_all_services();
        ?>
        <div class="wrap">
            <h1><?php _e( 'Services', 'schedspot' ); ?>
                <a href="<?php echo admin_url( 'admin.php?page=schedspot-services&action=add' ); ?>" class="page-title-action"><?php _e( 'Add New', 'schedspot' ); ?></a>
            </h1>

            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="action" id="bulk-action-selector-top">
                        <option value="-1"><?php _e( 'Bulk Actions', 'schedspot' ); ?></option>
                        <option value="delete"><?php _e( 'Delete', 'schedspot' ); ?></option>
                        <option value="activate"><?php _e( 'Activate', 'schedspot' ); ?></option>
                        <option value="deactivate"><?php _e( 'Deactivate', 'schedspot' ); ?></option>
                    </select>
                    <input type="submit" id="doaction" class="button action" value="<?php _e( 'Apply', 'schedspot' ); ?>">
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th scope="col" class="manage-column column-name"><?php _e( 'Service Name', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-category"><?php _e( 'Category', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-duration"><?php _e( 'Duration', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-price"><?php _e( 'Price', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-workers"><?php _e( 'Workers', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-status"><?php _e( 'Status', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-actions"><?php _e( 'Actions', 'schedspot' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $services ) ) : ?>
                        <tr>
                            <td colspan="8" class="no-items"><?php _e( 'No services found.', 'schedspot' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $services as $service ) : ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="service[]" value="<?php echo esc_attr( $service->id ); ?>">
                                </th>
                                <td class="column-name">
                                    <strong><?php echo esc_html( $service->name ); ?></strong>
                                    <?php if ( $service->description ) : ?>
                                        <br><small><?php echo esc_html( wp_trim_words( $service->description, 10 ) ); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="column-category">
                                    <?php echo esc_html( $service->category ?: __( 'Uncategorized', 'schedspot' ) ); ?>
                                </td>
                                <td class="column-duration">
                                    <?php echo esc_html( $service->duration . ' ' . __( 'minutes', 'schedspot' ) ); ?>
                                </td>
                                <td class="column-price">
                                    <?php if ( $service->price_type === 'fixed' ) : ?>
                                        <?php echo esc_html( '$' . number_format( $service->price, 2 ) ); ?>
                                    <?php else : ?>
                                        <?php echo esc_html( '$' . number_format( $service->price, 2 ) . '/hr' ); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="column-workers">
                                    <?php echo esc_html( $service->get_worker_count() ); ?>
                                </td>
                                <td class="column-status">
                                    <span class="status-badge status-<?php echo esc_attr( $service->status ); ?>">
                                        <?php echo esc_html( ucfirst( $service->status ) ); ?>
                                    </span>
                                </td>
                                <td class="column-actions">
                                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-services&action=edit&service_id=' . $service->id ); ?>" class="button button-small"><?php _e( 'Edit', 'schedspot' ); ?></a>
                                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=schedspot-services&action=delete&service_id=' . $service->id ), 'delete_service_' . $service->id ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php _e( 'Are you sure you want to delete this service?', 'schedspot' ); ?>')"><?php _e( 'Delete', 'schedspot' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .status-draft { background: #fff3cd; color: #856404; }
        </style>
        <?php
    }

    /**
     * Render add service form.
     *
     * @since 1.0.0
     */
    private function render_add_service_form() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Add New Service', 'schedspot' ); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field( 'schedspot_service_action' ); ?>
                <input type="hidden" name="schedspot_service_action" value="create">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="service_name"><?php _e( 'Service Name', 'schedspot' ); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="service_name" name="service_name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_description"><?php _e( 'Description', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <textarea id="service_description" name="service_description" rows="4" cols="50" class="large-text"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_category"><?php _e( 'Category', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="service_category" name="service_category" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_duration"><?php _e( 'Duration (minutes)', 'schedspot' ); ?> *</label>
                        </th>
                        <td>
                            <input type="number" id="service_duration" name="service_duration" min="15" step="15" value="60" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_price_type"><?php _e( 'Price Type', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <select id="service_price_type" name="service_price_type">
                                <option value="fixed"><?php _e( 'Fixed Price', 'schedspot' ); ?></option>
                                <option value="hourly"><?php _e( 'Hourly Rate', 'schedspot' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_price"><?php _e( 'Price', 'schedspot' ); ?> *</label>
                        </th>
                        <td>
                            <input type="number" id="service_price" name="service_price" min="0" step="0.01" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_status"><?php _e( 'Status', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <select id="service_status" name="service_status">
                                <option value="active"><?php _e( 'Active', 'schedspot' ); ?></option>
                                <option value="inactive"><?php _e( 'Inactive', 'schedspot' ); ?></option>
                                <option value="draft"><?php _e( 'Draft', 'schedspot' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_requirements"><?php _e( 'Requirements', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <textarea id="service_requirements" name="service_requirements" rows="3" cols="50" class="large-text" placeholder="<?php _e( 'Any special requirements or skills needed for this service...', 'schedspot' ); ?>"></textarea>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="<?php _e( 'Add Service', 'schedspot' ); ?>">
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-services' ); ?>" class="button"><?php _e( 'Cancel', 'schedspot' ); ?></a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render edit service form.
     *
     * @since 1.0.0
     * @param int $service_id Service ID.
     */
    private function render_edit_service_form( $service_id ) {
        $service = new SchedSpot_Service( $service_id );
        
        if ( ! $service->id ) {
            wp_die( __( 'Service not found.', 'schedspot' ) );
        }
        ?>
        <div class="wrap">
            <h1><?php printf( __( 'Edit Service: %s', 'schedspot' ), esc_html( $service->name ) ); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field( 'schedspot_service_action' ); ?>
                <input type="hidden" name="schedspot_service_action" value="update">
                <input type="hidden" name="service_id" value="<?php echo esc_attr( $service->id ); ?>">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="service_name"><?php _e( 'Service Name', 'schedspot' ); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="service_name" name="service_name" class="regular-text" value="<?php echo esc_attr( $service->name ); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_description"><?php _e( 'Description', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <textarea id="service_description" name="service_description" rows="4" cols="50" class="large-text"><?php echo esc_textarea( $service->description ); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_category"><?php _e( 'Category', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="service_category" name="service_category" class="regular-text" value="<?php echo esc_attr( $service->category ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_duration"><?php _e( 'Duration (minutes)', 'schedspot' ); ?> *</label>
                        </th>
                        <td>
                            <input type="number" id="service_duration" name="service_duration" min="15" step="15" value="<?php echo esc_attr( $service->duration ); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_price_type"><?php _e( 'Price Type', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <select id="service_price_type" name="service_price_type">
                                <option value="fixed" <?php selected( $service->price_type, 'fixed' ); ?>><?php _e( 'Fixed Price', 'schedspot' ); ?></option>
                                <option value="hourly" <?php selected( $service->price_type, 'hourly' ); ?>><?php _e( 'Hourly Rate', 'schedspot' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_price"><?php _e( 'Price', 'schedspot' ); ?> *</label>
                        </th>
                        <td>
                            <input type="number" id="service_price" name="service_price" min="0" step="0.01" value="<?php echo esc_attr( $service->price ); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_status"><?php _e( 'Status', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <select id="service_status" name="service_status">
                                <option value="active" <?php selected( $service->status, 'active' ); ?>><?php _e( 'Active', 'schedspot' ); ?></option>
                                <option value="inactive" <?php selected( $service->status, 'inactive' ); ?>><?php _e( 'Inactive', 'schedspot' ); ?></option>
                                <option value="draft" <?php selected( $service->status, 'draft' ); ?>><?php _e( 'Draft', 'schedspot' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_requirements"><?php _e( 'Requirements', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <textarea id="service_requirements" name="service_requirements" rows="3" cols="50" class="large-text" placeholder="<?php _e( 'Any special requirements or skills needed for this service...', 'schedspot' ); ?>"><?php echo esc_textarea( $service->requirements ); ?></textarea>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="<?php _e( 'Update Service', 'schedspot' ); ?>">
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-services' ); ?>" class="button"><?php _e( 'Cancel', 'schedspot' ); ?></a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Handle service form submission.
     *
     * @since 1.0.0
     */
    private function handle_service_form_submission() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'schedspot_service_action' ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        $action = sanitize_text_field( $_POST['schedspot_service_action'] );

        switch ( $action ) {
            case 'create':
                $this->create_service();
                break;
            case 'update':
                $this->update_service();
                break;
        }
    }

    /**
     * Create new service.
     *
     * @since 1.0.0
     */
    private function create_service() {
        $service_data = array(
            'name' => sanitize_text_field( $_POST['service_name'] ),
            'description' => sanitize_textarea_field( $_POST['service_description'] ),
            'category' => sanitize_text_field( $_POST['service_category'] ),
            'duration' => absint( $_POST['service_duration'] ),
            'price_type' => sanitize_text_field( $_POST['service_price_type'] ),
            'price' => floatval( $_POST['service_price'] ),
            'status' => sanitize_text_field( $_POST['service_status'] ),
            'requirements' => sanitize_textarea_field( $_POST['service_requirements'] ),
        );

        $service = new SchedSpot_Service();
        if ( $service->create( $service_data ) ) {
            wp_redirect( admin_url( 'admin.php?page=schedspot-services&created=1' ) );
            exit;
        } else {
            wp_redirect( admin_url( 'admin.php?page=schedspot-services&action=add&error=1' ) );
            exit;
        }
    }

    /**
     * Update service.
     *
     * @since 1.0.0
     */
    private function update_service() {
        $service_id = absint( $_POST['service_id'] );
        $service = new SchedSpot_Service( $service_id );
        
        if ( ! $service->id ) {
            wp_die( __( 'Service not found.', 'schedspot' ) );
        }

        $service_data = array(
            'name' => sanitize_text_field( $_POST['service_name'] ),
            'description' => sanitize_textarea_field( $_POST['service_description'] ),
            'category' => sanitize_text_field( $_POST['service_category'] ),
            'duration' => absint( $_POST['service_duration'] ),
            'price_type' => sanitize_text_field( $_POST['service_price_type'] ),
            'price' => floatval( $_POST['service_price'] ),
            'status' => sanitize_text_field( $_POST['service_status'] ),
            'requirements' => sanitize_textarea_field( $_POST['service_requirements'] ),
        );

        if ( $service->update( $service_data ) ) {
            wp_redirect( admin_url( 'admin.php?page=schedspot-services&updated=1' ) );
            exit;
        } else {
            wp_redirect( admin_url( 'admin.php?page=schedspot-services&action=edit&service_id=' . $service_id . '&error=1' ) );
            exit;
        }
    }

    /**
     * Handle delete service action.
     *
     * @since 1.0.0
     * @param int $service_id Service ID.
     */
    private function handle_delete_service( $service_id ) {
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'delete_service_' . $service_id ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        $service = new SchedSpot_Service( $service_id );
        
        if ( ! $service->id ) {
            wp_die( __( 'Service not found.', 'schedspot' ) );
        }

        if ( $service->delete() ) {
            wp_redirect( admin_url( 'admin.php?page=schedspot-services&deleted=1' ) );
            exit;
        } else {
            wp_redirect( admin_url( 'admin.php?page=schedspot-services&error=1' ) );
            exit;
        }
    }
}
