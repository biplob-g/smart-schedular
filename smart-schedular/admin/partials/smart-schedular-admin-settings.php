<?php
/**
 * Provide a admin area view for configuring plugin settings
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

// Get current settings
$options = get_option('smart_schedular_options', array(
    'admin_email' => get_option('admin_email'),
    'notification_subject' => 'New Appointment Booking',
    'notification_message' => "New appointment booking:\n\nService: {service}\nDate: {date}\nTime: {time}\nCustomer: {name}\nEmail: {email}\nPhone: {phone}",
    'confirmation_subject' => 'Appointment Confirmation',
    'confirmation_message' => "Thank you for booking an appointment with us!\n\nAppointment Details:\nService: {service}\nDate: {date}\nTime: {time}\n\nWe look forward to seeing you!"
));

// Save settings
if (isset($_POST['smart_schedular_save_settings']) && check_admin_referer('smart_schedular_settings_nonce')) {
    $options['admin_email'] = sanitize_email($_POST['admin_email']);
    $options['notification_subject'] = sanitize_text_field($_POST['notification_subject']);
    $options['notification_message'] = wp_kses_post($_POST['notification_message']);
    $options['confirmation_subject'] = sanitize_text_field($_POST['confirmation_subject']);
    $options['confirmation_message'] = wp_kses_post($_POST['confirmation_message']);
    
    update_option('smart_schedular_options', $options);
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully.', 'smart-schedular') . '</p></div>';
}

// Get available date formats
$date_formats = array(
    'F j, Y' => date_i18n('F j, Y'),
    'Y-m-d' => date_i18n('Y-m-d'),
    'm/d/Y' => date_i18n('m/d/Y'),
    'd/m/Y' => date_i18n('d/m/Y'),
    'j F Y' => date_i18n('j F Y'),
);

// Get available time formats
$time_formats = array(
    'g:i a' => date_i18n('g:i a'),
    'g:i A' => date_i18n('g:i A'),
    'H:i' => date_i18n('H:i'),
);

// Get time slot intervals
$time_intervals = array(
    15 => __('15 minutes', 'smart-schedular'),
    30 => __('30 minutes', 'smart-schedular'),
    45 => __('45 minutes', 'smart-schedular'),
    60 => __('1 hour', 'smart-schedular'),
    90 => __('1.5 hours', 'smart-schedular'),
    120 => __('2 hours', 'smart-schedular'),
);

// Get weekday names
$weekdays = array(
    0 => __('Sunday', 'smart-schedular'),
    1 => __('Monday', 'smart-schedular'),
    2 => __('Tuesday', 'smart-schedular'),
    3 => __('Wednesday', 'smart-schedular'),
    4 => __('Thursday', 'smart-schedular'),
    5 => __('Friday', 'smart-schedular'),
    6 => __('Saturday', 'smart-schedular'),
);

// Get active tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'notifications';
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <h2 class="nav-tab-wrapper">
        <a href="?page=smart-schedular-settings&tab=notifications" class="nav-tab <?php echo $active_tab === 'notifications' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Notifications', 'smart-schedular'); ?></a>
    </h2>
    
    <form method="post" action="">
        <?php wp_nonce_field('smart_schedular_settings_nonce'); ?>
        
        <div class="settings-container">
            <!-- Notification Settings -->
            <h2><?php esc_html_e('Email Notification Settings', 'smart-schedular'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="admin_email"><?php esc_html_e('Admin Email', 'smart-schedular'); ?></label></th>
                    <td>
                        <input type="email" id="admin_email" name="admin_email" class="regular-text" 
                               value="<?php echo esc_attr(isset($options['admin_email']) ? $options['admin_email'] : get_option('admin_email')); ?>" required>
                        <p class="description"><?php esc_html_e('Email address where admin notifications will be sent.', 'smart-schedular'); ?></p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e('Admin Notification', 'smart-schedular'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="notification_subject"><?php esc_html_e('Subject', 'smart-schedular'); ?></label></th>
                    <td>
                        <input type="text" id="notification_subject" name="notification_subject" class="regular-text" 
                               value="<?php echo esc_attr(isset($options['notification_subject']) ? $options['notification_subject'] : 'New Appointment Booking'); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="notification_message"><?php esc_html_e('Message', 'smart-schedular'); ?></label></th>
                    <td>
                        <textarea id="notification_message" name="notification_message" class="large-text" rows="5" required><?php 
                            echo esc_textarea(isset($options['notification_message']) ? $options['notification_message'] : "New appointment booking:\n\nService: {service}\nDate: {date}\nTime: {time}\nCustomer: {name}\nEmail: {email}\nPhone: {phone}"); 
                        ?></textarea>
                        <p class="description"><?php esc_html_e('Available placeholders: {service}, {date}, {time}, {name}, {email}, {phone}', 'smart-schedular'); ?></p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e('Customer Confirmation', 'smart-schedular'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="confirmation_subject"><?php esc_html_e('Subject', 'smart-schedular'); ?></label></th>
                    <td>
                        <input type="text" id="confirmation_subject" name="confirmation_subject" class="regular-text" 
                               value="<?php echo esc_attr(isset($options['confirmation_subject']) ? $options['confirmation_subject'] : 'Appointment Confirmation'); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="confirmation_message"><?php esc_html_e('Message', 'smart-schedular'); ?></label></th>
                    <td>
                        <textarea id="confirmation_message" name="confirmation_message" class="large-text" rows="5" required><?php 
                            echo esc_textarea(isset($options['confirmation_message']) ? $options['confirmation_message'] : "Thank you for booking an appointment with us!\n\nAppointment Details:\nService: {service}\nDate: {date}\nTime: {time}\n\nWe look forward to seeing you!"); 
                        ?></textarea>
                        <p class="description"><?php esc_html_e('Available placeholders: {service}, {date}, {time}, {name}, {email}, {phone}', 'smart-schedular'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <input type="submit" name="smart_schedular_save_settings" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'smart-schedular'); ?>">
        </p>
    </form>
</div>

<style>
.settings-container {
    margin-top: 20px;
}
</style> 