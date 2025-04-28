<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
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

// Get some stats
global $wpdb;
$appointments_table = $wpdb->prefix . 'smart_schedular_appointments';
$services_table = $wpdb->prefix . 'smart_schedular_services';

$pending_count = $wpdb->get_var("SELECT COUNT(*) FROM $appointments_table WHERE status = 'pending'");
$upcoming_count = $wpdb->get_var("SELECT COUNT(*) FROM $appointments_table WHERE status = 'confirmed' AND appointment_date >= CURDATE()");
$services_count = $wpdb->get_var("SELECT COUNT(*) FROM $services_table");

// Get recent appointments
$recent_appointments = $wpdb->get_results(
    "SELECT a.*, s.name as service_name 
    FROM $appointments_table a 
    LEFT JOIN $services_table s ON a.service_id = s.id 
    ORDER BY a.created_at DESC 
    LIMIT 5",
    ARRAY_A
);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="smart-schedular-dashboard">
        <div class="smart-schedular-stats">
            <div class="stat-box">
                <div class="stat-icon dashicons dashicons-clock"></div>
                <div class="stat-content">
                    <h3><?php echo intval($pending_count); ?></h3>
                    <p>Pending Appointments</p>
                </div>
                <a href="<?php echo admin_url('admin.php?page=smart-schedular-appointments&status=pending'); ?>" class="stat-link">View all</a>
            </div>
            
            <div class="stat-box">
                <div class="stat-icon dashicons dashicons-calendar-alt"></div>
                <div class="stat-content">
                    <h3><?php echo intval($upcoming_count); ?></h3>
                    <p>Upcoming Appointments</p>
                </div>
                <a href="<?php echo admin_url('admin.php?page=smart-schedular-appointments&status=confirmed'); ?>" class="stat-link">View all</a>
            </div>
            
            <div class="stat-box">
                <div class="stat-icon dashicons dashicons-admin-generic"></div>
                <div class="stat-content">
                    <h3><?php echo intval($services_count); ?></h3>
                    <p>Services</p>
                </div>
                <a href="<?php echo admin_url('admin.php?page=smart-schedular-services'); ?>" class="stat-link">Manage</a>
            </div>
        </div>
        
        <div class="smart-schedular-recent">
            <h2>Recent Appointments</h2>
            
            <?php if (empty($recent_appointments)) : ?>
                <p>No appointments found.</p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_appointments as $appointment) : ?>
                            <tr>
                                <td>
                                    <?php echo esc_html($appointment['customer_name']); ?><br>
                                    <small><?php echo esc_html($appointment['customer_email']); ?></small>
                                </td>
                                <td><?php echo esc_html($appointment['service_name']); ?></td>
                                <td>
                                    <?php 
                                    $date_obj = new DateTime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
                                    echo esc_html($date_obj->format(get_option('date_format'))); 
                                    ?><br>
                                    <small><?php echo esc_html($date_obj->format(get_option('time_format'))); ?></small>
                                </td>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    switch ($appointment['status']) {
                                        case 'pending':
                                            $status_class = 'status-pending';
                                            break;
                                        case 'confirmed':
                                            $status_class = 'status-confirmed';
                                            break;
                                        case 'declined':
                                            $status_class = 'status-declined';
                                            break;
                                    }
                                    ?>
                                    <span class="appointment-status <?php echo esc_attr($status_class); ?>">
                                        <?php echo esc_html(ucfirst($appointment['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=smart-schedular-appointments&view=' . $appointment['id']); ?>" class="button button-small">View</a>
                                    
                                    <?php if ($appointment['status'] === 'pending') : ?>
                                        <button class="button button-small approve-appointment" data-id="<?php echo intval($appointment['id']); ?>">Approve</button>
                                        <button class="button button-small decline-appointment" data-id="<?php echo intval($appointment['id']); ?>">Decline</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <p class="smart-schedular-actions">
                <a href="<?php echo admin_url('admin.php?page=smart-schedular-appointments'); ?>" class="button">View All Appointments</a>
                <a href="<?php echo admin_url('admin.php?page=smart-schedular-services'); ?>" class="button">Manage Services</a>
                <a href="<?php echo admin_url('admin.php?page=smart-schedular-settings'); ?>" class="button">Settings</a>
            </p>
        </div>
        
        <div class="smart-schedular-help">
            <h2>Quick Help</h2>
            <p>Welcome to Smart Schedular! Here's how to get started:</p>
            <ol>
                <li>Configure your services in the <a href="<?php echo admin_url('admin.php?page=smart-schedular-services'); ?>">Services</a> section.</li>
                <li>Add the booking form to any page using the shortcode: <code>[smart_schedular]</code></li>
                <li>For Google Calendar integration, set up your API credentials in <a href="<?php echo admin_url('admin.php?page=smart-schedular-settings'); ?>">Settings</a>.</li>
                <li>Manage your appointments in the <a href="<?php echo admin_url('admin.php?page=smart-schedular-appointments'); ?>">Appointments</a> section.</li>
            </ol>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Handle appointment approval
    $('.approve-appointment').on('click', function() {
        const appointmentId = $(this).data('id');
        
        if (confirm('Are you sure you want to approve this appointment?')) {
            $.ajax({
                url: smart_schedular.ajax_url,
                type: 'POST',
                data: {
                    action: 'smart_schedular_approve_appointment',
                    appointment_id: appointmentId,
                    nonce: smart_schedular.nonce
                },
                beforeSend: function() {
                    $(this).prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        alert('Appointment approved successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    $(this).prop('disabled', false);
                }
            });
        }
    });
    
    // Handle appointment decline
    $('.decline-appointment').on('click', function() {
        const appointmentId = $(this).data('id');
        
        if (confirm('Are you sure you want to decline this appointment?')) {
            $.ajax({
                url: smart_schedular.ajax_url,
                type: 'POST',
                data: {
                    action: 'smart_schedular_decline_appointment',
                    appointment_id: appointmentId,
                    nonce: smart_schedular.nonce
                },
                beforeSend: function() {
                    $(this).prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        alert('Appointment declined successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    $(this).prop('disabled', false);
                }
            });
        }
    });
});
</script> 