<?php
namespace LLMSGAA\Feature\GroupAdmin;

use WP_Post;
use WP_Query;
use wp_get_current_user;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MyGroupsController {

public static function init() {
    add_shortcode( 'llmsgaa_my_groups', [ __CLASS__, 'render_my_groups' ] );
    add_action( 'template_redirect', [ __CLASS__, 'maybe_allow_group_access' ] );
}

public static function render_my_groups( $atts ) {
    // Parse attributes
    $atts = shortcode_atts( [
        'title' => 'My Groups',
        'style' => 'cards', // 'cards', 'table', or 'list'
        'show_stats' => 'true'
    ], $atts );

    if ( ! is_user_logged_in() ) {
        return '<div class="llmsgaa-notice llmsgaa-notice-info">
                    <h3>' . esc_html( $atts['title'] ) . '</h3>
                    <p>You must be logged in to view your groups.</p>
                </div>';
    }

    global $wpdb;
    $user_id = get_current_user_id();

    // Get groups where user is admin
    $group_data = $wpdb->get_results( $wpdb->prepare(
        "SELECT post_id as group_id, meta_value as role
         FROM {$wpdb->prefix}lifterlms_user_postmeta 
         WHERE user_id = %d AND meta_key = '_group_role' AND meta_value = 'admin'",
        $user_id
    ) );

    if ( empty( $group_data ) ) {
        return '<div class="llmsgaa-notice llmsgaa-notice-info">
                    <h3>' . esc_html( $atts['title'] ) . '</h3>
                    <p>You are not an admin of any groups yet. Contact your administrator to be assigned group management privileges.</p>
                </div>';
    }

    // Get detailed group information
    $groups = [];
    foreach ( $group_data as $group_info ) {
        $group = get_post( $group_info->group_id );
        if ( ! $group || $group->post_status !== 'publish' ) {
            continue;
        }

        // Get group statistics if enabled
        $stats = [];
        if ( $atts['show_stats'] === 'true' ) {
            $stats = self::get_group_statistics( $group_info->group_id );
        }

        $groups[] = [
            'id' => $group->ID,
            'title' => $group->post_title,
            'description' => wp_trim_words( $group->post_excerpt ?: $group->post_content, 20 ),
            'url' => get_permalink( $group->ID ),
            'role' => $group_info->role,
            'stats' => $stats,
            'created_date' => get_the_date( 'F j, Y', $group->ID )
        ];
    }

    if ( empty( $groups ) ) {
        return '<div class="llmsgaa-notice llmsgaa-notice-info">
                    <h3>' . esc_html( $atts['title'] ) . '</h3>
                    <p>No published groups found.</p>
                </div>';
    }

    // Generate output based on style
    switch ( $atts['style'] ) {
        case 'table':
            return self::render_groups_table( $groups, $atts );
        case 'list':
            return self::render_groups_list( $groups, $atts );
        case 'cards':
        default:
            return self::render_groups_cards( $groups, $atts );
    }
}

/**
 * Get statistics for a group
 */
private static function get_group_statistics( $group_id ) {
    global $wpdb;
    
    // Count total members (active + pending)
    $active_members = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) 
         FROM {$wpdb->prefix}lifterlms_user_postmeta 
         WHERE post_id = %d AND meta_key = '_group_role'",
        $group_id
    ) );
    
    $pending_invites = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) 
         FROM {$wpdb->prefix}lifterlms_group_invitations 
         WHERE group_id = %d",
        $group_id
    ) );
    
    // Count available licenses
    $available_licenses = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) 
         FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm_group ON p.ID = pm_group.post_id AND pm_group.meta_key = 'group_id' AND pm_group.meta_value = %d
         LEFT JOIN {$wpdb->postmeta} pm_email ON p.ID = pm_email.post_id AND pm_email.meta_key = 'student_email'
         WHERE p.post_type = 'llms_group_order'
         AND p.post_status = 'publish'
         AND (pm_email.meta_value IS NULL OR pm_email.meta_value = '')",
        $group_id
    ) );
    
    // Count assigned licenses
    $assigned_licenses = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) 
         FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm_group ON p.ID = pm_group.post_id AND pm_group.meta_key = 'group_id' AND pm_group.meta_value = %d
         INNER JOIN {$wpdb->postmeta} pm_email ON p.ID = pm_email.post_id AND pm_email.meta_key = 'student_email'
         WHERE p.post_type = 'llms_group_order'
         AND p.post_status = 'publish'
         AND pm_email.meta_value IS NOT NULL AND pm_email.meta_value != ''",
        $group_id
    ) );
    
    return [
        'active_members' => absint( $active_members ),
        'pending_invites' => absint( $pending_invites ),
        'total_members' => absint( $active_members ) + absint( $pending_invites ),
        'available_licenses' => absint( $available_licenses ),
        'assigned_licenses' => absint( $assigned_licenses ),
        'total_licenses' => absint( $available_licenses ) + absint( $assigned_licenses )
    ];
}

/**
 * Render groups in card style
 */
