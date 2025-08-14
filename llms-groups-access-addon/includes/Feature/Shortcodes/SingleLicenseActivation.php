<?php
/**
 * Single License Self-Activation Shortcode
 * 
 * Provides a streamlined activation process for users who purchased a single license.
 * This shortcode displays a button that triggers a popup wizard for self-activation
 * or gifting to someone else.
 * 
 * Usage: [llmsgaa_single_license_activation]
 * 
 * @package LLMSGAA\Feature\Shortcodes
 */

namespace LLMSGAA\Feature\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SingleLicenseActivation {

    /**
     * Initialize the shortcode and hooks
     */
    public static function init() {
        add_shortcode( 'llmsgaa_single_license_activation', [ __CLASS__, 'render_shortcode' ] );
        
        // AJAX handlers
        add_action( 'wp_ajax_llmsgaa_activate_single_license', [ __CLASS__, 'ajax_activate_license' ] );
        add_action( 'wp_ajax_nopriv_llmsgaa_activate_single_license', [ __CLASS__, 'ajax_activate_license' ] );
        
        add_action( 'wp_ajax_llmsgaa_gift_single_license', [ __CLASS__, 'ajax_gift_license' ] );
        add_action( 'wp_ajax_nopriv_llmsgaa_gift_single_license', [ __CLASS__, 'ajax_gift_license' ] );
        
        // Enqueue scripts
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );
    }

    /**
     * Check if user has a single unredeemed license
     * 
     * @param int $user_id User ID to check
     * @return array|false Returns pass data if eligible, false otherwise
     */
    private static function get_eligible_pass( $user_id ) {
        $user = get_user_by( 'ID', $user_id );
        if ( ! $user ) {
            return false;
        }

        // Get all access passes for this user's email
        $passes = get_posts([
            'post_type'      => 'llms_access_pass',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => 'buyer_id',
                    'value'   => $user->user_email,
                    'compare' => '='
                ]
            ]
        ]);

        foreach ( $passes as $pass ) {
            // Check if redeemed - treat empty/null as not redeemed
            $is_redeemed = get_post_meta( $pass->ID, 'llmsgaa_redeemed', true );
            
            // Skip if already redeemed (explicitly set to '1')
            if ( $is_redeemed === '1' ) {
                continue;
            }

            // Get pass items
            $items = get_post_meta( $pass->ID, 'llmsgaa_pass_items', true );
            if ( is_string( $items ) ) {
                $items = json_decode( $items, true );
            }

            // Check if this is a single-item purchase
            if ( is_array( $items ) && count( $items ) === 1 ) {
                $item = reset( $items );
                
                // Check if quantity is 1
                if ( isset( $item['quantity'] ) && $item['quantity'] == 1 ) {
                    // Get the group ID for this pass
                    $group_id = get_post_meta( $pass->ID, 'group_id', true );
                    
                    // Get product information
                    $product_id = null;
                    // Try to get product ID from SKU - adjust this based on your actual implementation
                    if ( function_exists( '\LLMSGAA\Common\Utils::sku_to_product_id' ) ) {
                        $product_id = \LLMSGAA\Common\Utils::sku_to_product_id( $item['sku'] );
                    }
                    $product_title = $product_id ? get_the_title( $product_id ) : $item['sku'];
                    
                    return [
                        'pass_id'       => $pass->ID,
                        'pass_title'    => $pass->post_title,
                        'group_id'      => $group_id,
                        'group_title'   => $group_id ? get_the_title( $group_id ) : '',
                        'sku'           => $item['sku'],
                        'product_id'    => $product_id,
                        'product_title' => $product_title,
                        'buyer_email'   => $user->user_email
                    ];
                }
            }
        }

        return false;
    }

    /**
     * Render the shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public static function render_shortcode( $atts ) {
        // Parse attributes
        $atts = shortcode_atts([
            'button_text'    => 'Activate Your License',
            'button_class'   => 'llmsgaa-activate-button',
            'show_always'    => 'false',
            'message'        => 'You have an unused license ready to activate!',
            'hide_if_active' => 'true'
        ], $atts );

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            return '';
        }

        $user_id = get_current_user_id();
        $pass_data = self::get_eligible_pass( $user_id );

        // If no eligible pass and not showing always, return empty
        if ( ! $pass_data && $atts['show_always'] !== 'true' ) {
            return '';
        }

        // If hide_if_active is true, check if user already has active access
        if ( $atts['hide_if_active'] === 'true' && $pass_data ) {
            // Check if user already has active orders for this group
            $has_active = self::user_has_active_access( $user_id, $pass_data['group_id'] );
            if ( $has_active ) {
                return '';
            }
        }

        // Build the output
        ob_start();
        
        // Only render if we have eligible pass data
        if ( ! $pass_data ) {
            return '';
        }
        ?>
        <div class="llmsgaa-single-license-activation" data-pass-data='<?php echo esc_attr( json_encode( $pass_data ) ); ?>'>
            <div class="llmsgaa-activation-notice">
                <p><?php echo esc_html( $atts['message'] ); ?></p>
                <button type="button" 
                        class="<?php echo esc_attr( $atts['button_class'] ); ?>" 
                        id="llmsgaa-activate-license-btn"
                        data-pass-id="<?php echo esc_attr( $pass_data['pass_id'] ); ?>">
                    <?php echo esc_html( $atts['button_text'] ); ?>
                </button>
            </div>
        </div>

        <!-- Activation Wizard Modal -->

        <!-- Activation Wizard Modal -->
        <div id="llmsgaa-activation-modal" class="llmsgaa-modal" style="display: none;">
            <div class="llmsgaa-modal-overlay"></div>
            <div class="llmsgaa-modal-content">
                <button class="llmsgaa-modal-close">&times;</button>
                
                <div id="llmsgaa-wizard-step-1" class="llmsgaa-wizard-step">
                    <h2>Activate Your License</h2>
                    <p>You purchased: <strong><span class="product-title"></span></strong></p>
                    <p>Is this license for you or would you like to gift it to someone else?</p>
                    
                    <div class="llmsgaa-wizard-options">
                        <button type="button" class="llmsgaa-wizard-option" data-choice="self">
                            <span class="option-icon">👤</span>
                            <span class="option-text">
                                <strong>For Me</strong>
                                <small>I'll use this license myself</small>
                            </span>
                        </button>
                        
                        <button type="button" class="llmsgaa-wizard-option" data-choice="gift">
                            <span class="option-icon">🎁</span>
                            <span class="option-text">
                                <strong>Gift to Someone</strong>
                                <small>Send this license to another person</small>
                            </span>
                        </button>
                    </div>
                </div>

                <div id="llmsgaa-wizard-step-2-self" class="llmsgaa-wizard-step" style="display: none;">
                    <h2>Choose Your Start Date</h2>
                    <p>When would you like your access to begin?</p>
                    
                    <form id="llmsgaa-self-activation-form">
                        <div class="llmsgaa-form-group">
                            <label for="llmsgaa-start-date">Start Date:</label>
                            <input type="date" 
                                   id="llmsgaa-start-date" 
                                   name="start_date" 
                                   min="<?php echo date('Y-m-d'); ?>" 
                                   value="<?php echo date('Y-m-d'); ?>" 
                                   required>
                            <small class="llmsgaa-help-text">Your access will begin on this date</small>
                        </div>
                        
                        <div class="llmsgaa-wizard-actions">
                            <button type="button" class="llmsgaa-btn-secondary llmsgaa-wizard-back">Back</button>
                            <button type="submit" class="llmsgaa-btn-primary">Activate Now</button>
                        </div>
                    </form>
                </div>

                <div id="llmsgaa-wizard-step-2-gift" class="llmsgaa-wizard-step" style="display: none;">
                    <h2>Gift Your License</h2>
                    <p>Enter the email address of the person you'd like to gift this license to.</p>
                    
                    <form id="llmsgaa-gift-form">
                        <div class="llmsgaa-form-group">
                            <label for="llmsgaa-recipient-email">Recipient's Email:</label>
                            <input type="email" 
                                   id="llmsgaa-recipient-email" 
                                   name="recipient_email" 
                                   placeholder="recipient@example.com"
                                   required>
                            <small class="llmsgaa-help-text">They will receive an invitation to activate their access</small>
                        </div>

                        <div class="llmsgaa-form-group">
                            <label for="llmsgaa-recipient-message">Personal Message (Optional):</label>
                            <textarea id="llmsgaa-recipient-message" 
                                      name="personal_message" 
                                      rows="3" 
                                      placeholder="Add a personal message to include with the gift..."></textarea>
                        </div>
                        
                        <div class="llmsgaa-wizard-actions">
                            <button type="button" class="llmsgaa-btn-secondary llmsgaa-wizard-back">Back</button>
                            <button type="submit" class="llmsgaa-btn-primary">Send Gift</button>
                        </div>
                    </form>
                </div>

                <div id="llmsgaa-wizard-success" class="llmsgaa-wizard-step" style="display: none;">
                    <div class="llmsgaa-success-icon">✓</div>
                    <h2>Success!</h2>
                    <p class="llmsgaa-success-message"></p>
                    <button type="button" class="llmsgaa-btn-primary llmsgaa-modal-close-success">Done</button>
                </div>

                <div id="llmsgaa-wizard-loading" class="llmsgaa-wizard-step" style="display: none;">
                    <div class="llmsgaa-spinner"></div>
                    <p>Processing your request...</p>
                </div>
            </div>
        </div>

        <style>
        .llmsgaa-single-license-activation {
            margin: 20px 0;
        }

        .llmsgaa-activation-notice {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .llmsgaa-activation-notice p {
            margin: 0 0 15px 0;
            font-size: 16px;
        }

        .llmsgaa-activate-button {
            background: white;
            color: #667eea;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .llmsgaa-activate-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        /* Modal Styles */
        .llmsgaa-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 999999;
        }

        .llmsgaa-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
        }

        .llmsgaa-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .llmsgaa-modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .llmsgaa-modal-close:hover {
            background: #f5f5f5;
            color: #333;
        }

        .llmsgaa-wizard-step {
            padding: 40px 30px;
        }

        .llmsgaa-wizard-step h2 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 24px;
        }

        .llmsgaa-wizard-step p {
            color: #666;
            margin: 0 0 20px 0;
        }

        .llmsgaa-wizard-options {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .llmsgaa-wizard-option {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .llmsgaa-wizard-option:hover {
            border-color: #667eea;
            background: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .option-icon {
            font-size: 32px;
        }

        .option-text {
            text-align: left;
        }

        .option-text strong {
            display: block;
            color: #333;
            margin-bottom: 4px;
        }

        .option-text small {
            color: #666;
            font-size: 13px;
        }

        .llmsgaa-form-group {
            margin-bottom: 20px;
        }

        .llmsgaa-form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .llmsgaa-form-group input,
        .llmsgaa-form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .llmsgaa-form-group input:focus,
        .llmsgaa-form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .llmsgaa-help-text {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 13px;
        }

        .llmsgaa-wizard-actions {
            display: flex;
            gap: 10px;
            justify-content: space-between;
            margin-top: 30px;
        }

        .llmsgaa-btn-primary,
        .llmsgaa-btn-secondary {
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .llmsgaa-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .llmsgaa-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .llmsgaa-btn-secondary {
            background: #f5f5f5;
            color: #666;
        }

        .llmsgaa-btn-secondary:hover {
            background: #e9e9e9;
        }

        .llmsgaa-success-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            color: white;
        }

        .llmsgaa-success-message {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
        }

        .llmsgaa-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: llmsgaa-spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes llmsgaa-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        #llmsgaa-wizard-loading {
            text-align: center;
        }

        .llmsgaa-modal-close-success {
            margin: 0 auto;
            display: block;
        }
        </style>
        
        <script>
        // Inline JavaScript as fallback/primary handler
        jQuery(document).ready(function($) {
            console.log('Single License Activation Script Loaded');
            
            const passData = <?php echo json_encode( $pass_data ); ?>;
            const ajaxUrl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
            const nonce = '<?php echo wp_create_nonce( 'llmsgaa_single_activation' ); ?>';
            
            // Open modal handler
            $('#llmsgaa-activate-license-btn').on('click', function(e) {
                e.preventDefault();
                console.log('Activate button clicked');
                $('#llmsgaa-activation-modal').fadeIn(300);
                $('body').css('overflow', 'hidden');
                $('.product-title').text(passData.product_title || 'Your License');
                $('#llmsgaa-wizard-step-1').show();
                $('#llmsgaa-wizard-step-2-self, #llmsgaa-wizard-step-2-gift, #llmsgaa-wizard-success, #llmsgaa-wizard-loading').hide();
            });
            
            // Close modal handlers
            $('.llmsgaa-modal-close, .llmsgaa-modal-close-success').on('click', function(e) {
                e.preventDefault();
                $('#llmsgaa-activation-modal').fadeOut(300);
                $('body').css('overflow', '');
            });
            
            // Click outside to close
            $('#llmsgaa-activation-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).fadeOut(300);
                    $('body').css('overflow', '');
                }
            });
            
            // Choice buttons
            $('.llmsgaa-wizard-option').on('click', function() {
                const choice = $(this).data('choice');
                $('#llmsgaa-wizard-step-1').hide();
                
                if (choice === 'self') {
                    $('#llmsgaa-wizard-step-2-self').fadeIn(300);
                } else if (choice === 'gift') {
                    $('#llmsgaa-wizard-step-2-gift').fadeIn(300);
                }
            });
            
            // Back buttons
            $('.llmsgaa-wizard-back').on('click', function(e) {
                e.preventDefault();
                $('.llmsgaa-wizard-step').hide();
                $('#llmsgaa-wizard-step-1').fadeIn(300);
            });
            
            // Self activation form
            $('#llmsgaa-self-activation-form').on('submit', function(e) {
                e.preventDefault();
                
                const startDate = $('#llmsgaa-start-date').val();
                if (!startDate) {
                    alert('Please select a start date');
                    return;
                }
                
                $('.llmsgaa-wizard-step').hide();
                $('#llmsgaa-wizard-loading').show();
                
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'llmsgaa_activate_single_license',
                        nonce: nonce,
                        pass_id: passData.pass_id,
                        start_date: startDate
                    },
                    success: function(response) {
                        $('#llmsgaa-wizard-loading').hide();
                        if (response.success) {
                            $('.llmsgaa-success-message').text(response.data.message);
                            $('#llmsgaa-wizard-success').show();
                            $('.llmsgaa-single-license-activation').fadeOut();
                            
                            if (response.data.redirect) {
                                setTimeout(function() {
                                    window.location.href = response.data.redirect;
                                }, 3000);
                            }
                        } else {
                            alert('Error: ' + (response.data || 'Failed to activate license'));
                            $('#llmsgaa-wizard-step-2-self').show();
                        }
                    },
                    error: function() {
                        $('#llmsgaa-wizard-loading').hide();
                        alert('Network error. Please try again.');
                        $('#llmsgaa-wizard-step-2-self').show();
                    }
                });
            });
            
            // Gift form
            $('#llmsgaa-gift-form').on('submit', function(e) {
                e.preventDefault();
                
                const recipientEmail = $('#llmsgaa-recipient-email').val();
                const personalMessage = $('#llmsgaa-recipient-message').val();
                
                if (!recipientEmail) {
                    alert('Please enter recipient email');
                    return;
                }
                
                $('.llmsgaa-wizard-step').hide();
                $('#llmsgaa-wizard-loading').show();
                
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'llmsgaa_gift_single_license',
                        nonce: nonce,
                        pass_id: passData.pass_id,
                        recipient_email: recipientEmail,
                        personal_message: personalMessage
                    },
                    success: function(response) {
                        $('#llmsgaa-wizard-loading').hide();
                        if (response.success) {
                            $('.llmsgaa-success-message').text(response.data.message);
                            $('#llmsgaa-wizard-success').show();
                            $('.llmsgaa-single-license-activation').fadeOut();
                        } else {
                            alert('Error: ' + (response.data || 'Failed to send gift'));
                            $('#llmsgaa-wizard-step-2-gift').show();
                        }
                    },
                    error: function() {
                        $('#llmsgaa-wizard-loading').hide();
                        alert('Network error. Please try again.');
                        $('#llmsgaa-wizard-step-2-gift').show();
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Check if user has active access to a group
     * 
     * @param int $user_id User ID
     * @param int $group_id Group ID
     * @return bool
     */
    private static function user_has_active_access( $user_id, $group_id ) {
        $orders = get_posts([
            'post_type'      => 'llms_group_order',
            'posts_per_page' => 1,
            'meta_query'     => [
                [
                    'key'     => 'student_id',
                    'value'   => $user_id,
                    'compare' => '='
                ],
                [
                    'key'     => 'group_id',
                    'value'   => $group_id,
                    'compare' => '='
                ],
                [
                    'key'     => 'status',
                    'value'   => 'active',
                    'compare' => '='
                ]
            ]
        ]);

        return ! empty( $orders );
    }

    /**
     * AJAX handler for self-activation
     */
    public static function ajax_activate_license() {
        // Verify nonce
        if ( ! check_ajax_referer( 'llmsgaa_single_activation', 'nonce', false ) ) {
            wp_send_json_error( 'Invalid security token' );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'You must be logged in to activate a license' );
        }

        $user_id = get_current_user_id();
        $pass_id = intval( $_POST['pass_id'] ?? 0 );
        $start_date = sanitize_text_field( $_POST['start_date'] ?? date('Y-m-d') );

        if ( ! $pass_id ) {
            wp_send_json_error( 'Invalid license ID' );
        }

        // Verify this pass belongs to the user
        $pass_data = self::get_eligible_pass( $user_id );
        if ( ! $pass_data || $pass_data['pass_id'] != $pass_id ) {
            wp_send_json_error( 'This license is not available for activation' );
        }

        // Create the group order for the user
        $user = get_user_by( 'ID', $user_id );
        
        // Get product ID if possible
        $product_id = null;
        if ( function_exists( '\LLMSGAA\Common\Utils::sku_to_product_id' ) && ! empty( $pass_data['sku'] ) ) {
            $product_id = \LLMSGAA\Common\Utils::sku_to_product_id( $pass_data['sku'] );
        }
        
        $order_id = wp_insert_post([
            'post_type'   => 'llms_group_order',
            'post_status' => 'publish',
            'post_title'  => sprintf( 'Self-Activated Order - %s', $user->user_email ),
            'meta_input'  => [
                'group_id'      => $pass_data['group_id'],
                'product_id'    => $product_id ?: $pass_data['product_id'],
                'student_id'    => $user_id,
                'student_email' => $user->user_email,
                'start_date'    => $start_date,
                'end_date'      => date( 'Y-m-d', strtotime( '+1 year', strtotime( $start_date ) ) ),
                'status'        => 'active',
                'has_accepted_invite' => '1',
                'seat_id'       => $pass_id,
            ],
        ]);

        if ( ! $order_id ) {
            wp_send_json_error( 'Failed to create order' );
        }

        // Mark the pass as redeemed
        update_post_meta( $pass_id, 'llmsgaa_redeemed', '1' );

        // If user is not already a member of the group, add them
        global $wpdb;
        $existing_role = $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}lifterlms_user_postmeta 
             WHERE user_id = %d AND post_id = %d AND meta_key = '_group_role'",
            $user_id,
            $pass_data['group_id']
        ) );

        if ( ! $existing_role ) {
            // Add user as a member of the group
            $wpdb->insert(
                $wpdb->prefix . 'lifterlms_user_postmeta',
                [
                    'user_id'    => $user_id,
                    'post_id'    => $pass_data['group_id'],
                    'meta_key'   => '_group_role',
                    'meta_value' => 'member',
                    'updated_date' => current_time( 'mysql' )
                ]
            );

            // Set status as enrolled
            $wpdb->insert(
                $wpdb->prefix . 'lifterlms_user_postmeta',
                [
                    'user_id'    => $user_id,
                    'post_id'    => $pass_data['group_id'],
                    'meta_key'   => '_status',
                    'meta_value' => 'enrolled',
                    'updated_date' => current_time( 'mysql' )
                ]
            );
        }

        wp_send_json_success([
            'message' => 'Your license has been activated successfully! Your access begins on ' . $start_date . '.',
            'redirect' => get_permalink( $pass_data['group_id'] )
        ]);
    }

    /**
     * AJAX handler for gifting a license
     */
    public static function ajax_gift_license() {
        // Verify nonce
        if ( ! check_ajax_referer( 'llmsgaa_single_activation', 'nonce', false ) ) {
            wp_send_json_error( 'Invalid security token' );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'You must be logged in to gift a license' );
        }

        $user_id = get_current_user_id();
        $pass_id = intval( $_POST['pass_id'] ?? 0 );
        $recipient_email = sanitize_email( $_POST['recipient_email'] ?? '' );
        $personal_message = sanitize_textarea_field( $_POST['personal_message'] ?? '' );

        if ( ! $pass_id || ! is_email( $recipient_email ) ) {
            wp_send_json_error( 'Invalid data provided' );
        }

        // Verify this pass belongs to the user
        $pass_data = self::get_eligible_pass( $user_id );
        if ( ! $pass_data || $pass_data['pass_id'] != $pass_id ) {
            wp_send_json_error( 'This license is not available for gifting' );
        }

        // Check if recipient already exists as a user
        $recipient_user = get_user_by( 'email', $recipient_email );
        
        // Get product ID if possible
        $product_id = null;
        if ( function_exists( '\LLMSGAA\Common\Utils::sku_to_product_id' ) && ! empty( $pass_data['sku'] ) ) {
            $product_id = \LLMSGAA\Common\Utils::sku_to_product_id( $pass_data['sku'] );
        }

        // Create a pending group order
        $order_id = wp_insert_post([
            'post_type'   => 'llms_group_order',
            'post_status' => 'publish',
            'post_title'  => sprintf( 'Gifted Order - %s', $recipient_email ),
            'meta_input'  => [
                'group_id'      => $pass_data['group_id'],
                'product_id'    => $product_id ?: $pass_data['product_id'],
                'student_id'    => $recipient_user ? $recipient_user->ID : null,
                'student_email' => $recipient_email,
                'start_date'    => null, // Will be set when recipient activates
                'status'        => 'pending',
                'has_accepted_invite' => '0',
                'seat_id'       => $pass_id,
                'gifted_by'     => $user_id,
                'gift_message'  => $personal_message,
            ],
        ]);

        if ( ! $order_id ) {
            wp_send_json_error( 'Failed to create gift order' );
        }

        // Create invitation link
        $nonce = wp_create_nonce( "llmsgaa_consent_{$order_id}" );
        $invite_link = home_url( "/group-consent/{$order_id}/?nonce={$nonce}" );

        // Send invitation email
        $sender = get_user_by( 'ID', $user_id );
        $subject = sprintf( 
            '%s has gifted you access to %s', 
            $sender->display_name ?: $sender->user_login,
            $pass_data['product_title']
        );

        $message = sprintf(
            "Hello!\n\n%s has gifted you access to %s.\n\n",
            $sender->display_name ?: $sender->user_login,
            $pass_data['product_title']
        );

        if ( ! empty( $personal_message ) ) {
            $message .= "Personal message:\n\"" . $personal_message . "\"\n\n";
        }

        $message .= "Click the link below to activate your access:\n";
        $message .= $invite_link . "\n\n";
        $message .= "If you have any questions, please don't hesitate to contact us.\n\n";
        $message .= "Best regards,\n" . get_bloginfo( 'name' );

        $email_sent = wp_mail( $recipient_email, $subject, $message );

        if ( ! $email_sent ) {
            // Try to send with simpler message if first attempt failed
            $simple_message = sprintf(
                "You've been gifted access to %s. Activate here: %s",
                $pass_data['product_title'],
                $invite_link
            );
            $email_sent = wp_mail( $recipient_email, $subject, $simple_message );
        }

        // Mark the pass as redeemed
        update_post_meta( $pass_id, 'llmsgaa_redeemed', '1' );

        // Store invitation in the group invitations table if recipient is not yet a user
        if ( ! $recipient_user ) {
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'lifterlms_group_invitations',
                [
                    'group_id'    => $pass_data['group_id'],
                    'email'       => $recipient_email,
                    'role'        => 'member',
                    'invite_key'  => wp_generate_password( 20, false ),
                    'created'     => current_time( 'mysql' ),
                ]
            );
        }

        wp_send_json_success([
            'message' => sprintf(
                'Your license has been gifted to %s. They will receive an email with instructions to activate their access.',
                $recipient_email
            )
        ]);
    }

    /**
     * Enqueue scripts directly when shortcode is rendered
     */
    private static function enqueue_activation_scripts() {
        // Fix the path to match your plugin structure
        $plugin_url = plugins_url( '/', dirname( dirname( dirname( __FILE__ ) ) ) );
        
        wp_enqueue_script(
            'llmsgaa-single-activation',
            $plugin_url . 'public/js/single-activation.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script( 'llmsgaa-single-activation', 'llmsgaa_single', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'llmsgaa_single_activation' ),
        ]);
    }

    /**
     * Enqueue necessary scripts
     */
    public static function enqueue_scripts() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        // Check if shortcode is present on the page
        global $post;
        if ( ! $post || ! has_shortcode( $post->post_content, 'llmsgaa_single_license_activation' ) ) {
            return;
        }

        self::enqueue_activation_scripts();
    }
}

// Initialize the class
SingleLicenseActivation::init();