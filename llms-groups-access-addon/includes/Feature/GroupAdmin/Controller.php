<?php

namespace LLMSGAA\Feature\GroupAdmin;

use LLMS_Groups_Profile;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Controller for managing custom tabs on the group profile page.
 */
class Controller {

    public static function init_hooks() {
        add_action( 'plugins_loaded', [ __CLASS__, 'maybe_bootstrap_tabs' ], 20 );
        add_action( 'llms_group_profile_main_members', [ __CLASS__, 'render_members_tab' ] );
        add_action( 'llms_group_profile_main_llmsgaa_customize', [ __CLASS__, 'render_customize_tab' ] );
        add_action( 'user_register', [ __CLASS__, 'sync_orders_on_register' ], 10, 1 );


    }

    public static function get_admin_data( $group_id ) {
    global $wpdb;

    // Current admins
    $meta_table = $wpdb->prefix . 'lifterlms_user_postmeta';
    $user_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT user_id FROM {$meta_table}
             WHERE post_id = %d AND meta_key = '_group_role'
             AND meta_value IN ('primary_admin','admin')",
            $group_id
        )
    );

    $members = array_map(function( $uid ) use ( $wpdb, $group_id, $meta_table ) {
        $status = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_value FROM {$meta_table}
                 WHERE post_id = %d AND user_id = %d AND meta_key = '_status'",
                $group_id,
                $uid
            )
        );
        return (object) [
            'user_id' => $uid,
            'status' => $status ?: 'unknown',
        ];
    }, $user_ids );

    // Pending invites
    $invite_table = $wpdb->prefix . 'lifterlms_group_invitations';
    $pending_invites = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, invite_key, email FROM {$invite_table}
             WHERE group_id = %d AND role = 'admin'",
            $group_id
        )
    );

    return compact( 'members', 'pending_invites' );
    }

    public static function maybe_bootstrap_tabs() {
        if ( ! class_exists( 'LLMS_Groups_Profile' ) ) return;

        add_filter( 'llms_groups_profile_tab_slugs',     [ __CLASS__, 'register_tab_slugs' ], 5 );
        add_filter( 'llms_groups_profile_navigation',    [ __CLASS__, 'remove_navigation_items' ], 5, 2 );
        add_filter( 'llms_groups_profile_navigation',    [ __CLASS__, 'custom_navigation' ], 10, 2 );

        add_action( 'init', function() {
            remove_all_actions( 'llms_group_profile_sidebar' );
        }, 20 );

        add_action( 'llms_group_profile_main_passes',    [ __CLASS__, 'render_passes_tab' ] );
        add_action( 'llms_group_profile_main_customize', [ __CLASS__, 'render_customize_tab' ] );
        add_action( 'llms_group_profile_main_tutorial',  [ __CLASS__, 'render_tutorial_tab' ] );
        \LLMSGAA\Feature\GroupAdmin\Reporting::init_hooks();

    }

    public static function register_tab_slugs( $slugs ) {
        $slugs['passes']     = _x( 'passes', 'Tab slug', 'llms-groups-access-addon' );
        $slugs['reports']    = _x( 'reports', 'Tab slug', 'llms-groups-access-addon' );  // ← new
        $slugs['llmsgaa_customize']  = _x( 'llmsgaa_customize', 'Tab slug', 'llms-groups-access-addon' );
        $slugs['tutorial']   = _x( 'tutorial', 'Tab slug', 'llms-groups-access-addon' );
        return $slugs;
    }

public static function remove_navigation_items( $nav, $get_active ) {
    // Remove ALL default LifterLMS tabs first
    foreach ( [ 'members', 'settings', 'about', 'reports' ] as $key ) {
        unset( $nav[ $key ] );
    }
    
    // Also remove any other potential default tabs
    $default_tabs_to_remove = [ 'overview', 'activity', 'enrollment', 'progress' ];
    foreach ( $default_tabs_to_remove as $key ) {
        if ( isset( $nav[ $key ] ) ) {
            unset( $nav[ $key ] );
        }
    }
    
    return $nav;
}