private static function render_groups_cards( $groups, $atts ) {
    ob_start();
    ?>
    <div class="llmsgaa-my-groups-dashboard">
        <h3 class="llmsgaa-dashboard-title"><?php echo esc_html( $atts['title'] ); ?></h3>
        <div class="llmsgaa-group-cards">
            <?php foreach ( $groups as $group ): ?>
            <div class="llmsgaa-group-card">
                <div class="llmsgaa-group-header">
                    <h4 class="llmsgaa-group-title">
                        <span class="llmsgaa-group-admin-badge">ADMIN</span>
                        <?php echo esc_html( $group['title'] ); ?>
                    </h4>
                    <div class="llmsgaa-group-status">
                        <span class="llmsgaa-status-icon">⚙️</span>
                        <span class="llmsgaa-status-label">Manager</span>
                    </div>
                </div>
                
                <?php if ( $group['description'] ): ?>
                <p class="llmsgaa-group-description"><?php echo esc_html( $group['description'] ); ?></p>
                <?php endif; ?>
                
                <?php if ( ! empty( $group['stats'] ) && $atts['show_stats'] === 'true' ): ?>
                <div class="llmsgaa-group-stats">
                    <div class="llmsgaa-stat-item">
                        <span class="llmsgaa-stat-icon">👥</span>
                        <span class="llmsgaa-stat-value"><?php echo $group['stats']['total_members']; ?></span>
                        <span class="llmsgaa-stat-label">Members</span>
                    </div>
                    <div class="llmsgaa-stat-item">
                        <span class="llmsgaa-stat-icon">🎫</span>
                        <span class="llmsgaa-stat-value"><?php echo $group['stats']['available_licenses']; ?></span>
                        <span class="llmsgaa-stat-label">Available</span>
                    </div>
                    <div class="llmsgaa-stat-item">
                        <span class="llmsgaa-stat-icon">✅</span>
                        <span class="llmsgaa-stat-value"><?php echo $group['stats']['assigned_licenses']; ?></span>
                        <span class="llmsgaa-stat-label">Assigned</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="llmsgaa-group-details">
                    <div class="llmsgaa-group-date">
                        <strong>Created:</strong> <?php echo esc_html( $group['created_date'] ); ?>
                    </div>
                </div>
                
                <div class="llmsgaa-group-actions">
                    <a href="<?php echo esc_url( $group['url'] ); ?>" class="llmsgaa-group-manage-btn">
                        <span class="llmsgaa-btn-icon">⚙️</span>
                        Manage Group
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render groups in table style
 */
private static function render_groups_table( $groups, $atts ) {
    ob_start();
    ?>
    <div class="llmsgaa-my-groups-dashboard">
        <h3 class="llmsgaa-dashboard-title"><?php echo esc_html( $atts['title'] ); ?></h3>
        <div class="llmsgaa-table-wrapper">
            <table class="llmsgaa-groups-table">
                <thead>
                    <tr>
                        <th>Group Name</th>
                        <th>Members</th>
                        <th>Licenses</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $groups as $group ): ?>
                    <tr>
                        <td>
                            <div class="llmsgaa-group-info">
                                <strong><?php echo esc_html( $group['title'] ); ?></strong>
                                <small class="llmsgaa-admin-role">Admin</small>
                            </div>
                        </td>
                        <td>
                            <?php if ( ! empty( $group['stats'] ) ): ?>
                                <?php echo $group['stats']['total_members']; ?>
                                <?php if ( $group['stats']['pending_invites'] > 0 ): ?>
                                    <small class="llmsgaa-pending">(<?php echo $group['stats']['pending_invites']; ?> pending)</small>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( ! empty( $group['stats'] ) ): ?>
                                <span class="llmsgaa-license-summary">
                                    <?php echo $group['stats']['assigned_licenses']; ?> / <?php echo $group['stats']['total_licenses']; ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( $group['created_date'] ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( $group['url'] ); ?>" class="llmsgaa-manage-link">
                                Manage
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render groups in simple list style
 */
private static function render_groups_list( $groups, $atts ) {
    ob_start();
    ?>
    <div class="llmsgaa-my-groups-dashboard">
        <h3 class="llmsgaa-dashboard-title"><?php echo esc_html( $atts['title'] ); ?></h3>
        <ul class="llmsgaa-groups-list">
            <?php foreach ( $groups as $group ): ?>
            <li class="llmsgaa-group-item">
                <div class="llmsgaa-group-summary">
                    <strong><?php echo esc_html( $group['title'] ); ?></strong>
                    <span class="llmsgaa-admin-badge">(Admin)</span>
                    <?php if ( ! empty( $group['stats'] ) ): ?>
                        <span class="llmsgaa-group-stats-inline">
                            👥 <?php echo $group['stats']['total_members']; ?> members
                            • 🎫 <?php echo $group['stats']['available_licenses']; ?> available
                        </span>
                    <?php endif; ?>
                </div>
                <div class="llmsgaa-group-actions-inline">
                    <a href="<?php echo esc_url( $group['url'] ); ?>" class="llmsgaa-manage-link-inline">
                        ⚙️ Manage Group
                    </a>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}



public static function maybe_allow_group_access() {
    if ( is_singular( 'llms_group' ) && is_user_logged_in() ) {
        global $post, $wpdb;

        $group_id = get_queried_object_id();
        $user_id = get_current_user_id();

        $role = $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}lifterlms_user_postmeta 
             WHERE user_id = %d AND post_id = %d AND meta_key = '_group_role'",
            $user_id,
            $group_id
        ) );

        if ( $role === 'admin' ) {
            return; // Allow access
        }

    }
}

}

