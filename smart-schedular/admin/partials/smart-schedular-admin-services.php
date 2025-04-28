<?php
/**
 * Provide a admin area view for managing services
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Smart_Schedular
 * @subpackage Smart_Schedular/admin/partials
 */

// Direct access security
if (!defined('WPINC')) {
    die;
}

global $wpdb;

// Process form submission for adding/editing service
if (isset($_POST['smart_schedular_service_submit']) && current_user_can('manage_options')) {
    // Verify nonce
    if (!isset($_POST['smart_schedular_service_nonce']) || !wp_verify_nonce($_POST['smart_schedular_service_nonce'], 'smart_schedular_service_save')) {
        wp_die(__('Security check failed', 'smart-schedular'));
    }
    
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    $service_name = isset($_POST['service_title']) ? sanitize_text_field($_POST['service_title']) : '';
    $service_description = isset($_POST['service_description']) ? wp_kses_post($_POST['service_description']) : '';
    $service_duration = isset($_POST['service_duration']) ? intval($_POST['service_duration']) : 60;
    $service_color = isset($_POST['service_color']) ? sanitize_hex_color($_POST['service_color']) : '#3788d8';
    
    // Handle available days - ensure it's an array
    $available_days = isset($_POST['available_days']) && is_array($_POST['available_days']) 
        ? implode(',', array_map('intval', $_POST['available_days'])) 
        : '1,2,3,4,5';
    
    // Validate required fields
    if (empty($service_name)) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Service name is required.', 'smart-schedular') . '</p></div>';
        return;
    }
    
    $table_name = $wpdb->prefix . 'smart_schedular_services';
    
    // Create or update service
    $service_data = array(
        'name' => $service_name,
        'description' => $service_description,
        'duration' => $service_duration,
        'color' => $service_color,
        'available_days' => $available_days,
        'updated_at' => current_time('mysql')
    );
    
    if ($service_id > 0) {
        // Update existing service
        $result = $wpdb->update(
            $table_name,
            $service_data,
            array('id' => $service_id)
        );
    } else {
        // Insert new service
        $service_data['created_at'] = current_time('mysql');
        $result = $wpdb->insert($table_name, $service_data);
        $service_id = $wpdb->insert_id;
    }
    
    if ($result !== false) {
        // Save business hours
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        foreach ($days as $day) {
            $is_available = isset($_POST[$day . '_available']) ? '1' : '0';
            $start_time = isset($_POST[$day . '_start']) ? sanitize_text_field($_POST[$day . '_start']) : '09:00';
            $end_time = isset($_POST[$day . '_end']) ? sanitize_text_field($_POST[$day . '_end']) : '17:00';
            
            update_post_meta($service_id, '_' . $day . '_available', $is_available);
            update_post_meta($service_id, '_' . $day . '_start', $start_time);
            update_post_meta($service_id, '_' . $day . '_end', $end_time);
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>' . 
            ($service_id > 0 ? esc_html__('Service updated successfully.', 'smart-schedular') : esc_html__('Service created successfully.', 'smart-schedular')) . 
            '</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Error saving service.', 'smart-schedular') . '</p></div>';
    }
}

// Process service deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['service_id']) && current_user_can('manage_options')) {
    $service_id = intval($_GET['service_id']);
    
    // Verify nonce
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'smart_schedular_delete_service_' . $service_id)) {
        wp_die(__('Security check failed', 'smart-schedular'));
    }
    
    // Check if service is being used in appointments
    $appointments_table = $wpdb->prefix . 'smart_schedular_appointments';
    $appointment_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $appointments_table WHERE service_id = %d",
        $service_id
    ));
    
    if ($appointment_count > 0) {
        echo '<div class="notice notice-error is-dismissible"><p>' . 
            esc_html__('This service cannot be deleted because it is associated with existing appointments.', 'smart-schedular') . 
            '</p></div>';
    } else {
        try {
            // Delete the service
            $table_name = $wpdb->prefix . 'smart_schedular_services';
            $result = $wpdb->delete(
                $table_name,
                array('id' => $service_id),
                array('%d')
            );
            
            if ($result) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Service deleted successfully.', 'smart-schedular') . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . 
                    esc_html__('Error deleting service: ', 'smart-schedular') . 
                    ($wpdb->last_error ? esc_html($wpdb->last_error) : esc_html__('No rows affected', 'smart-schedular')) . 
                    '</p></div>';
            }
        } catch (Exception $e) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Error: ', 'smart-schedular') . esc_html($e->getMessage()) . '</p></div>';
        }
    }
}

