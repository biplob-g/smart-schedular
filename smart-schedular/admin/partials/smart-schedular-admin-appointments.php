<?php
/**
 * Provide a admin area view for managing appointments
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

// Get plugin options
$options = get_option('smart_schedular_options', array(
    'date_format' => get_option('date_format'),
    'time_format' => get_option('time_format'),
));

// Process appointment actions
if (isset($_GET['action']) && isset($_GET['appointment_id']) && current_user_can('manage_options')) {
    $appointment_id = intval($_GET['appointment_id']);
    
    // Verify nonce
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'smart_schedular_appointment_' . $appointment_id)) {
        wp_die(__('Security check failed', 'smart-schedular'));
    }
    
    $action = sanitize_text_field($_GET['action']);
    
    switch ($action) {
        case 'approve':
            update_post_meta($appointment_id, '_appointment_status', 'approved');
            // Send approval email to customer
            $customer_email = get_post_meta($appointment_id, '_customer_email', true);
            if (!empty($customer_email)) {
                // Email sending logic would go here
            }
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Appointment approved.', 'smart-schedular') . '</p></div>';
            break;
            
        case 'cancel':
            update_post_meta($appointment_id, '_appointment_status', 'cancelled');
            // Send cancellation email to customer
            $customer_email = get_post_meta($appointment_id, '_customer_email', true);
            if (!empty($customer_email)) {
                // Email sending logic would go here
            }
            echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__('Appointment cancelled.', 'smart-schedular') . '</p></div>';
            break;
            
        case 'delete':
            wp_delete_post($appointment_id, true);
            echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('Appointment deleted.', 'smart-schedular') . '</p></div>';
            break;
    }
}

// Get appointments
$appointments_query_args = array(
    'post_type' => 'smart_schedular_appt',
    'posts_per_page' => -1,
    'meta_key' => '_appointment_date',
    'orderby' => 'meta_value',
    'order' => 'DESC',
);

// Add service filter
if (isset($_GET['service_filter']) && !empty($_GET['service_filter'])) {
    $appointments_query_args['meta_query'][] = array(
        'key' => '_service_id',
        'value' => intval($_GET['service_filter']),
        'compare' => '='
    );
}

// Add date filter
if (isset($_GET['date_filter']) && !empty($_GET['date_filter'])) {
    $appointments_query_args['meta_query'][] = array(
        'key' => '_appointment_date',
        'value' => sanitize_text_field($_GET['date_filter']),
        'compare' => '='
    );
}

// Add status filter
if (isset($_GET['status_filter']) && !empty($_GET['status_filter'])) {
    $appointments_query_args['meta_query'][] = array(
        'key' => '_appointment_status',
        'value' => sanitize_text_field($_GET['status_filter']),
        'compare' => '='
    );
}

$appointments_query = new WP_Query($appointments_query_args);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (isset($_GET['view']) && $_GET['view'] === 'calendar'): ?>
        <div class="nav-tab-wrapper">
            <a href="<?php echo esc_url(admin_url('admin.php?page=smart-schedular-appointments')); ?>" class="nav-tab"><?php esc_html_e('List View', 'smart-schedular'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=smart-schedular-appointments&view=calendar')); ?>" class="nav-tab nav-tab-active"><?php esc_html_e('Calendar View', 'smart-schedular'); ?></a>
        </div>
        
        <div class="smart-schedular-calendar-view">
            <p><?php esc_html_e('Calendar view will be implemented in future updates.', 'smart-schedular'); ?></p>
        </div>
    <?php else: ?>
        <div class="nav-tab-wrapper">
            <a href="<?php echo esc_url(admin_url('admin.php?page=smart-schedular-appointments')); ?>" class="nav-tab nav-tab-active"><?php esc_html_e('List View', 'smart-schedular'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=smart-schedular-appointments&view=calendar')); ?>" class="nav-tab"><?php esc_html_e('Calendar View', 'smart-schedular'); ?></a>
        </div>
        
        <?php if ($appointments_query->have_posts()): ?>
            <div class="smart-schedular-filters">
                <form method="get">
                    <input type="hidden" name="page" value="smart-schedular-appointments">
                    <select name="status_filter">
                        <option value=""><?php esc_html_e('All Statuses', 'smart-schedular'); ?></option>
                        <option value="pending" <?php selected(isset($_GET['status_filter']) && $_GET['status_filter'] === 'pending'); ?>><?php esc_html_e('Pending', 'smart-schedular'); ?></option>
                        <option value="approved" <?php selected(isset($_GET['status_filter']) && $_GET['status_filter'] === 'approved'); ?>><?php esc_html_e('Approved', 'smart-schedular'); ?></option>
                        <option value="cancelled" <?php selected(isset($_GET['status_filter']) && $_GET['status_filter'] === 'cancelled'); ?>><?php esc_html_e('Cancelled', 'smart-schedular'); ?></option>
                    </select>
                    <select name="service_filter">
                        <option value=""><?php esc_html_e('All Services', 'smart-schedular'); ?></option>
                        <?php 
                        global $wpdb;
                        $services = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}smart_schedular_services ORDER BY name ASC");
                        
                        foreach ($services as $service) {
                            $selected = isset($_GET['service_filter']) && $_GET['service_filter'] == $service->id ? 'selected' : '';
                            echo '<option value="' . esc_attr($service->id) . '" ' . $selected . '>' . esc_html($service->name) . '</option>';
                        }
                        ?>
                    </select>
                    <input type="date" name="date_filter" value="<?php echo isset($_GET['date_filter']) ? esc_attr($_GET['date_filter']) : ''; ?>" placeholder="<?php esc_attr_e('Filter by date', 'smart-schedular'); ?>">
                    <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'smart-schedular'); ?>">
                    <?php if (isset($_GET['status_filter']) || isset($_GET['service_filter']) || isset($_GET['date_filter'])): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=smart-schedular-appointments')); ?>" class="button"><?php esc_html_e('Clear Filters', 'smart-schedular'); ?></a>
                    <?php endif; ?>
                </form>
            </div>
            
            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-title column-primary"><?php esc_html_e('Client', 'smart-schedular'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Service', 'smart-schedular'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Date', 'smart-schedular'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Time', 'smart-schedular'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Status', 'smart-schedular'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Actions', 'smart-schedular'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($appointments_query->have_posts()): $appointments_query->the_post(); 
                        $appointment_id = get_the_ID();
                        $service_id = get_post_meta($appointment_id, '_service_id', true);
                        $appointment_date = get_post_meta($appointment_id, '_appointment_date', true);
                        $appointment_time = get_post_meta($appointment_id, '_appointment_time', true);
                        $appointment_status = get_post_meta($appointment_id, '_appointment_status', true);
                        $customer_name = get_post_meta($appointment_id, '_customer_name', true);
                        $customer_email = get_post_meta($appointment_id, '_customer_email', true);
                        $customer_phone = get_post_meta($appointment_id, '_customer_phone', true);
                        
                        // Get service name from custom table
                        $service_name = '';
                        if ($service_id) {
                            $service = $wpdb->get_row($wpdb->prepare(
                                "SELECT * FROM {$wpdb->prefix}smart_schedular_services WHERE id = %d",
                                $service_id
                            ));
                            if ($service) {
                                $service_name = $service->name;
                            }
                        }
                        
                        // Format date and time
                        $formatted_date = !empty($appointment_date) ? date_i18n($options['date_format'], strtotime($appointment_date)) : 'N/A';
                        $formatted_time = !empty($appointment_time) ? date_i18n($options['time_format'], strtotime($appointment_time)) : 'N/A';
                        
                        // Status badge
                        $status_class = '';
                        switch ($appointment_status) {
                            case 'pending':
                                $status_class = 'status-pending';
                                break;
                            case 'approved':
                                $status_class = 'status-approved';
                                break;
                            case 'cancelled':
                                $status_class = 'status-cancelled';
                                break;
                            default:
                                $status_class = 'status-pending';
                                $appointment_status = 'pending';
                        }
                    ?>
                        <tr>
                            <td class="title column-title column-primary">
                                <strong><?php echo esc_html($customer_name); ?></strong>
                                <div class="customer-details">
                                    <span><?php echo esc_html($customer_email); ?></span><br>
                                    <span><?php echo esc_html($customer_phone); ?></span>
                                </div>
                            </td>
                            <td><?php echo esc_html($service_name); ?></td>
                            <td><?php echo esc_html($formatted_date); ?></td>
                            <td><?php echo esc_html($formatted_time); ?></td>
                            <td>
                                <span class="appointment-status <?php echo esc_attr($status_class); ?>">
                                    <?php echo esc_html(ucfirst($appointment_status)); ?>
                                </span>
                            </td>
                            <td class="actions">
                                <?php if ($appointment_status === 'pending'): ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=smart-schedular-appointments&action=approve&appointment_id=' . $appointment_id), 'smart_schedular_appointment_' . $appointment_id); ?>" class="button button-small button-primary"><?php esc_html_e('Approve', 'smart-schedular'); ?></a>
                                <?php endif; ?>
                                
                                <?php if ($appointment_status !== 'cancelled'): ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=smart-schedular-appointments&action=cancel&appointment_id=' . $appointment_id), 'smart_schedular_appointment_' . $appointment_id); ?>" class="button button-small"><?php esc_html_e('Cancel', 'smart-schedular'); ?></a>
                                <?php endif; ?>
                                
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=smart-schedular-appointments&action=delete&appointment_id=' . $appointment_id), 'smart_schedular_appointment_' . $appointment_id); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this appointment?', 'smart-schedular'); ?>');"><?php esc_html_e('Delete', 'smart-schedular'); ?></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <?php wp_reset_postdata(); ?>
            
        <?php else: ?>
            <div class="no-appointments-message">
                <p><?php esc_html_e('No appointments found.', 'smart-schedular'); ?></p>
            </div>
        <?php endif; ?>
        
    <?php endif; ?>
</div>

<style>
.appointment-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}
.status-pending {
    background-color: #ffecb3;
    color: #866500;
}
.status-approved {
    background-color: #d1e7dd;
    color: #0a6640;
}
.status-cancelled {
    background-color: #f8d7da;
    color: #842029;
}
.customer-details {
    color: #666;
    font-size: 12px;
    margin-top: 3px;
}
.smart-schedular-filters {
    margin: 15px 0;
    padding: 10px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 3px;
}
.smart-schedular-filters select,
.smart-schedular-filters input[type="date"] {
    margin-right: 10px;
}
.actions .button {
    margin-right: 5px;
}
.nav-tab-wrapper {
    margin-bottom: 15px;
}
</style> 