public static function custom_navigation( $nav, $get_active ) {
    $base = trailingslashit( get_permalink() );
    
    // Create completely new navigation array in the exact order we want
    $new_nav = [];
    
    // Define tabs in the exact order we want them
    $ordered_tabs = [
        'passes' => [
            'title' => __( 'Members & Passes', 'llms-groups-access-addon' ),
            'url'   => user_trailingslashit( $base . LLMS_Groups_Profile::get_tab_slug( 'passes' ) ),
        ],
        'reports' => [
            'title' => __( 'Reports', 'llms-groups-access-addon' ),
            'url'   => user_trailingslashit( $base . LLMS_Groups_Profile::get_tab_slug( 'reports' ) ),
        ],
        'llmsgaa_customize' => [
            'title' => __( 'Settings', 'llms-groups-access-addon' ),
            'url'   => user_trailingslashit( $base . LLMS_Groups_Profile::get_tab_slug( 'llmsgaa_customize' ) ),
        ],
        'tutorial' => [
            'title' => __( 'Tutorial/Help', 'llms-groups-access-addon' ),
            'url'   => user_trailingslashit( $base . LLMS_Groups_Profile::get_tab_slug( 'tutorial' ) ),
        ],
    ];
    
    // Build the new navigation in our desired order
    foreach ( $ordered_tabs as $slug => $tab_data ) {
        $new_nav[ $slug ] = $tab_data;
    }
    
    // Set active tab
    if ( $get_active ) {
        $current = LLMS_Groups_Profile::get_current_tab();
        if ( $current && isset( $new_nav[ $current ] ) ) {
            $new_nav[ $current ]['active'] = true;
        }
    }
    
    // Return our completely new navigation (ignoring any existing nav)
    return $new_nav;
}


public static function render_passes_tab( $group_id = null ) {
    if ( ! $group_id ) {
        $group_id = self::resolve_group_id();
    }

    // 1) Fetch all Licenses
    $passes = get_posts([
        'post_type'      => 'llms_access_pass',
        'posts_per_page' => -1,
        'meta_key'       => 'group_id',
        'meta_value'     => $group_id,
    ]);

    // 2) Build SKU map (so your view gets $sku_map)
    $sku_map = [];
    foreach ( $passes as $p ) {
        $items = get_post_meta( $p->ID, 'llmsgaa_pass_items', true );
        if ( is_string( $items ) ) {
            $items = json_decode( $items, true );
        }
        foreach ( (array) $items as $it ) {
            if ( ! empty( $it['sku'] ) ) {
                // map SKU to itself (or to a label if you prefer)
                $sku_map[ $it['sku'] ] = $it['sku'];
            }
        }
    }

    // 3) Load group orders
    $orders = get_posts([
        'post_type'      => 'llms_group_order',
        'posts_per_page' => -1,
        'meta_query'     => [
            [ 'key' => 'group_id', 'value' => $group_id ],
        ],
    ]);

    // 4) Load seat‐invites
    global $wpdb;
    $invite_tbl = $wpdb->prefix . 'lifterlms_group_invitations';
    $pending = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, invite_key, email FROM {$invite_tbl} WHERE group_id = %d",
        $group_id
    ) );

    // 5) Load admin data
    $admin_data      = self::get_admin_data( $group_id );
    $members         = $admin_data['members'];
    $pending_invites = $admin_data['pending_invites'];

    // 6) Finally include the combined view, which now has:
    //    $passes, $sku_map, $orders, $pending, $members, $pending_invites, $group_id
    include LLMSGAA_DIR . 'views/group-profile/passes.php';
}



    public static function render_customize_tab( $group_id = null ) {
        if ( ! $group_id ) $group_id = self::resolve_group_id();
        include LLMSGAA_DIR . 'views/settings/customize.php';
    }

    public static function render_tutorial_tab( $group_id = null ) {
        if ( ! $group_id ) $group_id = self::resolve_group_id();

        $page = get_page_by_path( 'group-tutorial' );
        if ( $page && ! is_wp_error( $page ) ) {
            echo apply_filters( 'the_content', $page->post_content );
        } else {
            echo '<p>' . esc_html__( 'Tutorial page not found. Please create a page with the slug "group-tutorial".', 'llms-groups-access-addon' ) . '</p>';
        }
    }

