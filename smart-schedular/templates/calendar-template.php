<?php
/**
 * Calendar Template for Smart Schedular
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Smart_Schedular
 * @subpackage Smart_Schedular/templates
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get the color from service or use default
$service_color = isset($service['color']) ? esc_attr($service['color']) : '#1a73e8';
$service_font_family = isset($service['font_family']) ? esc_attr($service['font_family']) : 'system-ui, -apple-system, sans-serif';
?>

<style>
    .smart-schedular-container {
        font-family: <?php echo $service_font_family; ?>;
    }
    .smart-schedular-service-panel {
        background-color: <?php echo $service_color; ?>;
    }
    .smart-schedular-day.selected,
    .smart-schedular-day.today::after,
    .smart-schedular-time-slot.selected,
    .smart-schedular-button,
    .smart-schedular-spinner {
        background-color: <?php echo $service_color; ?>;
        border-color: <?php echo $service_color; ?>;
    }
    .smart-schedular-time-slot:hover,
    .smart-schedular-day.available:hover:not(.selected),
    .smart-schedular-back-button:hover {
        background-color: <?php echo esc_attr($this->adjustBrightness($service_color, 0.9)); ?>;
        border-color: <?php echo $service_color; ?>;
    }
    .smart-schedular-timezone-selector select,
    .smart-schedular-back-button {
        color: <?php echo $service_color; ?>;
        border-color: <?php echo $service_color; ?>;
    }
    .smart-schedular-button:hover {
        background-color: <?php echo esc_attr($this->adjustBrightness($service_color, -0.1)); ?>;
    }
</style>

<div class="smart-schedular-container" data-service-id="<?php echo esc_attr($service['id']); ?>">
    <!-- Left panel - Service info -->
    <div class="smart-schedular-service-panel">
        <?php if (!empty($service['logo_url'])): ?>
            <img src="<?php echo esc_url($service['logo_url']); ?>" alt="<?php echo isset($service['name']) ? esc_attr($service['name']) : ''; ?>" class="smart-schedular-service-logo">
        <?php endif; ?>
        
        <h2 class="smart-schedular-service-title"><?php echo isset($service['name']) ? esc_html($service['name']) : ''; ?></h2>
        
        <div class="smart-schedular-service-duration">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <?php echo isset($service['duration']) ? esc_html($service['duration']) : '0'; ?> min
        </div>
        
        <p class="smart-schedular-service-description"><?php echo isset($service['description']) ? esc_html($service['description']) : ''; ?></p>
    </div>
    
    <!-- Right panel - Calendar -->
    <div class="smart-schedular-calendar-panel">
        <!-- Calendar view -->
        <div class="smart-schedular-calendar-view">
            <h2 class="smart-schedular-calendar-title"><?php esc_html_e('Select a Date & Time', 'smart-schedular'); ?></h2>
            
            <!-- Calendar header -->
            <div class="smart-schedular-calendar-header">
                <div class="smart-schedular-month-title"></div>
                <div class="smart-schedular-nav-buttons">
                    <button type="button" class="smart-schedular-nav-button smart-schedular-prev-month" aria-label="<?php esc_attr_e('Previous month', 'smart-schedular'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </button>
                    <button type="button" class="smart-schedular-nav-button smart-schedular-next-month" aria-label="<?php esc_attr_e('Next month', 'smart-schedular'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                </div>
            </div>
            
            <!-- Calendar days -->
            <div class="smart-schedular-calendar-grid">
                <div class="smart-schedular-weekday"><?php esc_html_e('SUN', 'smart-schedular'); ?></div>
                <div class="smart-schedular-weekday"><?php esc_html_e('MON', 'smart-schedular'); ?></div>
                <div class="smart-schedular-weekday"><?php esc_html_e('TUE', 'smart-schedular'); ?></div>
                <div class="smart-schedular-weekday"><?php esc_html_e('WED', 'smart-schedular'); ?></div>
                <div class="smart-schedular-weekday"><?php esc_html_e('THU', 'smart-schedular'); ?></div>
                <div class="smart-schedular-weekday"><?php esc_html_e('FRI', 'smart-schedular'); ?></div>
                <div class="smart-schedular-weekday"><?php esc_html_e('SAT', 'smart-schedular'); ?></div>
                <!-- Calendar days will be inserted here via JavaScript -->
            </div>
            
            <!-- Selected date header -->
            <h3 class="smart-schedular-selected-date"><?php esc_html_e('Select a date', 'smart-schedular'); ?></h3>
            
            <!-- Time slots -->
            <div class="smart-schedular-time-slots">
                <!-- Time slots will be inserted here via JavaScript -->
            </div>
            
            <!-- Timezone selector -->
            <div class="smart-schedular-timezone-selector">
                <div class="smart-schedular-timezone-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                </div>
                <select class="smart-schedular-timezone-select">
                    <?php 
                        // Get all timezones
                        $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
                        foreach ($timezones as $timezone) {
                            echo '<option value="' . esc_attr($timezone) . '">' . esc_html($timezone) . '</option>';
                        }
                    ?>
                </select>
            </div>
            
            <!-- Continue button -->
            <div class="smart-schedular-buttons">
                <button type="button" class="smart-schedular-button smart-schedular-continue-button" disabled>
                    <?php esc_html_e('Continue', 'smart-schedular'); ?>
                </button>
            </div>
        </div>
        
        <!-- Booking form view -->
        <div class="smart-schedular-booking-form">
            <h2 class="smart-schedular-calendar-title"><?php esc_html_e('Enter Your Details', 'smart-schedular'); ?></h2>
            
            <!-- Appointment summary -->
            <div class="smart-schedular-appointment-summary">
                <p>
                    <strong><?php esc_html_e('Date:', 'smart-schedular'); ?></strong> 
                    <span class="smart-schedular-summary-date"></span>
                </p>
                <p>
                    <strong><?php esc_html_e('Time:', 'smart-schedular'); ?></strong> 
                    <span class="smart-schedular-summary-time"></span>
                </p>
                <p>
                    <strong><?php esc_html_e('Timezone:', 'smart-schedular'); ?></strong> 
                    <span class="smart-schedular-summary-timezone"></span>
                </p>
            </div>
            
            <!-- Booking form -->
            <form>
                <div class="smart-schedular-form-group">
                    <label for="customer_name"><?php esc_html_e('Your Name *', 'smart-schedular'); ?></label>
                    <input type="text" id="customer_name" name="customer_name" required>
                </div>
                
                <div class="smart-schedular-form-group">
                    <label for="customer_email"><?php esc_html_e('Your Email *', 'smart-schedular'); ?></label>
                    <input type="email" id="customer_email" name="customer_email" required>
                </div>
                
                <div class="smart-schedular-form-group">
                    <label for="customer_phone"><?php esc_html_e('Phone Number (optional)', 'smart-schedular'); ?></label>
                    <input type="tel" id="customer_phone" name="customer_phone">
                </div>
                
                <!-- Hidden fields -->
                <input type="hidden" id="appointment_date" name="appointment_date">
                <input type="hidden" id="appointment_time" name="appointment_time">
                <input type="hidden" id="appointment_timezone" name="appointment_timezone">
                
                <!-- Form buttons -->
                <div class="smart-schedular-buttons">
                    <button type="button" class="smart-schedular-back-button">
                        <?php esc_html_e('Back', 'smart-schedular'); ?>
                    </button>
                    <button type="submit" class="smart-schedular-button smart-schedular-booking-button">
                        <?php esc_html_e('Book Appointment', 'smart-schedular'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Success view -->
        <div class="smart-schedular-success-view" style="display: none;">
            <h2 class="smart-schedular-calendar-title"><?php esc_html_e('Booking Confirmed!', 'smart-schedular'); ?></h2>
            <p><?php esc_html_e('Your appointment has been booked successfully. You will receive a confirmation email shortly.', 'smart-schedular'); ?></p>
            <p><?php esc_html_e('After admin approval, you will receive another email with the meeting details.', 'smart-schedular'); ?></p>
            <button type="button" class="smart-schedular-button" onclick="location.reload()">
                <?php esc_html_e('Book Another Appointment', 'smart-schedular'); ?>
            </button>
        </div>
        
        <!-- Loader -->
        <div class="smart-schedular-loader">
            <div class="smart-schedular-spinner"></div>
            <p><?php esc_html_e('Loading...', 'smart-schedular'); ?></p>
        </div>
        
        <!-- Message -->
        <div class="smart-schedular-message"></div>
    </div>
</div> 