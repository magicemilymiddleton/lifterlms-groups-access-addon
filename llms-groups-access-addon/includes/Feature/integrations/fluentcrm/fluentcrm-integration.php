<?php
/**
 * FluentCRM Integration for LifterLMS Groups
 * 
 * Sends webhooks when users are added to groups as members or admins
 * 
 * @package LLMSGAA
 * @subpackage Integrations
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Webhook endpoints
define( 'LLMSGAA_FLUENTCRM_WEBHOOK_ADMIN', 'https://dc.zonesofregulation.com/?fluentcrm=1&route=contact&hash=6c8b371e-19d3-4063-9fc7-ab15ea58f512' );
define( 'LLMSGAA_FLUENTCRM_WEBHOOK_MEMBER', 'https://dc.zonesofregulation.com/?fluentcrm=1&route=contact&hash=2bfc64d9-3fec-40b0-bd8a-c3e932a215d3' );

// Hook into user creation and updates
add_action( 'user_register', 'llmsgaa_fluentcrm_check_new_user', 999 );
add_action( 'profile_update', 'llmsgaa_fluentcrm_check_user_update', 999 );
add_action( 'added_user_meta', 'llmsgaa_fluentcrm_check_meta_added', 999, 4 );
add_action( 'updated_user_meta', 'llmsgaa_fluentcrm_check_meta_updated', 999, 4 );

// Delayed check action
add_action( 'llmsgaa_delayed_fluentcrm_check', 'llmsgaa_fluentcrm_process_user' );

/**
 * Check new user registration
 */
function llmsgaa_fluentcrm_check_new_user( $user_id ) {
    error_log( "[FluentCRM] New user registered: {$user_id}" );
    
    // Schedule a delayed check (5 seconds) to catch group assignments that happen after user creation
    wp_schedule_single_event( time() + 5, 'llmsgaa_delayed_fluentcrm_check', [ $user_id ] );
    
    // Also check immediately in case groups are already assigned
    llmsgaa_fluentcrm_process_user( $user_id );
}

/**
 * Check user profile updates
 */
function llmsgaa_fluentcrm_check_user_update( $user_id ) {
    error_log( "[FluentCRM] User profile updated: {$user_id}" );
    llmsgaa_fluentcrm_process_user( $user_id );
}

/**
 * Check when user meta is added
 */
function llmsgaa_fluentcrm_check_meta_added( $meta_id, $user_id, $meta_key, $meta_value ) {
    if ( strpos( $meta_key, 'llms' ) !== false || strpos( $meta_key, 'group' ) !== false ) {
        error_log( "[FluentCRM] Meta added - User: {$user_id}, Key: {$meta_key}, Value: " . print_r( $meta_value, true ) );
    }
    
    if ( $meta_key === '_group_role' || strpos( $meta_key, 'group' ) !== false ) {
        llmsgaa_fluentcrm_process_user( $user_id );
    }
}

/**
 * Check when user meta is updated
 */
function llmsgaa_fluentcrm_check_meta_updated( $meta_id, $user_id, $meta_key, $meta_value ) {
    if ( $meta_key === '_group_role' || strpos( $meta_key, 'group' ) !== false ) {
        error_log( "[FluentCRM] Meta updated - User: {$user_id}, Key: {$meta_key}, Value: " . print_r( $meta_value, true ) );
        llmsgaa_fluentcrm_process_user( $user_id );
    }
}

/**
 * Process user and send webhooks if they're in groups
 */
