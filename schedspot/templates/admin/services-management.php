<?php
/**
 * Admin Services Management Template
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
    <h1 class="wp-heading-inline"><?php _e( 'Services', 'schedspot' ); ?></h1>
    <a href="<?php echo admin_url( 'admin.php?page=schedspot-services&action=add' ); ?>" class="page-title-action">
        <?php _e( 'Add New Service', 'schedspot' ); ?>
    </a>
    <hr class="wp-header-end">

    <?php settings_errors( 'schedspot_services' ); ?>

    <?php if ( $current_view === 'edit' && $editing_service ) : ?>
        <!-- Edit Service Form -->
        <div class="schedspot-edit-service">
            <h2><?php _e( 'Edit Service', 'schedspot' ); ?></h2>
            
            <form method="post">
                <?php wp_nonce_field( 'update_service_' . $editing_service->id ); ?>
                <input type="hidden" name="action" value="update_service">
                <input type="hidden" name="service_id" value="<?php echo esc_attr( $editing_service->id ); ?>">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="service_name"><?php _e( 'Service Name', 'schedspot' ); ?></label></th>
                        <td><input type="text" id="service_name" name="service_name" value="<?php echo esc_attr( $editing_service->name ); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_description"><?php _e( 'Description', 'schedspot' ); ?></label></th>
                        <td><textarea id="service_description" name="service_description" rows="4" class="large-text"><?php echo esc_textarea( $editing_service->description ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_category"><?php _e( 'Category', 'schedspot' ); ?></label></th>
                        <td>
                            <input type="text" id="service_category" name="service_category" value="<?php echo esc_attr( $editing_service->category ); ?>" class="regular-text">
                            <p class="description"><?php _e( 'Service category for organization.', 'schedspot' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_price"><?php _e( 'Base Price', 'schedspot' ); ?></label></th>
                        <td>
                            <input type="number" id="service_price" name="service_price" value="<?php echo esc_attr( $editing_service->base_price ); ?>" step="0.01" min="0" class="small-text">
                            <span class="description">$</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_duration"><?php _e( 'Duration (minutes)', 'schedspot' ); ?></label></th>
                        <td><input type="number" id="service_duration" name="service_duration" value="<?php echo esc_attr( $editing_service->duration ); ?>" min="15" step="15" class="small-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_status"><?php _e( 'Status', 'schedspot' ); ?></label></th>
                        <td>
                            <select id="service_status" name="service_status">
                                <option value="1" <?php selected( $editing_service->is_active, 1 ); ?>><?php _e( 'Active', 'schedspot' ); ?></option>
                                <option value="0" <?php selected( $editing_service->is_active, 0 ); ?>><?php _e( 'Inactive', 'schedspot' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php esc_attr_e( 'Update Service', 'schedspot' ); ?>">
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-services' ); ?>" class="button"><?php _e( 'Cancel', 'schedspot' ); ?></a>
                </p>
            </form>
        </div>

    <?php else : ?>
        <!-- Services List -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <form method="get">
                    <input type="hidden" name="page" value="schedspot-services">
                    
                    <select name="category">
                        <option value=""><?php _e( 'All Categories', 'schedspot' ); ?></option>
                        <?php foreach ( $categories as $category ) : ?>
                            <option value="<?php echo esc_attr( $category ); ?>" <?php selected( $_GET['category'] ?? '', $category ); ?>>
                                <?php echo esc_html( $category ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="status">
                        <option value=""><?php _e( 'All Statuses', 'schedspot' ); ?></option>
                        <option value="1" <?php selected( $_GET['status'] ?? '', '1' ); ?>><?php _e( 'Active', 'schedspot' ); ?></option>
                        <option value="0" <?php selected( $_GET['status'] ?? '', '0' ); ?>><?php _e( 'Inactive', 'schedspot' ); ?></option>
                    </select>

                    <input type="search" name="s" value="<?php echo esc_attr( $_GET['s'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Search services...', 'schedspot' ); ?>">

                    <input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'schedspot' ); ?>">
                </form>
            </div>
        </div>

        <form method="post">
            <?php wp_nonce_field( 'bulk-services' ); ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1">
                        </td>
                        <th class="manage-column"><?php _e( 'Name', 'schedspot' ); ?></th>
                        <th class="manage-column"><?php _e( 'Category', 'schedspot' ); ?></th>
                        <th class="manage-column"><?php _e( 'Duration', 'schedspot' ); ?></th>
                        <th class="manage-column"><?php _e( 'Base Price', 'schedspot' ); ?></th>
                        <th class="manage-column"><?php _e( 'Status', 'schedspot' ); ?></th>
                        <th class="manage-column"><?php _e( 'Actions', 'schedspot' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $services ) ) : ?>
                        <?php foreach ( $services as $service ) : ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="service[]" value="<?php echo esc_attr( $service->id ); ?>">
                                </th>
                                <td>
                                    <strong>
                                        <a href="<?php echo admin_url( 'admin.php?page=schedspot-services&action=edit&service_id=' . $service->id ); ?>">
                                            <?php echo esc_html( $service->name ); ?>
                                        </a>
                                    </strong>
                                    <?php if ( $service->description ) : ?>
                                        <br><small class="description"><?php echo esc_html( wp_trim_words( $service->description, 10 ) ); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $service->category ?: __( 'Uncategorized', 'schedspot' ) ); ?></td>
                                <td><?php echo esc_html( $service->duration ); ?> <?php _e( 'min', 'schedspot' ); ?></td>
                                <td><strong>$<?php echo number_format( $service->base_price, 2 ); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo $service->is_active ? 'active' : 'inactive'; ?>">
                                        <?php echo $service->is_active ? __( 'Active', 'schedspot' ) : __( 'Inactive', 'schedspot' ); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo admin_url( 'admin.php?page=schedspot-services&action=edit&service_id=' . $service->id ); ?>">
                                                <?php _e( 'Edit', 'schedspot' ); ?>
                                            </a> |
                                        </span>
                                        <span class="duplicate">
                                            <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=schedspot-services&action=duplicate&service_id=' . $service->id ), 'duplicate_service_' . $service->id ); ?>">
                                                <?php _e( 'Duplicate', 'schedspot' ); ?>
                                            </a> |
                                        </span>
                                        <span class="toggle">
                                            <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=schedspot-services&action=toggle_status&service_id=' . $service->id ), 'toggle_status_' . $service->id ); ?>">
                                                <?php echo $service->is_active ? __( 'Deactivate', 'schedspot' ) : __( 'Activate', 'schedspot' ); ?>
                                            </a> |
                                        </span>
                                        <span class="delete">
                                            <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=schedspot-services&action=delete&service_id=' . $service->id ), 'delete_service_' . $service->id ); ?>" 
                                               onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this service?', 'schedspot' ); ?>')">
                                                <?php _e( 'Delete', 'schedspot' ); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7" class="no-items"><?php _e( 'No services found.', 'schedspot' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <select name="bulk_action">
                        <option value="-1"><?php _e( 'Bulk Actions', 'schedspot' ); ?></option>
                        <option value="activate"><?php _e( 'Activate', 'schedspot' ); ?></option>
                        <option value="deactivate"><?php _e( 'Deactivate', 'schedspot' ); ?></option>
                        <option value="delete"><?php _e( 'Delete', 'schedspot' ); ?></option>
                    </select>
                    <input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', 'schedspot' ); ?>">
                </div>
            </div>
        </form>

        <!-- Add New Service Form -->
        <div class="schedspot-add-service" style="margin-top: 30px;">
            <h2><?php _e( 'Add New Service', 'schedspot' ); ?></h2>
            
            <form method="post">
                <?php wp_nonce_field( 'add_service' ); ?>
                <input type="hidden" name="action" value="add_service">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="new_service_name"><?php _e( 'Service Name', 'schedspot' ); ?></label></th>
                        <td><input type="text" id="new_service_name" name="service_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="new_service_description"><?php _e( 'Description', 'schedspot' ); ?></label></th>
                        <td><textarea id="new_service_description" name="service_description" rows="4" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="new_service_category"><?php _e( 'Category', 'schedspot' ); ?></label></th>
                        <td><input type="text" id="new_service_category" name="service_category" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="new_service_price"><?php _e( 'Base Price', 'schedspot' ); ?></label></th>
                        <td>
                            <input type="number" id="new_service_price" name="service_price" step="0.01" min="0" class="small-text" value="0.00">
                            <span class="description">$</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="new_service_duration"><?php _e( 'Duration (minutes)', 'schedspot' ); ?></label></th>
                        <td><input type="number" id="new_service_duration" name="service_duration" min="15" step="15" class="small-text" value="60"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="new_service_status"><?php _e( 'Status', 'schedspot' ); ?></label></th>
                        <td>
                            <select id="new_service_status" name="service_status">
                                <option value="1"><?php _e( 'Active', 'schedspot' ); ?></option>
                                <option value="0"><?php _e( 'Inactive', 'schedspot' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php esc_attr_e( 'Add Service', 'schedspot' ); ?>">
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

.status-active { background: #d4edda; color: #155724; }
.status-inactive { background: #f8d7da; color: #721c24; }

.schedspot-add-service,
.schedspot-edit-service {
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