// Get service for editing
$edit_service = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['service_id'])) {
    $service_id = intval($_GET['service_id']);
    $table_name = $wpdb->prefix . 'smart_schedular_services';
    
    $edit_service = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $service_id
    ));
    
    // Debug info
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Edit service: ' . print_r($edit_service, true));
    }
}

// Get all services
$table_name = $wpdb->prefix . 'smart_schedular_services';
$services = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id ASC");

// Debug info
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('All services: ' . print_r($services, true));
}

// Days of the week for available days selection
$days_of_week = array(
    1 => __('Monday', 'smart-schedular'),
    2 => __('Tuesday', 'smart-schedular'),
    3 => __('Wednesday', 'smart-schedular'),
    4 => __('Thursday', 'smart-schedular'),
    5 => __('Friday', 'smart-schedular'),
    6 => __('Saturday', 'smart-schedular'),
    7 => __('Sunday', 'smart-schedular')
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=smart-schedular-services&action=new')); ?>" class="page-title-action"><?php esc_html_e('Add New Service', 'smart-schedular'); ?></a>
    <hr class="wp-header-end">
    
    <?php if (isset($_GET['action']) && ($_GET['action'] === 'new' || $_GET['action'] === 'edit')): ?>
        <!-- Service Add/Edit Form -->
        <div class="smart-schedular-service-form">
            <h2><?php echo $edit_service ? esc_html__('Edit Service', 'smart-schedular') : esc_html__('Add New Service', 'smart-schedular'); ?></h2>
            
            <form method="post" action="" class="service-form">
                <?php wp_nonce_field('smart_schedular_service_save', 'smart_schedular_service_nonce'); ?>
                <input type="hidden" name="service_id" value="<?php echo $edit_service ? esc_attr($edit_service->id) : ''; ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="service_title"><?php esc_html_e('Service Name', 'smart-schedular'); ?> <span class="required">*</span></label></th>
                        <td>
                            <input type="text" name="service_title" id="service_title" class="regular-text" value="<?php echo $edit_service && isset($edit_service->name) ? esc_attr($edit_service->name) : ''; ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_description"><?php esc_html_e('Description', 'smart-schedular'); ?></label></th>
                        <td>
                            <?php
                            $content = $edit_service && isset($edit_service->description) ? $edit_service->description : '';
                            $editor_settings = array(
                                'textarea_name' => 'service_description',
                                'textarea_rows' => 5,
                                'media_buttons' => false,
                            );
                            wp_editor($content, 'service_description', $editor_settings);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_duration"><?php esc_html_e('Duration (minutes)', 'smart-schedular'); ?></label></th>
                        <td>
                            <input type="number" name="service_duration" id="service_duration" class="small-text" min="15" step="15" value="<?php echo $edit_service && isset($edit_service->duration) ? esc_attr($edit_service->duration) : '60'; ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_color"><?php esc_html_e('Color', 'smart-schedular'); ?></label></th>
                        <td>
                            <input type="color" name="service_color" id="service_color" value="<?php echo $edit_service && isset($edit_service->color) ? esc_attr($edit_service->color) : '#3788d8'; ?>">
                            <p class="description"><?php esc_html_e('Choose a color for this service (used in calendar view).', 'smart-schedular'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_timezone"><?php esc_html_e('Service Timezone', 'smart-schedular'); ?></label></th>
                        <td>
                            <?php
                            $timezones = array(
                                'UTC' => 'UTC (GMT+0)',
                                'EST' => 'EST (GMT-5)',
                                'PST' => 'PST (GMT-8)',
                                'IST' => 'IST (GMT+5:30)'
                            );
                            $current_timezone = $edit_service && isset($edit_service->timezone) ? $edit_service->timezone : 'UTC';
                            ?>
                            <select name="service_timezone" id="service_timezone" class="regular-text">
                                <?php foreach ($timezones as $tz_key => $tz_label): ?>
                                    <option value="<?php echo esc_attr($tz_key); ?>" <?php selected($current_timezone, $tz_key); ?>>
                                        <?php echo esc_html($tz_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('Select the timezone for this service. All appointments will be scheduled in this timezone.', 'smart-schedular'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Available Days', 'smart-schedular'); ?></th>
                        <td>
                            <?php 
                            $available_days = array(1,2,3,4,5);  // Default Mon-Fri
                            if ($edit_service && isset($edit_service->available_days) && !empty($edit_service->available_days)) {
                                $available_days = explode(',', $edit_service->available_days);
                            }
                            
                            foreach ($days_of_week as $day_num => $day_name): ?>
                                <label>
                                    <input type="checkbox" name="available_days[]" value="<?php echo esc_attr($day_num); ?>" <?php checked(in_array($day_num, $available_days)); ?>>
                                    <?php echo esc_html($day_name); ?>
                                </label><br>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e('Select which days of the week this service is available.', 'smart-schedular'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h3><?php esc_html_e('Business Hours', 'smart-schedular'); ?></h3>
                <table class="form-table business-hours">
                    <?php
                    $days = array(
                        'monday' => __('Monday', 'smart-schedular'),
                        'tuesday' => __('Tuesday', 'smart-schedular'),
                        'wednesday' => __('Wednesday', 'smart-schedular'),
                        'thursday' => __('Thursday', 'smart-schedular'),
                        'friday' => __('Friday', 'smart-schedular'),
                        'saturday' => __('Saturday', 'smart-schedular'),
                        'sunday' => __('Sunday', 'smart-schedular')
                    );

                    foreach ($days as $day_key => $day_label) :
                        $start_time = isset($edit_service) ? get_post_meta($edit_service->id, '_' . $day_key . '_start', true) : '09:00';
                        $end_time = isset($edit_service) ? get_post_meta($edit_service->id, '_' . $day_key . '_end', true) : '17:00';
                        $is_available = isset($edit_service) ? get_post_meta($edit_service->id, '_' . $day_key . '_available', true) : '1';
                    ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($day_label); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr($day_key); ?>_available" value="1" <?php checked($is_available, '1'); ?>>
                                <?php esc_html_e('Available', 'smart-schedular'); ?>
                            </label>
                            <input type="time" name="<?php echo esc_attr($day_key); ?>_start" value="<?php echo esc_attr($start_time); ?>" class="business-hours-input">
                            <?php esc_html_e('to', 'smart-schedular'); ?>
                            <input type="time" name="<?php echo esc_attr($day_key); ?>_end" value="<?php echo esc_attr($end_time); ?>" class="business-hours-input">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                
                <p class="submit">
                    <input type="submit" name="smart_schedular_service_submit" class="button button-primary" value="<?php echo $edit_service ? esc_attr__('Update Service', 'smart-schedular') : esc_attr__('Add Service', 'smart-schedular'); ?>">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=smart-schedular-services')); ?>" class="button"><?php esc_html_e('Cancel', 'smart-schedular'); ?></a>
                </p>
            </form>
        </div>
    <?php else: ?>
        <!-- Services List -->
        <?php if (empty($services)): ?>
            <div class="notice notice-info">
                <p><?php esc_html_e('No services found. Create your first service to start accepting appointments.', 'smart-schedular'); ?></p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-title column-primary"><?php esc_html_e('Service', 'smart-schedular'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Duration', 'smart-schedular'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Shortcode', 'smart-schedular'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Actions', 'smart-schedular'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $service): 
                        // Check if service has appointments
                        $appointments_table = $wpdb->prefix . 'smart_schedular_appointments';
                        $has_appointments = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $appointments_table WHERE service_id = %d",
                            $service->id
                        ));
                    ?>
                        <tr>
                            <td class="title column-title column-primary">
                                <strong><a href="<?php echo esc_url(admin_url('admin.php?page=smart-schedular-services&action=edit&service_id=' . $service->id)); ?>"><?php echo isset($service->name) ? esc_html($service->name) : ''; ?></a></strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=smart-schedular-services&action=edit&service_id=' . $service->id)); ?>"><?php esc_html_e('Edit', 'smart-schedular'); ?></a> | 
                                    </span>
                                    <span class="delete">
                                        <?php if (empty($has_appointments)): ?>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=smart-schedular-services&action=delete&service_id=' . $service->id), 'smart_schedular_delete_service_' . $service->id); ?>" class="submitdelete" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this service?', 'smart-schedular'); ?>');"><?php esc_html_e('Delete', 'smart-schedular'); ?></a>
                                        <?php else: ?>
                                            <span title="<?php esc_attr_e('This service has appointments and cannot be deleted', 'smart-schedular'); ?>" class="text-muted"><?php esc_html_e('Delete', 'smart-schedular'); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </td>
                            <td><?php echo isset($service->duration) ? esc_html($service->duration) . ' ' . esc_html__('minutes', 'smart-schedular') : ''; ?></td>
                            <td>
                                <div class="shortcode-container">
                                    <code>[smart_schedular service_id="<?php echo esc_attr($service->id); ?>"]</code>
                                    <button type="button" class="copy-shortcode button button-small" data-shortcode='[smart_schedular service_id="<?php echo esc_attr($service->id); ?>"]'>
                                        <span class="dashicons dashicons-clipboard"></span> Copy
                                    </button>
                                  
                                </div>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=smart-schedular-services&action=edit&service_id=' . $service->id)); ?>" class="button button-small"><?php esc_html_e('Edit', 'smart-schedular'); ?></a>
                                <?php if ($has_appointments == 0): ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=smart-schedular-services&action=delete&service_id=' . $service->id), 'smart_schedular_delete_service_' . $service->id); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this service?', 'smart-schedular'); ?>');"><?php esc_html_e('Delete', 'smart-schedular'); ?></a>
                                <?php else: ?>
                                    <button class="button button-small" disabled title="<?php esc_attr_e('This service has appointments and cannot be deleted', 'smart-schedular'); ?>"><?php esc_html_e('Delete', 'smart-schedular'); ?></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.smart-schedular-service-form {
    max-width: 800px;
    margin-top: 20px;
}
.required {
    color: #dc3232;
}
.text-muted {
    color: #999;
    cursor: not-allowed;
}
.shortcode-container {
    position: relative;
}
.shortcode-container code {
    display: inline-block;
    font-size: 12px;
    background: #f6f7f7;
    padding: 4px 8px;
    border-radius: 3px;
    margin-right: 5px;
}
.copy-shortcode {
    vertical-align: middle;
}
.shortcode-note {
    font-size: 12px;
    margin-top: 5px;
    color: #666;
}
</style>
<script>
jQuery(document).ready(function($) {
    $('.copy-shortcode').on('click', function() {
        var shortcode = $(this).data('shortcode');
        var tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(shortcode).select();
        document.execCommand('copy');
        tempInput.remove();
        
        // Show copied message
        var $button = $(this);
        var originalText = $button.html();
        $button.html('<span class="dashicons dashicons-yes"></span> Copied!');
        setTimeout(function() {
            $button.html(originalText);
        }, 2000);
    });
});
</script> 