function llmsgaa_fluentcrm_process_user( $user_id ) {
    global $wpdb;
    
    error_log( "[FluentCRM] Processing user {$user_id} for webhooks" );
    
    $user = get_user_by( 'id', $user_id );
    if ( ! $user ) {
        error_log( "[FluentCRM] User {$user_id} not found" );
        return;
    }
    
    // Check for group roles in LifterLMS user postmeta
    $group_roles = $wpdb->get_results( $wpdb->prepare(
        "SELECT post_id as group_id, meta_value as role 
         FROM {$wpdb->prefix}lifterlms_user_postmeta 
         WHERE user_id = %d 
         AND meta_key = '_group_role'
         AND meta_value IN ('admin', 'member')",
        $user_id
    ) );
    
    if ( empty( $group_roles ) ) {
        error_log( "[FluentCRM] No group roles found for user {$user_id}" );
        return;
    }
    
    error_log( "[FluentCRM] Found " . count( $group_roles ) . " group role(s) for user {$user_id}" );
    
    // Get webhook history
    $webhook_history = get_user_meta( $user_id, '_fluentcrm_webhook_history', true );
    if ( ! is_array( $webhook_history ) ) {
        $webhook_history = [];
    }
    
    foreach ( $group_roles as $group_role ) {
        $history_key = $group_role->group_id . '_' . $group_role->role;
        
        // Check if we've sent this webhook recently (within 24 hours)
        if ( isset( $webhook_history[ $history_key ] ) ) {
            $last_sent = $webhook_history[ $history_key ];
            if ( ( time() - $last_sent ) < DAY_IN_SECONDS ) {
                error_log( "[FluentCRM] Skipping webhook for {$history_key} - sent recently" );
                continue;
            }
        }
        
        // Send the webhook
        $sent = llmsgaa_fluentcrm_send_webhook( $user, $group_role->role, $group_role->group_id );
        
        if ( $sent ) {
            $webhook_history[ $history_key ] = time();
            update_user_meta( $user_id, '_fluentcrm_webhook_history', $webhook_history );
        }
    }
}

/**
 * Send webhook to FluentCRM
 */
function llmsgaa_fluentcrm_send_webhook( $user, $role = 'member', $group_id = null ) {
    $webhook_url = ( $role === 'admin' ) ? LLMSGAA_FLUENTCRM_WEBHOOK_ADMIN : LLMSGAA_FLUENTCRM_WEBHOOK_MEMBER;
    
    $data = [
        'email' => $user->user_email,
        'first_name' => $user->first_name ?: $user->display_name,
        'last_name' => $user->last_name ?: '',
        'tags' => [ $role === 'admin' ? 'group-admin' : 'group-member' ],
        'lists' => [ $role === 'admin' ? 'group-admins' : 'group-members' ],
        'status' => 'subscribed'
    ];
    
    error_log( "[FluentCRM] Sending {$role} webhook for {$user->user_email} (group {$group_id})" );
    
    $response = wp_remote_post( $webhook_url, [
        'method' => 'POST',
        'timeout' => 30,
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
        'body' => wp_json_encode( $data ),
    ] );
    
    if ( is_wp_error( $response ) ) {
        error_log( "[FluentCRM] ❌ Webhook failed: " . $response->get_error_message() );
        return false;
    }
    
    $code = wp_remote_retrieve_response_code( $response );
    
    if ( $code >= 200 && $code < 300 ) {
        error_log( "[FluentCRM] ✅ Webhook sent successfully" );
        return true;
    } else {
        error_log( "[FluentCRM] ⚠️ Webhook returned code {$code}" );
        return false;
    }
}

/**
 * Manual sync function
 */
function llmsgaa_manual_fluentcrm_sync( $user_id ) {
    error_log( "[FluentCRM] Manual sync triggered for user {$user_id}" );
    llmsgaa_fluentcrm_process_user( $user_id );
}

/**
 * Admin notice for manual sync
 */
