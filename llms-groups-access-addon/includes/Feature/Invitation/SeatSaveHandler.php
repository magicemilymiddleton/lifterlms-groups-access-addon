<?php
namespace LLMSGAA\Feature\Invitation;

use LLMSGAA\Feature\Invitation\InviteService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SeatSaveHandler {

    /**
     * Register the seat save handler on both logged-in and not-logged-in actions.
     */
    public static function init_hooks() {
        add_action( 'admin_post_llmsgaa_save_group_seats',     [ __CLASS__, 'handle_group_seat_save' ] );
        add_action( 'admin_post_nopriv_llmsgaa_save_group_seats', [ __CLASS__, 'handle_group_seat_save' ] );
        add_action( 'admin_post_llmsgaa_cancel_invite',        [ __CLASS__, 'handle_cancel_invite' ] );
    add_action( 'admin_post_nopriv_llmsgaa_cancel_invite', [ __CLASS__, 'handle_cancel_invite' ] );

    }

    /**
     * Handle the save_group_seats form submission.
     * Activates existing group members immediately or sends invites to new emails.
     */
    public static function handle_group_seat_save() {
        // Verify nonce and permissions
        check_admin_referer( 'llmsgaa_save_group_seats', 'llmsgaa_group_seats_nonce' );

        $group_id = absint( $_POST['group_id'] ?? 0 );
        $emails   = $_POST['order_email'] ?? [];

        foreach ( $emails as $order_id => $email ) {
            $order_id = absint( $order_id );
            $email    = sanitize_email( $email );

            if ( ! $order_id || ! is_email( $email ) ) {
                continue;
            }

            // 1) Save the email on the order
            update_post_meta( $order_id, 'student_email', $email );

            // 2) Try to find a WP user by email
            $user = get_user_by( 'email', $email );

            if ( $user ) {
                // 2a) Check if the user already belongs to this group
                $roles = \LLMS_Groups_Members::get_user_group_roles( $user->ID, $group_id );

                if ( ! empty( $roles ) ) {
                    // Already a group member: activate immediately
                    update_post_meta( $order_id, 'student_id',          $user->ID );
                    update_post_meta( $order_id, 'status',              'active' );
                    update_post_meta( $order_id, 'has_accepted_invite', '1' );
                    continue;
                }

                // 2b) User exists but not yet in group: enroll and activate
                \LLMS_Groups_Members::add_user_to_group( $user->ID, $group_id, 'member' );
                update_post_meta( $order_id, 'student_id',          $user->ID );
                update_post_meta( $order_id, 'status',              'active' );
                update_post_meta( $order_id, 'has_accepted_invite', '1' );
                continue;
            }

            // 3) No WP user: mark pending and send an invite
            update_post_meta( $order_id, 'student_id',          null );
            update_post_meta( $order_id, 'status',              'pending' );
            update_post_meta( $order_id, 'has_accepted_invite', '0' );

            // Use centralized service to handle insert + email
            $result = InviteService::send_invite( $group_id, $email, 'member' );
            if ( is_wp_error( $result ) ) {
                error_log( "❌ InviteService error for {$email}: " . $result->get_error_message() );
            }
        }

        // Redirect back to group page
        wp_safe_redirect( get_permalink( $group_id ) );
        exit;
    }




/**
 * Handle cancelling group invitations
 */
public static function handle_cancel_invite() {
    // Verify nonce and permissions
    check_admin_referer( 'llmsgaa_cancel_invite_action', 'llmsgaa_cancel_invite_nonce' );

    $group_id = absint( $_GET['group_id'] ?? 0 );
    $email = sanitize_email( $_GET['email'] ?? '' );
    $invite_id = absint( $_GET['invite_id'] ?? 0 );

    if ( ! $group_id ) {
        wp_die( 'Invalid group ID' );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'lifterlms_group_invitations';

    // Build the where clause based on what we have
    if ( $invite_id ) {
        // Use invite ID if provided
        $result = $wpdb->delete(
            $table,
            [ 'id' => $invite_id, 'group_id' => $group_id ],
            [ '%d', '%d' ]
        );
    } elseif ( $email ) {
        // Use email if provided
        $result = $wpdb->delete(
            $table,
            [ 'email' => $email, 'group_id' => $group_id ],
            [ '%s', '%d' ]
        );
    } else {
        wp_die( 'No invitation identifier provided' );
    }

    if ( $result === false ) {
        error_log( "❌ Failed to cancel invitation: " . $wpdb->last_error );
        wp_die( 'Failed to cancel invitation' );
    }

    if ( $result === 0 ) {
        error_log( "⚠️ No invitation found to cancel for group {$group_id}" );
    } else {
        error_log( "✅ Cancelled invitation for group {$group_id}, email: {$email}, invite_id: {$invite_id}" );
    }

    // Redirect back to group page with success message
    $redirect_url = add_query_arg( 'invite_cancelled', '1', get_permalink( $group_id ) );
    wp_safe_redirect( $redirect_url );
    exit;
}


}

// Register hooks
SeatSaveHandler::init_hooks();
