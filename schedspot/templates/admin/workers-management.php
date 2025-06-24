<?php
/**
 * Admin Workers Management Template
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'Workers', 'schedspot' ); ?></h1>
    <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=add' ); ?>" class="page-title-action">
        <?php _e( 'Add New Worker', 'schedspot' ); ?>
    </a>
    <hr class="wp-header-end">

    <?php settings_errors( 'schedspot_workers' ); ?>

    <?php if ( $current_view === 'edit' && $editing_worker ) : ?>
        <!-- Edit Worker Form -->
        <div class="schedspot-edit-worker">
            <h2><?php printf( __( 'Edit Worker: %s', 'schedspot' ), esc_html( $editing_worker->display_name ) ); ?></h2>
            
            <form method="post">
                <?php wp_nonce_field( 'update_worker_' . $editing_worker->ID ); ?>
                <input type="hidden" name="action" value="update_worker">
                <input type="hidden" name="worker_id" value="<?php echo esc_attr( $editing_worker->ID ); ?>">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="worker_bio"><?php _e( 'Bio', 'schedspot' ); ?></label></th>
                        <td>
                            <textarea id="worker_bio" name="worker_bio" rows="4" class="large-text"><?php echo esc_textarea( $editing_worker->profile['bio'] ?? '' ); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="worker_skills"><?php _e( 'Skills', 'schedspot' ); ?></label></th>
                        <td>
                            <input type="text" id="worker_skills" name="worker_skills" value="<?php echo esc_attr( implode( ', ', $editing_worker->profile['skills'] ?? array() ) ); ?>" class="regular-text">
                            <p class="description"><?php _e( 'Comma-separated list of skills.', 'schedspot' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hourly_rate"><?php _e( 'Hourly Rate', 'schedspot' ); ?></label></th>
                        <td>
                            <input type="number" id="hourly_rate" name="hourly_rate" value="<?php echo esc_attr( $editing_worker->profile['hourly_rate'] ?? '' ); ?>" step="0.01" min="0" class="small-text">
                            <span class="description">$</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="experience_years"><?php _e( 'Years of Experience', 'schedspot' ); ?></label></th>
                        <td>
                            <input type="number" id="experience_years" name="experience_years" value="<?php echo esc_attr( $editing_worker->profile['experience_years'] ?? '' ); ?>" min="0" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="worker_phone"><?php _e( 'Phone', 'schedspot' ); ?></label></th>
                        <td>
                            <input type="tel" id="worker_phone" name="worker_phone" value="<?php echo esc_attr( $editing_worker->profile['phone'] ?? '' ); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="worker_address"><?php _e( 'Address', 'schedspot' ); ?></label></th>
                        <td>
                            <input type="text" id="worker_address" name="worker_address" value="<?php echo esc_attr( $editing_worker->profile['address'] ?? '' ); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="assigned_services"><?php _e( 'Assigned Services', 'schedspot' ); ?></label></th>
                        <td>
                            <?php if ( ! empty( $services ) ) : ?>
                                <?php foreach ( $services as $service ) : ?>
                                    <label>
                                        <input type="checkbox" name="assigned_services[]" value="<?php echo esc_attr( $service->id ); ?>" 
                                               <?php checked( in_array( $service->id, get_user_meta( $editing_worker->ID, 'schedspot_assigned_services', true ) ?: array() ) ); ?>>
                                        <?php echo esc_html( $service->name ); ?>
                                    </label><br>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p><?php _e( 'No services available. Please create services first.', 'schedspot' ); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php esc_attr_e( 'Update Worker', 'schedspot' ); ?>">
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers' ); ?>" class="button"><?php _e( 'Cancel', 'schedspot' ); ?></a>
                </p>
            </form>
        </div>

    <?php else : ?>
        <!-- Workers List -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <form method="get">
                    <input type="hidden" name="page" value="schedspot-workers">
                    
                    <select name="status">
                        <option value=""><?php _e( 'All Workers', 'schedspot' ); ?></option>
                        <option value="available" <?php selected( $_GET['status'] ?? '', 'available' ); ?>><?php _e( 'Available', 'schedspot' ); ?></option>
                        <option value="unavailable" <?php selected( $_GET['status'] ?? '', 'unavailable' ); ?>><?php _e( 'Unavailable', 'schedspot' ); ?></option>
                    </select>

                    <input type="search" name="s" value="<?php echo esc_attr( $_GET['s'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Search workers...', 'schedspot' ); ?>">

                    <input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'schedspot' ); ?>">
                </form>
            </div>
        </div>

        <form method="post">
            <?php wp_nonce_field( 'bulk-workers' ); ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1">
                        </td>
                        <th class="manage-column"><?php _e( 'Worker', 'schedspot' ); ?></th>
                        <th class="manage-column"><?php _e( 'Contact', 'schedspot' ); ?></th>
                        <th class="manage-column"><?php _e( 'Skills', 'schedspot' ); ?></th>
                        <th class="manage-column"><?php _e( 'Rate', 'schedspot' ); ?></th>
                        <th class="manage-column"><?php _e( 'Bookings', 'schedspot' ); ?></th>
                        <th class="manage-column"><?php _e( 'Status', 'schedspot' ); ?></th>
                        <th class="manage-column"><?php _e( 'Actions', 'schedspot' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $workers ) ) : ?>
                        <?php foreach ( $workers as $worker ) : ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="worker[]" value="<?php echo esc_attr( $worker->ID ); ?>">
                                </th>
                                <td>
                                    <strong>
                                        <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=edit&worker_id=' . $worker->ID ); ?>">
                                            <?php echo esc_html( $worker->display_name ); ?>
                                        </a>
                                    </strong>
                                    <br><small><?php echo esc_html( $worker->user_login ); ?></small>
                                </td>
                                <td>
                                    <?php echo esc_html( $worker->user_email ); ?>
                                    <?php if ( ! empty( $worker->profile['phone'] ) ) : ?>
                                        <br><small><?php echo esc_html( $worker->profile['phone'] ); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( ! empty( $worker->profile['skills'] ) ) : ?>
                                        <?php foreach ( array_slice( $worker->profile['skills'], 0, 3 ) as $skill ) : ?>
                                            <span class="skill-tag"><?php echo esc_html( $skill ); ?></span>
                                        <?php endforeach; ?>
                                        <?php if ( count( $worker->profile['skills'] ) > 3 ) : ?>
                                            <span class="skill-tag">+<?php echo count( $worker->profile['skills'] ) - 3; ?></span>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span class="description"><?php _e( 'No skills listed', 'schedspot' ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( ! empty( $worker->profile['hourly_rate'] ) ) : ?>
                                        <strong>$<?php echo number_format( $worker->profile['hourly_rate'], 2 ); ?>/hr</strong>
                                    <?php else : ?>
                                        <span class="description"><?php _e( 'Not set', 'schedspot' ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html( $worker->total_bookings ); ?></strong>
                                    <br><small><?php printf( __( 'Rating: %.1f', 'schedspot' ), $worker->rating ); ?></small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $worker->is_available ? 'available' : 'unavailable'; ?>">
                                        <?php echo $worker->is_available ? __( 'Available', 'schedspot' ) : __( 'Unavailable', 'schedspot' ); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=edit&worker_id=' . $worker->ID ); ?>">
                                                <?php _e( 'Edit', 'schedspot' ); ?>
                                            </a> |
                                        </span>
                                        <span class="toggle">
                                            <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=schedspot-workers&action=toggle_status&worker_id=' . $worker->ID ), 'toggle_status_' . $worker->ID ); ?>">
                                                <?php echo $worker->is_available ? __( 'Make Unavailable', 'schedspot' ) : __( 'Make Available', 'schedspot' ); ?>
                                            </a> |
                                        </span>
                                        <span class="delete">
                                            <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=schedspot-workers&action=delete&worker_id=' . $worker->ID ), 'delete_worker_' . $worker->ID ); ?>" 
                                               onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to remove this worker? This will not delete the user account.', 'schedspot' ); ?>')">
                                                <?php _e( 'Remove', 'schedspot' ); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="8" class="no-items"><?php _e( 'No workers found.', 'schedspot' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <select name="bulk_action">
                        <option value="-1"><?php _e( 'Bulk Actions', 'schedspot' ); ?></option>
                        <option value="activate"><?php _e( 'Make Available', 'schedspot' ); ?></option>
                        <option value="deactivate"><?php _e( 'Make Unavailable', 'schedspot' ); ?></option>
                    </select>
                    <input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', 'schedspot' ); ?>">
                </div>
            </div>
        </form>

        <!-- Add New Worker Form -->
        <div class="schedspot-add-worker" style="margin-top: 30px;">
            <h2><?php _e( 'Add New Worker', 'schedspot' ); ?></h2>
            
            <form method="post">
                <?php wp_nonce_field( 'add_worker' ); ?>
                <input type="hidden" name="action" value="add_worker">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="user_id"><?php _e( 'Select User', 'schedspot' ); ?></label></th>
                        <td>
                            <select id="user_id" name="user_id" required>
                                <option value=""><?php _e( 'Choose a user...', 'schedspot' ); ?></option>
                                <?php foreach ( $available_users as $user ) : ?>
                                    <option value="<?php echo esc_attr( $user->ID ); ?>">
                                        <?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Select an existing user to make them a worker.', 'schedspot' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="new_worker_bio"><?php _e( 'Bio', 'schedspot' ); ?></label></th>
                        <td><textarea id="new_worker_bio" name="worker_bio" rows="4" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="new_worker_skills"><?php _e( 'Skills', 'schedspot' ); ?></label></th>
                        <td>
                            <input type="text" id="new_worker_skills" name="worker_skills" class="regular-text">
                            <p class="description"><?php _e( 'Comma-separated list of skills.', 'schedspot' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="new_hourly_rate"><?php _e( 'Hourly Rate', 'schedspot' ); ?></label></th>
                        <td>
                            <input type="number" id="new_hourly_rate" name="hourly_rate" step="0.01" min="0" class="small-text" value="0.00">
                            <span class="description">$</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="new_experience_years"><?php _e( 'Years of Experience', 'schedspot' ); ?></label></th>
                        <td><input type="number" id="new_experience_years" name="experience_years" min="0" class="small-text" value="0"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="new_worker_phone"><?php _e( 'Phone', 'schedspot' ); ?></label></th>
                        <td><input type="tel" id="new_worker_phone" name="worker_phone" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="new_worker_address"><?php _e( 'Address', 'schedspot' ); ?></label></th>
                        <td><input type="text" id="new_worker_address" name="worker_address" class="regular-text"></td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php esc_attr_e( 'Add Worker', 'schedspot' ); ?>">
                </p>
            </form>
        </div>
    <?php endif; ?>
</div>

<style>
.status-badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-available { background: #d4edda; color: #155724; }
.status-unavailable { background: #f8d7da; color: #721c24; }

.skill-tag {
    display: inline-block;
    background: #e9ecef;
    color: #495057;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    margin-right: 4px;
    margin-bottom: 2px;
}

.schedspot-add-worker,
.schedspot-edit-worker {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.tablenav .actions {
    padding: 2px 0;
}

.tablenav .actions input,
.tablenav .actions select {
    margin-right: 6px;
}
</style>
