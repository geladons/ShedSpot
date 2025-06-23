<?php
/**
 * SchedSpot Admin Workers Management
 *
 * Handles all worker-related admin functionality
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Admin_Workers Class.
 *
 * @class SchedSpot_Admin_Workers
 * @version 1.0.0
 */
class SchedSpot_Admin_Workers {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize workers admin functionality.
     *
     * @since 1.0.0
     */
    public function init() {
        // No hooks needed here - this class is called by SchedSpot_Admin
    }

    /**
     * Workers page callback.
     *
     * @since 1.0.0
     */
    public function workers_page() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
        $worker_id = isset( $_GET['worker_id'] ) ? absint( $_GET['worker_id'] ) : 0;

        // Handle form submissions
        if ( isset( $_POST['schedspot_worker_action'] ) ) {
            $this->handle_worker_form_submission();
        }

        switch ( $action ) {
            case 'add':
                $this->render_add_worker_form();
                break;
            case 'edit':
                $this->render_edit_worker_form( $worker_id );
                break;
            case 'view':
                $this->render_worker_profile( $worker_id );
                break;
            case 'availability':
                $this->render_worker_availability( $worker_id );
                break;
            case 'delete':
                $this->handle_delete_worker( $worker_id );
                break;
            default:
                $this->render_workers_list();
                break;
        }
    }

    /**
     * Render workers list.
     *
     * @since 1.0.0
     */
    private function render_workers_list() {
        $workers = get_users( array( 'role' => 'schedspot_worker', 'number' => 50 ) );
        ?>
        <div class="wrap">
            <h1><?php _e( 'Workers', 'schedspot' ); ?>
                <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=add' ); ?>" class="page-title-action"><?php _e( 'Add New', 'schedspot' ); ?></a>
            </h1>

            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="action" id="bulk-action-selector-top">
                        <option value="-1"><?php _e( 'Bulk Actions', 'schedspot' ); ?></option>
                        <option value="activate"><?php _e( 'Activate', 'schedspot' ); ?></option>
                        <option value="deactivate"><?php _e( 'Deactivate', 'schedspot' ); ?></option>
                        <option value="delete"><?php _e( 'Delete', 'schedspot' ); ?></option>
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
                        <th scope="col" class="manage-column column-avatar"><?php _e( 'Avatar', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-name"><?php _e( 'Name', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-email"><?php _e( 'Email', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-phone"><?php _e( 'Phone', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-services"><?php _e( 'Services', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-rating"><?php _e( 'Rating', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-status"><?php _e( 'Status', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-actions"><?php _e( 'Actions', 'schedspot' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $workers ) ) : ?>
                        <tr>
                            <td colspan="9" class="no-items"><?php _e( 'No workers found.', 'schedspot' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $workers as $worker ) : ?>
                            <?php
                            $worker_meta = get_user_meta( $worker->ID );
                            $worker_services = get_user_meta( $worker->ID, 'schedspot_worker_services', true ) ?: array();
                            $worker_rating = get_user_meta( $worker->ID, 'schedspot_worker_rating', true ) ?: 0;
                            $worker_status = get_user_meta( $worker->ID, 'schedspot_worker_status', true ) ?: 'active';
                            $worker_phone = get_user_meta( $worker->ID, 'schedspot_worker_phone', true );
                            ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="worker[]" value="<?php echo esc_attr( $worker->ID ); ?>">
                                </th>
                                <td class="column-avatar">
                                    <?php echo get_avatar( $worker->ID, 40 ); ?>
                                </td>
                                <td class="column-name">
                                    <strong><?php echo esc_html( $worker->display_name ); ?></strong>
                                    <div class="row-actions">
                                        <span class="view"><a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=view&worker_id=' . $worker->ID ); ?>"><?php _e( 'View', 'schedspot' ); ?></a> | </span>
                                        <span class="edit"><a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=edit&worker_id=' . $worker->ID ); ?>"><?php _e( 'Edit', 'schedspot' ); ?></a> | </span>
                                        <span class="availability"><a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=availability&worker_id=' . $worker->ID ); ?>"><?php _e( 'Availability', 'schedspot' ); ?></a></span>
                                    </div>
                                </td>
                                <td class="column-email">
                                    <?php echo esc_html( $worker->user_email ); ?>
                                </td>
                                <td class="column-phone">
                                    <?php echo esc_html( $worker_phone ?: __( 'Not provided', 'schedspot' ) ); ?>
                                </td>
                                <td class="column-services">
                                    <?php echo esc_html( count( $worker_services ) ); ?>
                                </td>
                                <td class="column-rating">
                                    <div class="worker-rating">
                                        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                            <span class="star <?php echo $i <= $worker_rating ? 'filled' : 'empty'; ?>">★</span>
                                        <?php endfor; ?>
                                        <span class="rating-text">(<?php echo esc_html( number_format( $worker_rating, 1 ) ); ?>)</span>
                                    </div>
                                </td>
                                <td class="column-status">
                                    <span class="status-badge status-<?php echo esc_attr( $worker_status ); ?>">
                                        <?php echo esc_html( ucfirst( $worker_status ) ); ?>
                                    </span>
                                </td>
                                <td class="column-actions">
                                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=view&worker_id=' . $worker->ID ); ?>" class="button button-small"><?php _e( 'View', 'schedspot' ); ?></a>
                                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=edit&worker_id=' . $worker->ID ); ?>" class="button button-small"><?php _e( 'Edit', 'schedspot' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <style>
        .column-avatar { width: 60px; }
        .worker-rating .star.filled { color: #ffa500; }
        .worker-rating .star.empty { color: #ddd; }
        .worker-rating .rating-text { font-size: 0.9em; color: #666; margin-left: 5px; }
        .status-badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .status-pending { background: #fff3cd; color: #856404; }
        </style>
        <?php
    }

    /**
     * Render add worker form.
     *
     * @since 1.0.0
     */
    private function render_add_worker_form() {
        $services = SchedSpot_Service::get_all_services();
        ?>
        <div class="wrap">
            <h1><?php _e( 'Add New Worker', 'schedspot' ); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field( 'schedspot_worker_action' ); ?>
                <input type="hidden" name="schedspot_worker_action" value="create">

                <h2><?php _e( 'User Account Information', 'schedspot' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="user_login"><?php _e( 'Username', 'schedspot' ); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="user_login" name="user_login" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="user_email"><?php _e( 'Email', 'schedspot' ); ?> *</label>
                        </th>
                        <td>
                            <input type="email" id="user_email" name="user_email" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="user_pass"><?php _e( 'Password', 'schedspot' ); ?> *</label>
                        </th>
                        <td>
                            <input type="password" id="user_pass" name="user_pass" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="display_name"><?php _e( 'Display Name', 'schedspot' ); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="display_name" name="display_name" class="regular-text" required>
                        </td>
                    </tr>
                </table>

                <h2><?php _e( 'Worker Profile Information', 'schedspot' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="worker_phone"><?php _e( 'Phone Number', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <input type="tel" id="worker_phone" name="worker_phone" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="worker_bio"><?php _e( 'Bio', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <textarea id="worker_bio" name="worker_bio" rows="4" cols="50" class="large-text"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="worker_skills"><?php _e( 'Skills', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="worker_skills" name="worker_skills" class="regular-text" placeholder="<?php _e( 'Comma-separated list of skills', 'schedspot' ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="worker_hourly_rate"><?php _e( 'Hourly Rate', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="worker_hourly_rate" name="worker_hourly_rate" min="0" step="0.01" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="worker_services"><?php _e( 'Services', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <?php if ( ! empty( $services ) ) : ?>
                                <?php foreach ( $services as $service ) : ?>
                                    <label>
                                        <input type="checkbox" name="worker_services[]" value="<?php echo esc_attr( $service->id ); ?>">
                                        <?php echo esc_html( $service->name ); ?>
                                    </label><br>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p><?php _e( 'No services available. Please create services first.', 'schedspot' ); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="worker_status"><?php _e( 'Status', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <select id="worker_status" name="worker_status">
                                <option value="active"><?php _e( 'Active', 'schedspot' ); ?></option>
                                <option value="inactive"><?php _e( 'Inactive', 'schedspot' ); ?></option>
                                <option value="pending"><?php _e( 'Pending Approval', 'schedspot' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="<?php _e( 'Add Worker', 'schedspot' ); ?>">
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers' ); ?>" class="button"><?php _e( 'Cancel', 'schedspot' ); ?></a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render worker profile.
     *
     * @since 1.0.0
     * @param int $worker_id Worker ID.
     */
    private function render_worker_profile( $worker_id ) {
        $worker = get_user_by( 'ID', $worker_id );
        
        if ( ! $worker || ! in_array( 'schedspot_worker', $worker->roles ) ) {
            wp_die( __( 'Worker not found.', 'schedspot' ) );
        }

        $worker_meta = get_user_meta( $worker_id );
        $worker_services = get_user_meta( $worker_id, 'schedspot_worker_services', true ) ?: array();
        $worker_rating = get_user_meta( $worker_id, 'schedspot_worker_rating', true ) ?: 0;
        $worker_status = get_user_meta( $worker_id, 'schedspot_worker_status', true ) ?: 'active';
        ?>
        <div class="wrap">
            <h1><?php printf( __( 'Worker Profile: %s', 'schedspot' ), esc_html( $worker->display_name ) ); ?>
                <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=edit&worker_id=' . $worker_id ); ?>" class="page-title-action"><?php _e( 'Edit', 'schedspot' ); ?></a>
            </h1>

            <div class="worker-profile-container">
                <div class="worker-profile-grid">
                    <div class="worker-section">
                        <h3><?php _e( 'Basic Information', 'schedspot' ); ?></h3>
                        <div class="worker-avatar">
                            <?php echo get_avatar( $worker_id, 100 ); ?>
                        </div>
                        <table class="form-table">
                            <tr>
                                <th><?php _e( 'Name', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( $worker->display_name ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Email', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( $worker->user_email ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Phone', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( get_user_meta( $worker_id, 'schedspot_worker_phone', true ) ?: __( 'Not provided', 'schedspot' ) ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Status', 'schedspot' ); ?></th>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr( $worker_status ); ?>">
                                        <?php echo esc_html( ucfirst( $worker_status ) ); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Rating', 'schedspot' ); ?></th>
                                <td>
                                    <div class="worker-rating">
                                        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                            <span class="star <?php echo $i <= $worker_rating ? 'filled' : 'empty'; ?>">★</span>
                                        <?php endfor; ?>
                                        <span class="rating-text">(<?php echo esc_html( number_format( $worker_rating, 1 ) ); ?>)</span>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="worker-section">
                        <h3><?php _e( 'Professional Information', 'schedspot' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><?php _e( 'Hourly Rate', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( '$' . number_format( get_user_meta( $worker_id, 'schedspot_worker_hourly_rate', true ) ?: 0, 2 ) ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Services', 'schedspot' ); ?></th>
                                <td>
                                    <?php if ( ! empty( $worker_services ) ) : ?>
                                        <?php
                                        $service_names = array();
                                        foreach ( $worker_services as $service_id ) {
                                            $service = new SchedSpot_Service( $service_id );
                                            if ( $service->id ) {
                                                $service_names[] = $service->name;
                                            }
                                        }
                                        echo esc_html( implode( ', ', $service_names ) );
                                        ?>
                                    <?php else : ?>
                                        <?php _e( 'No services assigned', 'schedspot' ); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Skills', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( get_user_meta( $worker_id, 'schedspot_worker_skills', true ) ?: __( 'Not specified', 'schedspot' ) ); ?></td>
                            </tr>
                        </table>
                    </div>

                    <?php
                    $bio = get_user_meta( $worker_id, 'schedspot_worker_bio', true );
                    if ( $bio ) :
                    ?>
                    <div class="worker-section">
                        <h3><?php _e( 'Bio', 'schedspot' ); ?></h3>
                        <div class="worker-bio">
                            <?php echo wp_kses_post( wpautop( $bio ) ); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="worker-actions">
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers' ); ?>" class="button"><?php _e( 'Back to Workers', 'schedspot' ); ?></a>
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=edit&worker_id=' . $worker_id ); ?>" class="button button-primary"><?php _e( 'Edit Worker', 'schedspot' ); ?></a>
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=availability&worker_id=' . $worker_id ); ?>" class="button"><?php _e( 'Manage Availability', 'schedspot' ); ?></a>
                </div>
            </div>
        </div>

        <style>
        .worker-profile-container {
            max-width: 1200px;
        }
        .worker-profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .worker-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        .worker-section h3 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .worker-avatar {
            text-align: center;
            margin-bottom: 20px;
        }
        .worker-bio {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #0073aa;
        }
        .worker-actions {
            padding: 20px 0;
            border-top: 1px solid #ccd0d4;
        }
        .worker-actions .button {
            margin-right: 10px;
        }
        .worker-rating .star.filled { color: #ffa500; }
        .worker-rating .star.empty { color: #ddd; }
        .worker-rating .rating-text { font-size: 0.9em; color: #666; margin-left: 5px; }
        </style>
        <?php
    }

    /**
     * Handle worker form submission.
     *
     * @since 1.0.0
     */
    private function handle_worker_form_submission() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'schedspot_worker_action' ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        $action = sanitize_text_field( $_POST['schedspot_worker_action'] );

        switch ( $action ) {
            case 'create':
                $this->create_worker();
                break;
            case 'update':
                $this->update_worker();
                break;
        }
    }

    /**
     * Create new worker.
     *
     * @since 1.0.0
     */
    private function create_worker() {
        $user_data = array(
            'user_login' => sanitize_user( $_POST['user_login'] ),
            'user_email' => sanitize_email( $_POST['user_email'] ),
            'user_pass' => $_POST['user_pass'],
            'display_name' => sanitize_text_field( $_POST['display_name'] ),
            'role' => 'schedspot_worker',
        );

        $user_id = wp_insert_user( $user_data );

        if ( is_wp_error( $user_id ) ) {
            wp_redirect( admin_url( 'admin.php?page=schedspot-workers&action=add&error=' . urlencode( $user_id->get_error_message() ) ) );
            exit;
        }

        // Save worker meta data
        update_user_meta( $user_id, 'schedspot_worker_phone', sanitize_text_field( $_POST['worker_phone'] ) );
        update_user_meta( $user_id, 'schedspot_worker_bio', sanitize_textarea_field( $_POST['worker_bio'] ) );
        update_user_meta( $user_id, 'schedspot_worker_skills', sanitize_text_field( $_POST['worker_skills'] ) );
        update_user_meta( $user_id, 'schedspot_worker_hourly_rate', floatval( $_POST['worker_hourly_rate'] ) );
        update_user_meta( $user_id, 'schedspot_worker_status', sanitize_text_field( $_POST['worker_status'] ) );
        
        if ( isset( $_POST['worker_services'] ) && is_array( $_POST['worker_services'] ) ) {
            update_user_meta( $user_id, 'schedspot_worker_services', array_map( 'absint', $_POST['worker_services'] ) );
        }

        wp_redirect( admin_url( 'admin.php?page=schedspot-workers&created=1' ) );
        exit;
    }

    /**
     * Handle delete worker action.
     *
     * @since 1.0.0
     * @param int $worker_id Worker ID.
     */
    private function handle_delete_worker( $worker_id ) {
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'delete_worker_' . $worker_id ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        $worker = get_user_by( 'ID', $worker_id );
        
        if ( ! $worker || ! in_array( 'schedspot_worker', $worker->roles ) ) {
            wp_die( __( 'Worker not found.', 'schedspot' ) );
        }

        if ( wp_delete_user( $worker_id ) ) {
            wp_redirect( admin_url( 'admin.php?page=schedspot-workers&deleted=1' ) );
            exit;
        } else {
            wp_redirect( admin_url( 'admin.php?page=schedspot-workers&error=1' ) );
            exit;
        }
    }
}