add_action( 'admin_notices', function() {
    if ( isset( $_GET['fluentcrm_sync'] ) && current_user_can( 'manage_options' ) ) {
        global $wpdb;
        
        $users = $wpdb->get_col( "
            SELECT DISTINCT user_id 
            FROM {$wpdb->prefix}lifterlms_user_postmeta 
            WHERE meta_key = '_group_role' 
            AND meta_value IN ('admin', 'member')
        " );
        
        foreach ( $users as $user_id ) {
            llmsgaa_fluentcrm_process_user( $user_id );
        }
        
        echo '<div class="notice notice-success"><p>FluentCRM sync completed for ' . count( $users ) . ' users. Check error log for details.</p></div>';
    }
} );

// Log when integration loads
error_log( "[FluentCRM] Integration loaded - hooks registered" );





add_action( 'wp_login', function( $user_login, $user ) {
    error_log( "[FluentCRM-Force] User logged in: {$user->ID} ({$user_login})" );
    
    // Give it a moment for any other processes
    wp_schedule_single_event( time() + 2, 'llmsgaa_force_fluentcrm_check', [ $user->ID ] );
}, 10, 2 );

// Force check action
add_action( 'llmsgaa_force_fluentcrm_check', function( $user_id ) {
    global $wpdb;
    
    error_log( "[FluentCRM-Force] Checking user {$user_id}" );
    
    // Get ALL group memberships
    $memberships = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}lifterlms_user_postmeta 
         WHERE user_id = %d AND meta_key = '_group_role'",
        $user_id
    ) );
    
    if ( empty( $memberships ) ) {
        error_log( "[FluentCRM-Force] No memberships found" );
        return;
    }
    
    foreach ( $memberships as $membership ) {
        error_log( "[FluentCRM-Force] Found membership: Group {$membership->post_id}, Role {$membership->meta_value}" );
        
        // Check if webhook was already sent
        $webhook_key = "fluentcrm_sent_{$membership->post_id}_{$membership->meta_value}";
        $last_sent = get_user_meta( $user_id, $webhook_key, true );
        
        if ( $last_sent ) {
            error_log( "[FluentCRM-Force] Webhook already sent on {$last_sent}" );
            continue;
        }
        
        // Send webhook
        $user = get_user_by( 'id', $user_id );
        if ( $user ) {
            $sent = llmsgaa_fluentcrm_send_webhook( $user, $membership->meta_value, $membership->post_id );
            if ( $sent ) {
                update_user_meta( $user_id, $webhook_key, current_time( 'mysql' ) );
                error_log( "[FluentCRM-Force] ✅ Webhook sent!" );
            }
        }
    }
} );

// Also check when accessing admin
add_action( 'admin_init', function() {
    if ( ! is_user_logged_in() ) {
        return;
    }
    
    $user_id = get_current_user_id();
    $last_check = get_transient( 'fluentcrm_admin_check_' . $user_id );
    
    if ( ! $last_check ) {
        // Check once per hour
        set_transient( 'fluentcrm_admin_check_' . $user_id, true, HOUR_IN_SECONDS );
        
        error_log( "[FluentCRM-Force] Admin check for user {$user_id}" );
        do_action( 'llmsgaa_force_fluentcrm_check', $user_id );
    }
} );

// Cron job to check all users
add_action( 'init', function() {
    if ( ! wp_next_scheduled( 'llmsgaa_fluentcrm_daily_sync' ) ) {
        wp_schedule_event( time(), 'daily', 'llmsgaa_fluentcrm_daily_sync' );
    }
} );

add_action( 'llmsgaa_fluentcrm_daily_sync', function() {
    global $wpdb;
    
    error_log( "[FluentCRM-Cron] Starting daily sync" );
    
    // Get all users with group roles
    $users = $wpdb->get_col( "
        SELECT DISTINCT user_id 
        FROM {$wpdb->prefix}lifterlms_user_postmeta 
        WHERE meta_key = '_group_role' 
        AND meta_value IN ('admin', 'member')
        LIMIT 100
    " );
    
    foreach ( $users as $user_id ) {
        do_action( 'llmsgaa_force_fluentcrm_check', $user_id );
    }
    
    error_log( "[FluentCRM-Cron] Daily sync complete for " . count( $users ) . " users" );
} );