public static function render_members_tab( $group_id = null ) {
    if ( ! $group_id ) {
        $group_id = self::resolve_group_id();
    }

    // Load group orders
    
    $orders = get_posts([
        'post_type'      => 'llms_group_order',
        'posts_per_page' => -1,
        'meta_query'     => [
            [ 'key' => 'group_id', 'value' => $group_id ],
        ],
        'orderby'  => 'meta_value',
        'meta_key' => 'start_date',
        'order'    => 'ASC',
    ]);


    // Load group admin and invite data
    $admin_data = self::get_admin_data( $group_id );
    $members         = $admin_data['members'];
    $pending_invites = $admin_data['pending_invites'];

    // Render view
    include LLMSGAA_DIR . 'views/group-profile/members.php';
}

public static function render_admins_tab( $group_id = null ) {
    if ( ! $group_id ) $group_id = self::resolve_group_id();

    global $wpdb;
    $meta_table = $wpdb->prefix . 'lifterlms_user_postmeta';
    $invite_tbl = $wpdb->prefix . 'lifterlms_group_invitations';

    // Get current admins
    $user_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT user_id FROM {$meta_table} WHERE post_id = %d AND meta_key = '_group_role' AND meta_value IN ('primary_admin','admin')",
        $group_id
    ) );

    // Get pending invitations
    $pending_invites = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, invite_key, email FROM {$invite_tbl} WHERE group_id = %d AND role = 'admin'",
        $group_id
    ) );

    // Build member objects
    $members = [];
    foreach ( $user_ids as $uid ) {
        $user = get_user_by( 'ID', $uid );
        if ( ! $user ) continue;

        $status = $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$meta_table} WHERE post_id = %d AND user_id = %d AND meta_key = '_status'",
            $group_id,
            $uid
        ) );

        $members[] = (object) [
            'user_id' => $uid,
            'status'  => $status ?: 'unknown',
        ];
    }
 include LLMSGAA_DIR . 'views/group-profile/admins.php';
}

    public static function resolve_group_id() {
        if ( class_exists( 'LLMS_Groups_Profile' ) && method_exists( 'LLMS_Groups_Profile', 'get_current_group_id' ) ) {
            return LLMS_Groups_Profile::get_current_group_id();
        } elseif ( get_the_ID() ) {
            return get_the_ID();
        } else {
            return get_queried_object_id();
        }
    }

  public static function render_group_page() {

    $group_id = get_the_ID();

    // your existing seat logic
    $orders  = LLMS_Groups_Enrollment::get_group_orders( $group_id );
    $pending = LLMS_Groups_Members::get_pending_seat_invites( $group_id );

    // new admin logic
    $members         = LLMS_Groups_Members::get_group_admins( $group_id );
    $pending_invites = LLMS_Groups_Members::get_pending_invites( $group_id );

    include LLMSGAA_DIR . 'views/group-profile/passes.php';
  }

 


      /**
     * On user registration, look up all llms_group_order posts matching the user's email
     * and update their student_id meta to the new user ID.
     *
     * @param int $user_id The newly registered WordPress user ID.
     */
    public static function sync_orders_on_register( $user_id ) {
        // Load user data
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            error_log( "⚠️ sync_orders_on_register: No user found for ID {$user_id}" );
            return;
        }

        $email = $user->user_email;
        error_log( "🚀 sync_orders_on_register: looking up orders for new user {$email} (ID {$user_id})" );

        // Query all llms_group_order posts with matching student_email meta
        $orders = get_posts([
            'post_type'      => 'llms_group_order',
            'posts_per_page' => -1,
            'meta_query'     => [
                [ 'key' => 'student_email', 'value' => $email ],
            ],
        ]);

        $count = count( $orders );
        error_log( "🔍 Found {$count} llms_group_order(s) for email {$email}" );
        if ( $count === 0 ) {
            return;
        }

        // Update student_id on each matching order
        foreach ( $orders as $order ) {
            $updated = update_post_meta( $order->ID, 'student_id', absint( $user_id ) );
            if ( $updated ) {
                error_log( "🎉 Updated student_id to {$user_id} on order ID {$order->ID}" );
            } else {
                error_log( "❗ Failed to update student_id on order ID {$order->ID}" );
            }
        }
    }


}


