Documentation for LLMSGAA

Warning!
We built this plugin with haste and there's a good deal of security and optimization to upgrade here.

Do you know how to create meta fields for custom post types in WordPress?
And display those meta fields on the WordPress backend and reference them in the WordPress frontend?

This is an add-on built on top of LifterLMS Groups.
Which is built on top of LifterLMS.
Which is built on top of WordPress.

This plugin uses a lot of this kind of logic 4 levels of features combined into one codebase.
We’re going to talk about:
- what WordPress features we’re using
- what LifterLMS features we’re using
- what LifterLMS Groups features we’re using
- what custom features we’ve built in our LLMSGAA add-on


——————

We have 4 plugins:
LLMSGAA - LifterLMS Groups Access Add-on

When the plugin is installed, you’ll get some WordPress Admin changes.
Under:
wp-admin
> Groups (LifterLMS Groups needs to be installed for this)
> “Licenses” llms_access_pass - see your Shopify orders that were imported to WordPress
> “Group Orders” llms_group_order - see your individual enrollments in all groups
> “Pass Settings” - see an overview of the latest Licenses purchased (more settings or reporting can be built out here)
> “Sku Mapping” - map your LifterLMS courses & memberships to incoming SKUs from Shopify


——————

📌 Complete List of LLMSGAA Shortcodes
1. [llmsgaa_student_dashboard]
Purpose: Displays a user's granted course access in a dashboard format Location: includes/Feature/Shortcodes/StudentDashboard.php
Parameters:
* title - Dashboard title (default: "Your Course Access")
* show_expired - Show expired courses (default: "true")
* show_upcoming - Show upcoming/future courses (default: "true")
* style - Display style: "cards", "table", or "list" (default: "cards")
Example:
[llmsgaa_student_dashboard title="My Training" style="table" show_expired="false"]
What it shows:
* Active course access with start/end dates
* Expired licenses (if enabled)
* Upcoming/pending licenses
* Course status indicators
* Access buttons to courses

2. [llmsgaa_my_course_access]
Purpose: Alternative name for the student dashboard (alias for llmsgaa_student_dashboard) Location: includes/Feature/Shortcodes/StudentDashboard.php
Parameters: Same as llmsgaa_student_dashboard
Example:
[llmsgaa_my_course_access style="cards"]

3. [llmsgaa_user_dashboard]
Purpose: Main user dashboard showing access passes and licenses Location: Referenced in main plugin file (registered via shortcode handler)
What it displays:
* Your Orders table (purchased access passes)
* Available licenses summary
* Redemption status
* License management interface
Note: This appears to be the primary dashboard shortcode used throughout the documentation.

4. [llmsgaa_my_groups]
Purpose: Shows all groups where the user is an administrator Location: includes/Feature/GroupAdmin/MyGroupsController.php
Parameters:
* title - Section title (default: "My Groups")
* style - Display style: "cards", "table", or "list" (default: "cards")
* show_stats - Show group statistics (default: "true")
Example:
[llmsgaa_my_groups title="Groups I Manage" show_stats="true" style="cards"]
What it displays:
* List of groups user administers
* Member count per group
* Active licenses count
* Links to group management pages
* Group creation dates

5. [llmsgaa_new_org_form]
Purpose: Displays a form for creating new organizations/groups Location: includes/Feature/Shortcodes/Controller.php
What it does:
* Renders organization registration form
* Collects organization name, admin email, name
* Allows adding multiple license items
* Creates new group on submission
* Sets up initial admin user
Example:
[llmsgaa_new_org_form]

6. [llmsgaa_group_debug]
Purpose: Debug tool showing access permissions and group membership info (admin only) Location: includes/Feature/AccessLogic/Override.php
What it displays:
* Current user's access status
* Group membership details
* Active licenses
* Permission chain analysis
* Database meta values
* Useful for troubleshooting access issues
Example:
[llmsgaa_group_debug]
Security: Only visible to administrators or users with debugging permissions

📊 Shortcode Usage Summary Table
Shortcode	Purpose	Target Users	Typical Page
[llmsgaa_student_dashboard]	View assigned courses	Students/Members	My Courses page
[llmsgaa_user_dashboard]	Manage licenses/passes	Group Admins	Dashboard page
[llmsgaa_my_groups]	List managed groups	Group Admins	My Groups page
[llmsgaa_new_org_form]	Create organizations	New Admins	Registration page
[llmsgaa_group_debug]	Troubleshoot access	Developers/Support	Debug page
🎨 Styling Notes
All shortcodes use these CSS classes for consistent styling:
* .llmsgaa-dashboard - Main container
* .llmsgaa-notice - Notification messages
* .llmsgaa-card - Card layouts
* .llmsgaa-table - Table layouts
* .llmsgaa-status - Status indicators
The plugin includes these stylesheets:
* student-dashboard.css - Student dashboard styles
* llmsgaa-passes.css - Pass management styles
* llmsgaa-custom.css - General custom styles
* main.css - Core plugin styles

💡 Implementation Tips
1. For Student Pages: Use [llmsgaa_student_dashboard] to show their assigned courses
2. For Admin Dashboards: Use [llmsgaa_user_dashboard] for full license management
3. For Group Management: Use [llmsgaa_my_groups] on a dedicated management page
4. For Debugging: Place [llmsgaa_group_debug] on a private admin-only page
The shortcodes automatically handle:
* Login state detection
* Permission checking
* Empty state messages
* Responsive layouts
* AJAX interactions (where applicable)



# Developer Reference
## Functions & Features Reference
This document lists the functions, classes, and features created by the LifterLMS Groups Access Add-on (LLMSGAA) for developer reference.
---

## **Core Plugin Architecture**

### Main Entry Points

```php
// Plugin initialization
LLMSGAA\PluginRegistrar::init()
```

**Registers all components:**
- `Core::init_hooks()`
- `MetaBoxes::init_hooks()`
- `GroupAdmin\Controller::init_hooks()`
- `Settings\SettingsPage::init_hooks()`
- `AdminColumns::init_hooks()`
- `Common\Assets::init_hooks()`
- `GroupAdmin\FormHandler::init()`
- `AccessLogic\Override::init()`
- `Shortcodes\Controller::init()`
- `GroupAdmin\MyGroupsController::init()`
- `FormHandler\Controller::init_hooks()`
- `Reports\Controller::init_hooks()`
- `GroupAdmin\Reporting::init_hooks()`
- `Invitation\SeatSaveHandler::init_hooks()`
- `Scheduler\ScheduleHandler::init()`

---

## **Custom Post Types**

### `llms_access_pass`
**Purpose:** Stores bulk license/access pass information

**Meta Fields:**
- `buyer_id` - Purchaser email
- `group_id` - Associated group
- `shopify_order_id` - External order reference
- `quantity` - Total seats
- `llmsgaa_pass_items` - Serialized array of SKUs/quantities
- `llmsgaa_redeemed` - Redemption status
- `start_date` - Activation date
- `expiration_date` - Pass expiration

### `llms_group_order`
**Purpose:** Individual license assignments to members

**Meta Fields:**
- `group_id` - Parent group
- `student_email` - Member email
- `student_id` - WordPress user ID
- `access_pass_id` - Source pass
- `product_id` - Course/membership ID
- `start_date` - Enrollment start
- `end_date` - Access expiration
- `status` - active/expired/pending
- `_invite_sent` - Invitation tracking

---

## **Core Classes & Methods**

### `LLMSGAA\Feature\UnifiedMemberManager`

Central class for all member and license operations.

#### **Public Static Methods:**

```php
// Member Management
UnifiedMemberManager::get_group_members( $group_id )
UnifiedMemberManager::add_member( $group_id, $email, $role = 'member' )
UnifiedMemberManager::remove_member( $group_id, $user_id = null, $email = null )
UnifiedMemberManager::change_member_role( $group_id, $user_id, $new_role )

// License Management
UnifiedMemberManager::get_available_licenses( $group_id )
UnifiedMemberManager::get_member_licenses( $group_id, $email )
UnifiedMemberManager::assign_license( $license_id, $email )
UnifiedMemberManager::unassign_license( $order_id )
UnifiedMemberManager::bulk_assign_licenses( $group_id, $emails, $license_id )

// Course Access
UnifiedMemberManager::get_user_course_access( $email )
UnifiedMemberManager::filter_active_courses( $courses )

// Bulk Operations
UnifiedMemberManager::init_bulk_handlers()
UnifiedMemberManager::ajax_bulk_assign_licenses()
UnifiedMemberManager::ajax_import_csv()
```

---

### `LLMSGAA\Feature\AccessLogic\Override`

Handles custom permission logic and access control.

#### **Public Static Methods:**

```php
Override::init()
Override::check( $can, $group_id, $user_id )
Override::filter_restricted_content( $content )
Override::redirect_non_admins()
Override::debug_shortcode( $atts )
Override::output_debug_console()
Override::register_access_denied_endpoint()
Override::add_query_vars( $vars )
Override::access_denied_template( $template )
```

---

### `LLMSGAA\Feature\GroupAdmin\Controller`

Manages group admin interfaces and tabs.

#### **Public Static Methods:**

```php
Controller::init_hooks()
Controller::resolve_group_id()
Controller::get_admin_data( $group_id )
Controller::maybe_bootstrap_tabs()
Controller::register_tab_slugs( $slugs )
Controller::remove_navigation_items( $nav, $get_active )
Controller::custom_navigation( $nav, $get_active )
Controller::render_passes_tab( $group_id = null )
Controller::render_customize_tab( $group_id = null )
Controller::render_tutorial_tab( $group_id = null )
Controller::render_members_tab( $group_id = null )
Controller::render_admins_tab( $group_id = null )
```

---

### `LLMSGAA\Feature\Scheduler\ScheduleHandler`

Background job processing using Action Scheduler.

#### **Public Static Methods:**

```php
ScheduleHandler::init()
ScheduleHandler::universal_schedule_check( $post_id, $post, $update )
ScheduleHandler::universal_schedule_check_on_insert( $post_id, $post, $update )
ScheduleHandler::process_order_scheduling( $post_id )
ScheduleHandler::handle_enroll( $order_id )
ScheduleHandler::handle_expire( $order_id )
ScheduleHandler::maybe_reschedule_on_meta_update( $meta_id, $post_id, $meta_key, $meta_value )
ScheduleHandler::ajax_trigger_schedule()
```

---

### `LLMSGAA\Feature\GroupAdmin\Reporting`

Handles group reporting and analytics.

#### **Public Static Methods:**

```php
Reporting::init_hooks()
Reporting::enqueue_assets()
Reporting::render_reports_tab( $group_id = null )
Reporting::get_group_courses( $group_id )
Reporting::ajax_get_course_report()
Reporting::ajax_get_student_detail()
```

---

### `LLMSGAA\Feature\Invitation\InviteService`

Manages email invitations for group members.

#### **Public Static Methods:**

```php
InviteService::send_invite( $group_id, $email, $role = 'member' )
InviteService::validate_invite( $invite_key )
InviteService::accept_invite( $invite_key, $user_id )
InviteService::cancel_invite( $group_id, $email )
InviteService::get_pending_invites( $group_id )
```

---

## **WordPress Hooks Created by LLMSGAA**

### Actions

```php
// License Management
do_action( 'llmsgaa_license_assigned', $user_id, $license_id, $group_id )
do_action( 'llmsgaa_license_unassigned', $user_id, $license_id, $group_id )
do_action( 'llmsgaa_pass_redeemed', $pass_id, $group_id, $start_date )

// Member Management
do_action( 'llmsgaa_member_added', $user_id, $group_id, $role )
do_action( 'llmsgaa_member_removed', $user_id, $group_id )
do_action( 'llmsgaa_member_role_changed', $user_id, $group_id, $old_role, $new_role )

// Invitations
do_action( 'llmsgaa_invitation_sent', $email, $group_id, $invite_key )
do_action( 'llmsgaa_invitation_accepted', $user_id, $group_id )
do_action( 'llmsgaa_invitation_cancelled', $email, $group_id )

// Scheduler
do_action( 'llmsggaa_enroll_user', $order_id )
do_action( 'llmsggaa_expire_user', $order_id )
do_action( 'llmsgaa_delayed_schedule', $post_id )
```

### Filters

```php
// Access Control
apply_filters( 'llmsgaa_user_can_access', $can_access, $user_id, $group_id )
apply_filters( 'llms_group_user_can_access', $can, $group_id, $user_id )

// Dashboard Data
apply_filters( 'llmsgaa_dashboard_data', $data, $user_id )
apply_filters( 'llmsgaa_available_licenses', $licenses, $group_id )
apply_filters( 'llmsgaa_member_courses', $courses, $email )

// Email Content
apply_filters( 'llmsgaa_invitation_email', $content, $email, $group_id )
apply_filters( 'llmsgaa_invitation_subject', $subject, $group_id )

// Navigation
apply_filters( 'llms_groups_profile_tab_slugs', $slugs )
apply_filters( 'llms_groups_profile_navigation', $nav, $get_active )
```

---

## **AJAX Handlers**

All AJAX handlers are prefixed with `wp_ajax_llmsgaa_`

### Member Management

```javascript
// Add member to group
action: 'llmsgaa_add_member'
data: { group_id, email, role, nonce }

// Remove member from group
action: 'llmsgaa_remove_member'
data: { user_id, group_id, email, nonce }

// Update member role
action: 'llmsgaa_update_member_role'
data: { user_id, group_id, role, email, nonce }
```

### License Management

```javascript
// Get available licenses
action: 'llmsgaa_get_available_licenses'
data: { group_id, nonce }

// Assign licenses to member
action: 'llmsgaa_assign_licenses'
data: { email, license_ids[], nonce }

// Get member's licenses
action: 'llmsgaa_get_member_licenses'
data: { group_id, email, nonce }

// Unassign license
action: 'llmsgaa_unassign_license'
data: { order_id, nonce }
```

### Bulk Operations

```javascript
// Bulk assign same license to multiple members
action: 'llmsgaa_bulk_assign_licenses'
data: { emails[], license_id, group_id, nonce }

// Sequential bulk assign (different licenses)
action: 'llmsgaa_bulk_assign_sequential'
data: { emails[], group_id, nonce }

// Import members via CSV
action: 'llmsgaa_import_csv'
data: { csv_file, group_id, default_role, assign_licenses, nonce }
```

### Invitations

```javascript
// Cancel pending invitation
action: 'llmsgaa_cancel_invite'
data: { invite_id, group_id, email, nonce }
```

### Reporting

```javascript
// Get course report data
action: 'llmsgaa_get_course_report'
data: { group_id, course_id, nonce }

// Get student detail
action: 'llmsgaa_get_student_detail'
data: { student_id, course_id, nonce }
```

### Scheduler

```javascript
// Manually trigger scheduling
action: 'trigger_order_schedule'
data: { order_id }
```

---

## **Database Operations**

### Custom Options

```php
// SKU mapping configuration
get_option( 'llmsgaa_sku_map' )
update_option( 'llmsgaa_sku_map', $map_array )

// Plugin settings
get_option( 'llmsgaa_settings' )
update_option( 'llmsgaa_settings', $settings_array )
```

### Direct Database Queries

The plugin uses these custom tables:

```sql
-- Group member roles (LifterLMS table)
{prefix}lifterlms_user_postmeta
  - user_id
  - post_id (group_id)
  - meta_key = '_group_role'
  - meta_value = 'admin'|'member'|'primary_admin'

-- Group invitations (LifterLMS table)
{prefix}lifterlms_group_invitations
  - id
  - group_id
  - email
  - invite_key
  - role
  - created_date
  - status
```

---

## **Frontend Assets**

### CSS Files Enqueued

```php
// Stylesheets (auto-loaded via Assets class)
'llmsgaa-main'           // main.css
'llmsgaa-llmsgaa-passes' // llmsgaa-passes.css
'llmsgaa-llmsgaa-custom' // llmsgaa-custom.css
'llmsgaa-reports'        // reports.css (conditional)
'llmsgaa-student-dashboard' // student-dashboard.css
```

### JavaScript Files Enqueued

```php
// Scripts (auto-loaded via Assets class)
'llmsgaa-cart-repeater'  // cart-repeater.js
'llmsgaa-llmsgaa-passes' // llmsgaa-passes.js
'llmsgaa-llmsgaa-utils'  // llmsgaa-utils.js
'llmsgaa-reports'        // reports.js (conditional)
```

### JavaScript Objects

```javascript
// Global objects available
window.llmsgaaSkuMap     // SKU mapping data
window.LLMSGAA_Reports    // Reports AJAX config
window.llmsgaa_ajax       // General AJAX config
```

---

## **Security Functions**

### Nonce Actions

```php
// Form nonces
'llmsgaa_new_org'           // New organization form
'llmsgaa_add_admin'         // Add group admin
'llmsgaa_unified_actions'   // Member management
'llmsgaa_reports_nonce'     // Reports access
'llmsgaa_cancel_invite_action' // Cancel invitation
'save_access_pass'          // Save pass meta
'save_group_order'          // Save order meta
```

### Capability Checks

```php
// Admin capabilities required
'manage_options'     // Plugin settings
'edit_posts'        // Access passes
'manage_lifterlms'  // Group management
```

---

## **Utility Functions**

### Helper Methods

```php
// Date formatting
LLMSGAA\Common\Utils::format_date( $date, $format = 'F j, Y' )
LLMSGAA\Common\Utils::days_until( $date )
LLMSGAA\Common\Utils::is_expired( $end_date )

// Email validation
LLMSGAA\Common\Utils::validate_email_list( $emails )
LLMSGAA\Common\Utils::sanitize_email_array( $emails )

// SKU operations
LLMSGAA\Common\Utils::get_sku_title( $sku )
LLMSGAA\Common\Utils::parse_sku_items( $items_json )
```

---

## **Data Structures**
### Access Pass Items Format

```php
// Stored in llmsgaa_pass_items meta
[
    [
        'sku' => 'COURSE-101',
        'quantity' => 25
    ],
    [
        'sku' => 'MEMBERSHIP-GOLD',
        'quantity' => 10
    ]
]
```

### SKU Map Format

```php
// Stored in llmsgaa_sku_map option
[
    'COURSE-101' => 1234,        // Course ID
    'MEMBERSHIP-GOLD' => 5678,   // Membership ID
    'BUNDLE-PRO' => 9012         // Bundle ID
]
```

### Group Order Status Values

```php
'pending'   // Future start date
'active'    // Currently active
'expired'   // Past end date
'cancelled' // Manually cancelled
```

---

## **Integration Points**
### FluentCRM Integration

```php
// Location: includes/Feature/integrations/fluentcrm/
- Tag users on enrollment
- Update contact fields
- Trigger automation workflows
```

### Action Scheduler Integration

```php
// Scheduled actions
'llmsggaa_enroll_user'    // Enrollment at start date
'llmsggaa_expire_user'    // Expiration at end date
'llmsgaa_delayed_schedule' // Delayed processing
```

### External Order Systems

```php
// Shopify integration via meta
'shopify_order_id'  // External order reference
'shopify_sku'       // Product SKU mapping
```

---

## **Template Files**
### View Files Location

```
/views/
├── group-profile/
│   ├── passes.php         // Passes tab content
│   ├── members.php        // Members management
│   └── admins.php         // Admin management
├── frontend/
│   ├── new-org-form.php   // Organization registration
│   └── dashboard.php      // User dashboard
├── settings/
│   ├── pass-settings.php  // Plugin settings
│   └── customize.php      // Group customization
└── email/
    ├── invitation.php     // Invitation email
    └── reminder.php       // Reminder email
```

---

## **Quick Implementation Examples**
### Add a Member with License

```php
// Add member to group
$result = LLMSGAA\Feature\UnifiedMemberManager::add_member( 
    $group_id, 
    'user@example.com', 
    'member' 
);

// Assign license if member added successfully
if ( ! is_wp_error( $result ) ) {
    LLMSGAA\Feature\UnifiedMemberManager::assign_license( 
        $license_id, 
        'user@example.com' 
    );
}
```

### Check User Access

```php
// Check if user can access group content
$can_access = apply_filters( 
    'llmsgaa_user_can_access', 
    false, 
    $user_id, 
    $group_id 
);
```

### Get Member's Courses

```php
// Get all courses for a user
$courses = LLMSGAA\Feature\UnifiedMemberManager::get_user_course_access( 
    'user@example.com' 
);

// Filter for active only
$active_courses = LLMSGAA\Feature\UnifiedMemberManager::filter_active_courses( 
    $courses 
);
```

### Bulk Import Members

```php
// Prepare CSV data
$csv_data = [
    ['email' => 'user1@example.com', 'role' => 'member'],
    ['email' => 'user2@example.com', 'role' => 'admin'],
];

// Process each member
foreach ( $csv_data as $member ) {
    LLMSGAA\Feature\UnifiedMemberManager::add_member(
        $group_id,
        $member['email'],
        $member['role']
    );
}
```

---

## **Debugging Tools**

### Enable Debug Mode

```php
// In wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'LLMSGAA_DEBUG', true );
```

### Debug Shortcode

```php
// Add to any page (admin only)
[llmsgaa_group_debug]
```

### Console Logging

```javascript
// Automatic console output when debug enabled
console.log('LLMSGAA Debug:', {
    user_id: current_user_id,
    group_id: current_group_id,
    access_status: access_result
});
```

---

## **Best Practices**

1. **Always use nonces** for AJAX and form submissions
2. **Sanitize all inputs** using WordPress functions
3. **Check capabilities** before allowing actions
4. **Use hooks** for extensibility
5. **Cache expensive queries** using transients
6. **Log errors** when LLMSGAA_DEBUG is enabled
7. **Use prepared statements** for direct DB queries
8. **Validate email addresses** before operations
9. **Handle WP_Error** returns properly
10. **Escape output** when displaying data

---

*This reference covers all custom functions and features created by the LifterLMS Groups Access Add-on v1.9*



# LifterLMS Groups Access Add-on - Hook Usage Examples

## 🪝 Complete Guide to Using LLMSGAA Hooks

This guide provides practical examples for using all hooks (actions and filters) created by the LifterLMS Groups Access Add-on.

---

## 📍 **Action Hooks**

Actions allow you to execute custom code at specific points in the plugin's execution.

### **License Management Actions**

#### `llmsgaa_license_assigned`
**Fired when:** A license is assigned to a user  
**Parameters:** `$user_id`, `$license_id`, `$group_id`

```php
// Example: Send a custom welcome email when license is assigned
add_action( 'llmsgaa_license_assigned', function( $user_id, $license_id, $group_id ) {
    $user = get_user_by( 'ID', $user_id );
    $license = get_post( $license_id );
    $group = get_post( $group_id );
    
    // Send custom email
    wp_mail(
        $user->user_email,
        'Your training access is ready!',
        sprintf(
            'Hi %s, You now have access to %s through %s group.',
            $user->display_name,
            $license->post_title,
            $group->post_title
        )
    );
    
    // Log to CRM
    if ( function_exists( 'fluentcrm_add_activity' ) ) {
        fluentcrm_add_activity( $user->user_email, 'License assigned: ' . $license->post_title );
    }
}, 10, 3 );
```

#### `llmsgaa_license_unassigned`
**Fired when:** A license is removed from a user  
**Parameters:** `$user_id`, `$license_id`, `$group_id`

```php
// Example: Clean up user data when license is removed
add_action( 'llmsgaa_license_unassigned', function( $user_id, $license_id, $group_id ) {
    // Remove user progress for this license's courses
    $order_meta = get_post_meta( $license_id, 'product_id', true );
    
    if ( $order_meta ) {
        // Clear LifterLMS progress
        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'lifterlms_user_postmeta',
            [
                'user_id' => $user_id,
                'post_id' => $order_meta,
                'meta_key' => '_status'
            ]
        );
    }
    
    // Notify admin
    $admin_email = get_option( 'admin_email' );
    wp_mail(
        $admin_email,
        'License Removed',
        sprintf( 'License %d removed from user %d in group %d', $license_id, $user_id, $group_id )
    );
}, 10, 3 );
```

#### `llmsgaa_pass_redeemed`
**Fired when:** An access pass is redeemed  
**Parameters:** `$pass_id`, `$group_id`, `$start_date`

```php
// Example: Create Slack notification when pass is redeemed
add_action( 'llmsgaa_pass_redeemed', function( $pass_id, $group_id, $start_date ) {
    $pass = get_post( $pass_id );
    $group = get_post( $group_id );
    $quantity = get_post_meta( $pass_id, 'quantity', true );
    
    // Send to Slack webhook
    $webhook_url = 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL';
    
    wp_remote_post( $webhook_url, [
        'body' => json_encode([
            'text' => sprintf(
                '🎉 New Pass Redeemed: %s\nGroup: %s\nSeats: %d\nStart Date: %s',
                $pass->post_title,
                $group->post_title,
                $quantity,
                $start_date
            )
        ]),
        'headers' => [
            'Content-Type' => 'application/json'
        ]
    ]);
    
    // Update analytics
    $redeemed_count = get_option( 'llmsgaa_total_redeemed', 0 );
    update_option( 'llmsgaa_total_redeemed', $redeemed_count + 1 );
}, 10, 3 );
```

### **Member Management Actions**

#### `llmsgaa_member_added`
**Fired when:** A new member is added to a group  
**Parameters:** `$user_id`, `$group_id`, `$role`

```php
// Example: Auto-enroll in onboarding course when member added
add_action( 'llmsgaa_member_added', function( $user_id, $group_id, $role ) {
    // Define onboarding course ID
    $onboarding_course_id = 123; // Your onboarding course ID
    
    // Auto-enroll new members
    if ( $role === 'member' ) {
        llms_enroll_student( $user_id, $onboarding_course_id );
        
        // Add welcome message meta
        update_user_meta( $user_id, 'llmsgaa_welcome_sent', current_time( 'mysql' ) );
        
        // Schedule follow-up email in 7 days
        wp_schedule_single_event(
            time() + ( 7 * DAY_IN_SECONDS ),
            'llmsgaa_send_followup',
            [ $user_id, $group_id ]
        );
    }
    
    // Give admins extra permissions
    if ( $role === 'admin' ) {
        $user = new WP_User( $user_id );
        $user->add_cap( 'manage_group_' . $group_id );
    }
}, 10, 3 );
```

#### `llmsgaa_member_removed`
**Fired when:** A member is removed from a group  
**Parameters:** `$user_id`, `$group_id`

```php
// Example: Clean up and notify when member is removed
add_action( 'llmsgaa_member_removed', function( $user_id, $group_id ) {
    $user = get_user_by( 'ID', $user_id );
    $group = get_post( $group_id );
    
    // Remove custom capabilities
    $user_obj = new WP_User( $user_id );
    $user_obj->remove_cap( 'manage_group_' . $group_id );
    
    // Log removal
    error_log( sprintf(
        'Member removed: %s (ID: %d) from group %s (ID: %d)',
        $user->user_email,
        $user_id,
        $group->post_title,
        $group_id
    ));
    
    // Send exit survey
    wp_mail(
        $user->user_email,
        'Your group membership has ended',
        'We\'d love to hear your feedback. Please take our exit survey: [link]'
    );
    
    // Update group statistics cache
    delete_transient( 'llmsgaa_group_stats_' . $group_id );
}, 10, 2 );
```

#### `llmsgaa_member_role_changed`
**Fired when:** A member's role is changed  
**Parameters:** `$user_id`, `$group_id`, `$old_role`, `$new_role`

```php
// Example: Handle role promotions and demotions
add_action( 'llmsgaa_member_role_changed', function( $user_id, $group_id, $old_role, $new_role ) {
    $user = get_user_by( 'ID', $user_id );
    
    // Handle promotion to admin
    if ( $old_role === 'member' && $new_role === 'admin' ) {
        // Grant admin course access
        $admin_training_course = 456; // Admin training course ID
        llms_enroll_student( $user_id, $admin_training_course );
        
        // Send promotion email
        wp_mail(
            $user->user_email,
            'You are now a group administrator!',
            'Congratulations! You now have admin privileges. Access your admin training here: [link]'
        );
        
        // Add to admin mailing list
        if ( function_exists( 'mailchimp_add_to_list' ) ) {
            mailchimp_add_to_list( $user->user_email, 'group-admins' );
        }
    }
    
    // Handle demotion from admin
    if ( $old_role === 'admin' && $new_role === 'member' ) {
        // Remove admin capabilities
        $user_obj = new WP_User( $user_id );
        $user_obj->remove_cap( 'edit_group_' . $group_id );
        
        // Log the change
        add_user_meta( $user_id, 'llmsgaa_role_history', [
            'date' => current_time( 'mysql' ),
            'from' => $old_role,
            'to' => $new_role,
            'group' => $group_id
        ]);
    }
}, 10, 4 );
```

### **Invitation Actions**

#### `llmsgaa_invitation_sent`
**Fired when:** An invitation email is sent  
**Parameters:** `$email`, `$group_id`, `$invite_key`

```php
// Example: Track invitations and send reminders
add_action( 'llmsgaa_invitation_sent', function( $email, $group_id, $invite_key ) {
    // Store invitation analytics
    $invites = get_option( 'llmsgaa_invitation_analytics', [] );
    $invites[] = [
        'email' => $email,
        'group_id' => $group_id,
        'sent_date' => current_time( 'mysql' ),
        'invite_key' => $invite_key
    ];
    update_option( 'llmsgaa_invitation_analytics', $invites );
    
    // Schedule reminder in 3 days if not accepted
    wp_schedule_single_event(
        time() + ( 3 * DAY_IN_SECONDS ),
        'llmsgaa_send_invite_reminder',
        [ $email, $group_id, $invite_key ]
    );
    
    // Notify group admin
    $group = get_post( $group_id );
    $admin_id = get_post_meta( $group_id, 'primary_admin', true );
    if ( $admin_id ) {
        $admin = get_user_by( 'ID', $admin_id );
        wp_mail(
            $admin->user_email,
            'Invitation sent',
            sprintf( 'An invitation was sent to %s for group %s', $email, $group->post_title )
        );
    }
}, 10, 3 );
```

#### `llmsgaa_invitation_accepted`
**Fired when:** An invitation is accepted  
**Parameters:** `$user_id`, `$group_id`

```php
// Example: Reward referrals when invitations are accepted
add_action( 'llmsgaa_invitation_accepted', function( $user_id, $group_id ) {
    // Find who sent the invitation
    $user = get_user_by( 'ID', $user_id );
    $referrer_id = get_user_meta( $user_id, 'referred_by', true );
    
    if ( $referrer_id ) {
        // Give referrer bonus points
        $current_points = get_user_meta( $referrer_id, 'llmsgaa_referral_points', true );
        update_user_meta( $referrer_id, 'llmsgaa_referral_points', $current_points + 100 );
        
        // Send thank you email to referrer
        $referrer = get_user_by( 'ID', $referrer_id );
        wp_mail(
            $referrer->user_email,
            'Your invitation was accepted!',
            sprintf( '%s has joined the group. You earned 100 referral points!', $user->display_name )
        );
    }
    
    // Clear any pending reminder emails
    wp_clear_scheduled_hook( 'llmsgaa_send_invite_reminder', [ $user->user_email, $group_id ] );
    
    // Auto-assign available license
    $licenses = LLMSGAA\Feature\UnifiedMemberManager::get_available_licenses( $group_id );
    if ( ! empty( $licenses ) ) {
        LLMSGAA\Feature\UnifiedMemberManager::assign_license( $licenses[0]->ID, $user->user_email );
    }
}, 10, 2 );
```

### **Scheduler Actions**

#### `llmsggaa_enroll_user`
**Fired when:** Scheduled enrollment should occur  
**Parameters:** `$order_id`

```php
// Example: Custom enrollment handling with notifications
add_action( 'llmsggaa_enroll_user', function( $order_id ) {
    // Get order details
    $student_email = get_post_meta( $order_id, 'student_email', true );
    $product_id = get_post_meta( $order_id, 'product_id', true );
    $group_id = get_post_meta( $order_id, 'group_id', true );
    
    // Perform enrollment
    $user = get_user_by( 'email', $student_email );
    if ( $user && $product_id ) {
        llms_enroll_student( $user->ID, $product_id );
        
        // Send "course now available" email
        wp_mail(
            $student_email,
            'Your course is now available!',
            sprintf( 'You can now access course %s. Login to start learning!', get_the_title( $product_id ) )
        );
        
        // Update order status
        update_post_meta( $order_id, 'status', 'active' );
        update_post_meta( $order_id, 'enrollment_date', current_time( 'mysql' ) );
        
        // Trigger webhooks
        do_action( 'llmsgaa_webhook_enrollment', [
            'order_id' => $order_id,
            'user_email' => $student_email,
            'course_id' => $product_id,
            'group_id' => $group_id
        ]);
    }
}, 10, 1 );
```

#### `llmsggaa_expire_user`
**Fired when:** Scheduled expiration should occur  
**Parameters:** `$order_id`

```php
// Example: Handle license expiration with grace period
add_action( 'llmsggaa_expire_user', function( $order_id ) {
    // Get order details
    $student_email = get_post_meta( $order_id, 'student_email', true );
    $product_id = get_post_meta( $order_id, 'product_id', true );
    $user = get_user_by( 'email', $student_email );
    
    // Check for grace period setting
    $grace_days = get_option( 'llmsgaa_expiration_grace_days', 7 );
    
    if ( $grace_days > 0 ) {
        // Set grace period flag
        update_post_meta( $order_id, 'in_grace_period', true );
        update_post_meta( $order_id, 'grace_period_ends', date( 'Y-m-d', strtotime( "+{$grace_days} days" ) ) );
        
        // Send warning email
        wp_mail(
            $student_email,
            'Your access expires soon',
            sprintf( 'Your access will expire in %d days. Contact your administrator to renew.', $grace_days )
        );
        
        // Schedule final expiration
        wp_schedule_single_event(
            time() + ( $grace_days * DAY_IN_SECONDS ),
            'llmsgaa_final_expiration',
            [ $order_id ]
        );
    } else {
        // Immediate expiration
        if ( $user && $product_id ) {
            llms_unenroll_student( $user->ID, $product_id );
            update_post_meta( $order_id, 'status', 'expired' );
            
            // Log expiration
            error_log( sprintf( 'License expired: Order %d for user %s', $order_id, $student_email ) );
        }
    }
}, 10, 1 );
```

---

## 🔧 **Filter Hooks**

Filters allow you to modify data before it's used by the plugin.

### **Access Control Filters**

#### `llmsgaa_user_can_access`
**Filters:** User access permission  
**Parameters:** `$can_access`, `$user_id`, `$group_id`  
**Return:** Boolean

```php
// Example: Grant access based on custom membership level
add_filter( 'llmsgaa_user_can_access', function( $can_access, $user_id, $group_id ) {
    // Check if user has premium membership
    $membership_level = get_user_meta( $user_id, 'membership_level', true );
    
    if ( $membership_level === 'platinum' ) {
        // Platinum members get access to all groups
        return true;
    }
    
    // Check if user has bypass permission
    if ( user_can( $user_id, 'bypass_group_restrictions' ) ) {
        return true;
    }
    
    // Check custom access list
    $vip_users = get_option( 'llmsgaa_vip_users', [] );
    if ( in_array( $user_id, $vip_users ) ) {
        return true;
    }
    
    // Check time-based access
    $access_schedule = get_post_meta( $group_id, 'access_schedule', true );
    if ( $access_schedule ) {
        $current_hour = date( 'G' );
        if ( $current_hour >= 9 && $current_hour <= 17 ) {
            return true; // Business hours access
        }
    }
    
    return $can_access;
}, 10, 3 );
```

#### `llms_group_user_can_access`
**Filters:** LifterLMS group access check  
**Parameters:** `$can`, `$group_id`, `$user_id`  
**Return:** Boolean

```php
// Example: Override based on IP address or location
add_filter( 'llms_group_user_can_access', function( $can, $group_id, $user_id ) {
    // Allow access from office IP addresses
    $office_ips = [
        '192.168.1.100',
        '10.0.0.50'
    ];
    
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ( in_array( $user_ip, $office_ips ) ) {
        return true;
    }
    
    // Check user's company domain
    $user = get_user_by( 'ID', $user_id );
    if ( $user ) {
        $email_domain = substr( strrchr( $user->user_email, '@' ), 1 );
        $allowed_domains = get_post_meta( $group_id, 'allowed_email_domains', true );
        
        if ( $allowed_domains && in_array( $email_domain, $allowed_domains ) ) {
            return true;
        }
    }
    
    // Check for temporary access pass
    $temp_pass = get_user_meta( $user_id, 'temp_access_pass_' . $group_id, true );
    if ( $temp_pass && strtotime( $temp_pass ) > time() ) {
        return true;
    }
    
    return $can;
}, 9999, 3 );
```

### **Dashboard Data Filters**

#### `llmsgaa_dashboard_data`
**Filters:** Dashboard display data  
**Parameters:** `$data`, `$user_id`  
**Return:** Modified data array

```php
// Example: Add custom data to user dashboard
add_filter( 'llmsgaa_dashboard_data', function( $data, $user_id ) {
    // Add completion statistics
    $data['stats'] = [
        'total_courses' => count( $data['courses'] ?? [] ),
        'completed' => 0,
        'in_progress' => 0,
        'not_started' => 0
    ];
    
    // Calculate progress for each course
    foreach ( $data['courses'] ?? [] as $key => $course ) {
        $progress = llms_get_user_progress( $user_id, $course['course_id'] );
        $data['courses'][$key]['progress'] = $progress;
        
        if ( $progress == 100 ) {
            $data['stats']['completed']++;
        } elseif ( $progress > 0 ) {
            $data['stats']['in_progress']++;
        } else {
            $data['stats']['not_started']++;
        }
        
        // Add certificate link if completed
        if ( $progress == 100 ) {
            $certificate_id = get_post_meta( $course['course_id'], '_llms_certificate', true );
            if ( $certificate_id ) {
                $data['courses'][$key]['certificate_url'] = llms_get_certificate_url( $certificate_id, $user_id );
            }
        }
    }
    
    // Add user achievements
    $data['achievements'] = [
        'badges' => llms_get_user_achievements( $user_id, 'badge' ),
        'certificates' => llms_get_user_achievements( $user_id, 'certificate' )
    ];
    
    // Add upcoming expirations warning
    $data['expiring_soon'] = [];
    foreach ( $data['courses'] ?? [] as $course ) {
        if ( ! empty( $course['end_date'] ) ) {
            $days_until = ( strtotime( $course['end_date'] ) - time() ) / DAY_IN_SECONDS;
            if ( $days_until > 0 && $days_until <= 30 ) {
                $data['expiring_soon'][] = [
                    'course' => $course['course_title'],
                    'days_left' => round( $days_until ),
                    'end_date' => $course['end_date']
                ];
            }
        }
    }
    
    return $data;
}, 10, 2 );
```

#### `llmsgaa_available_licenses`
**Filters:** Available licenses list  
**Parameters:** `$licenses`, `$group_id`  
**Return:** Modified licenses array

```php
// Example: Filter licenses based on user role or criteria
add_filter( 'llmsgaa_available_licenses', function( $licenses, $group_id ) {
    // Get current user
    $current_user = wp_get_current_user();
    
    // Filter licenses based on user department
    $user_department = get_user_meta( $current_user->ID, 'department', true );
    
    $filtered_licenses = [];
    foreach ( $licenses as $license ) {
        // Check if license is designated for specific department
        $license_departments = get_post_meta( $license->ID, 'allowed_departments', true );
        
        if ( empty( $license_departments ) || in_array( $user_department, $license_departments ) ) {
            // Add custom data to license
            $license->priority = get_post_meta( $license->ID, 'license_priority', true ) ?: 0;
            $license->restrictions = get_post_meta( $license->ID, 'license_restrictions', true );
            
            // Check seat availability
            $total_seats = get_post_meta( $license->ID, 'quantity', true );
            $used_seats = LLMSGAA\Feature\UnifiedMemberManager::get_used_seats( $license->ID );
            $license->available_seats = $total_seats - $used_seats;
            
            // Only show if seats available
            if ( $license->available_seats > 0 ) {
                $filtered_licenses[] = $license;
            }
        }
    }
    
    // Sort by priority
    usort( $filtered_licenses, function( $a, $b ) {
        return $b->priority - $a->priority;
    });
    
    return $filtered_licenses;
}, 10, 2 );
```

#### `llmsgaa_member_courses`
**Filters:** Member's course list  
**Parameters:** `$courses`, `$email`  
**Return:** Modified courses array

```php
// Example: Add prerequisites and recommended courses
add_filter( 'llmsgaa_member_courses', function( $courses, $email ) {
    $user = get_user_by( 'email', $email );
    if ( ! $user ) {
        return $courses;
    }
    
    // Add prerequisite check for each course
    foreach ( $courses as $key => $course ) {
        $prereq_id = get_post_meta( $course['course_id'], '_llms_prerequisite', true );
        
        if ( $prereq_id ) {
            $prereq_complete = llms_is_complete( $user->ID, $prereq_id );
            $courses[$key]['has_prerequisite'] = true;
            $courses[$key]['prerequisite_id'] = $prereq_id;
            $courses[$key]['prerequisite_title'] = get_the_title( $prereq_id );
            $courses[$key]['prerequisite_complete'] = $prereq_complete;
            
            // Lock course if prerequisite not complete
            if ( ! $prereq_complete ) {
                $courses[$key]['locked'] = true;
                $courses[$key]['locked_reason'] = 'Complete ' . get_the_title( $prereq_id ) . ' first';
            }
        }
        
        // Add recommended next courses
        $next_courses = get_post_meta( $course['course_id'], 'recommended_next', true );
        if ( $next_courses ) {
            $courses[$key]['recommended_next'] = array_map( function( $id ) {
                return [
                    'id' => $id,
                    'title' => get_the_title( $id ),
                    'url' => get_permalink( $id )
                ];
            }, $next_courses );
        }
        
        // Add course difficulty level
        $courses[$key]['difficulty'] = get_post_meta( $course['course_id'], 'difficulty_level', true ) ?: 'Beginner';
        
        // Add estimated time
        $courses[$key]['estimated_hours'] = get_post_meta( $course['course_id'], 'estimated_hours', true ) ?: '2-4';
    }
    
    return $courses;
}, 10, 2 );
```

### **Email Content Filters**

#### `llmsgaa_invitation_email`
**Filters:** Invitation email content  
**Parameters:** `$content`, `$email`, `$group_id`  
**Return:** Modified email content

```php
// Example: Customize invitation email with branding
add_filter( 'llmsgaa_invitation_email', function( $content, $email, $group_id ) {
    $group = get_post( $group_id );
    $user = get_user_by( 'email', $email );
    
    // Add custom header
    $header = '<div style="background: #0073aa; padding: 20px; text-align: center;">';
    $header .= '<img src="' . get_site_url() . '/logo.png" alt="Logo" style="max-width: 200px;">';
    $header .= '</div>';
    
    // Personalize content
    $personalized = $header;
    $personalized .= '<div style="padding: 20px; font-family: Arial, sans-serif;">';
    
    if ( $user ) {
        $personalized .= '<h2>Welcome back, ' . esc_html( $user->display_name ) . '!</h2>';
    } else {
        $personalized .= '<h2>You\'re invited to join ' . esc_html( $group->post_title ) . '!</h2>';
    }
    
    // Add the original content
    $personalized .= $content;
    
    // Add custom benefits section
    $personalized .= '<h3>What you\'ll get access to:</h3>';
    $personalized .= '<ul>';
    
    // Get group courses
    $licenses = LLMSGAA\Feature\UnifiedMemberManager::get_available_licenses( $group_id );
    foreach ( $licenses as $license ) {
        $personalized .= '<li>' . esc_html( $license->course_title ) . '</li>';
    }
    $personalized .= '</ul>';
    
    // Add footer with support info
    $personalized .= '<hr style="margin: 30px 0;">';
    $personalized .= '<p style="color: #666; font-size: 12px;">';
    $personalized .= 'Need help? Contact support at support@example.com<br>';
    $personalized .= 'This invitation expires in 30 days.';
    $personalized .= '</p>';
    $personalized .= '</div>';
    
    return $personalized;
}, 10, 3 );
```

#### `llmsgaa_invitation_subject`
**Filters:** Invitation email subject  
**Parameters:** `$subject`, `$group_id`  
**Return:** Modified subject string

```php
// Example: Dynamic invitation subject lines
add_filter( 'llmsgaa_invitation_subject', function( $subject, $group_id ) {
    $group = get_post( $group_id );
    
    // Get group admin name
    $admin_id = get_post_meta( $group_id, 'primary_admin', true );
    $admin = get_user_by( 'ID', $admin_id );
    
    // Create personalized subject
    if ( $admin ) {
        $subject = sprintf(
            '%s invited you to join %s training program',
            $admin->display_name,
            $group->post_title
        );
    } else {
        // Check for urgency
        $urgent = get_post_meta( $group_id, 'urgent_enrollment', true );
        if ( $urgent ) {
            $subject = '⚡ URGENT: Your ' . $group->post_title . ' access is ready';
        } else {
            $subject = '🎓 You\'re invited: ' . $group->post_title . ' Training Program';
        }
    }
    
    // Add company name if set
    $company = get_option( 'llmsgaa_company_name' );
    if ( $company ) {
        $subject = '[' . $company . '] ' . $subject;
    }
    
    return $subject;
}, 10, 2 );
```

### **Navigation Filters**

#### `llms_groups_profile_tab_slugs`
**Filters:** Group profile tab slugs  
**Parameters:** `$slugs`  
**Return:** Modified slugs array

```php
// Example: Add custom tabs to group profiles
add_filter( 'llms_groups_profile_tab_slugs', function( $slugs ) {
    // Add custom tabs
    $slugs['resources'] = 'resources';
    $slugs['discussion'] = 'discussion';
    $slugs['calendar'] = 'calendar';
    $slugs['leaderboard'] = 'leaderboard';
    
    // Remove unwanted tabs
    unset( $slugs['about'] );
    
    // Rename existing tabs
    $slugs['passes'] = 'licenses'; // Rename passes to licenses
    
    return $slugs;
}, 20 );
```

#### `llms_groups_profile_navigation`
**Filters:** Group profile navigation items  
**Parameters:** `$nav`, `$get_active`  
**Return:** Modified navigation array

```php
// Example: Customize group navigation with icons and permissions
add_filter( 'llms_groups_profile_navigation', function( $nav, $get_active ) {
    $group_id = get_the_ID();
    $user_id = get_current_user_id();
    
    // Add icon to each nav item
    $icons = [
        'passes' => '📦',
        'reports' => '📊',
        'members' => '👥',
        'settings' => '⚙️'
    ];
    
    foreach ( $nav as $key => &$item ) {
        if ( isset( $icons[$key] ) ) {
            $item['title'] = $icons[$key] . ' ' . $item['title'];
        }
    }
    
    // Add custom Resources tab
    $nav['resources'] = [
        'title' => '📚 Resources',
        'url' => trailingslashit( get_permalink() ) . 'resources'
    ];
    
    // Add Discussion Forum tab (only for members)
    if ( LLMS_Groups_Enrollment::user_has_access( $user_id, $group_id ) ) {
        $nav['discussion'] = [
            'title' => '💬 Discussion',
            'url' => trailingslashit( get_permalink() ) . 'discussion'
        ];
    }
    
    // Add Calendar tab (only for admins)
    global $wpdb;
    $is_admin = $wpdb->get_var( $wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->prefix}lifterlms_user_postmeta 
         WHERE user_id = %d AND post_id = %d AND meta_key = '_group_role' 
         AND meta_value IN ('admin', 'primary_admin')",
        $user_id,
        $group_id
    ));
    
    if ( $is_admin ) {
        $nav['calendar'] = [
            'title' => '📅 Calendar',
            'url' => trailingslashit( get_permalink() ) . 'calendar'
        ];
    }
    
    // Reorder navigation
    $order = [ 'passes', 'members', 'reports', 'resources', 'discussion', 'calendar', 'settings' ];
    $ordered_nav = [];
    
    foreach ( $order as $key ) {
        if ( isset( $nav[$key] ) ) {
            $ordered_nav[$key] = $nav[$key];
        }
    }
    
    return $ordered_nav;
}, 20, 2 );
```

---

## **Advanced Hook Combinations**
### **Complete Member Onboarding Flow**

```php
// Combine multiple hooks for complete onboarding
class LLMSGAA_Custom_Onboarding {
    
    public static function init() {
        // When member is added, start onboarding
        add_action( 'llmsgaa_member_added', [ __CLASS__, 'start_onboarding' ], 10, 3 );
        
        // When invitation is accepted, assign starter license
        add_action( 'llmsgaa_invitation_accepted', [ __CLASS__, 'assign_starter_package' ], 10, 2 );
        
        // When license is assigned, send welcome series
        add_action( 'llmsgaa_license_assigned', [ __CLASS__, 'trigger_welcome_series' ], 10, 3 );
        
        // Customize dashboard for new users
        add_filter( 'llmsgaa_dashboard_data', [ __CLASS__, 'add_onboarding_steps' ], 10, 2 );
    }
    
    public static function start_onboarding( $user_id, $group_id, $role ) {
        // Set onboarding flag
        update_user_meta( $user_id, 'llmsgaa_onboarding_status', 'started' );
        update_user_meta( $user_id, 'llmsgaa_onboarding_step', 1 );
        
        // Schedule check-ins
        wp_schedule_single_event( time() + DAY_IN_SECONDS, 'llmsgaa_onboarding_day_1', [ $user_id ] );
        wp_schedule_single_event( time() + WEEK_IN_SECONDS, 'llmsgaa_onboarding_week_1', [ $user_id ] );
    }
    
    public static function assign_starter_package( $user_id, $group_id ) {
        // Find and assign orientation course
        $orientation_sku = 'ORIENTATION-101';
        $licenses = LLMSGAA\Feature\UnifiedMemberManager::get_available_licenses( $group_id );
        
        foreach ( $licenses as $license ) {
            $items = get_post_meta( $license->ID, 'llmsgaa_pass_items', true );
            foreach ( $items as $item ) {
                if ( $item['sku'] === $orientation_sku ) {
                    LLMSGAA\Feature\UnifiedMemberManager::assign_license( 
                        $license->ID, 
                        get_user_by( 'ID', $user_id )->user_email 
                    );
                    break 2;
                }
            }
        }
    }
    
    public static function trigger_welcome_series( $user_id, $license_id, $group_id ) {
        // Send to email automation
        if ( function_exists( 'fluentcrm_add_to_sequence' ) ) {
            fluentcrm_add_to_sequence( 
                get_user_by( 'ID', $user_id )->user_email, 
                'new-member-welcome' 
            );
        }
    }
    
    public static function add_onboarding_steps( $data, $user_id ) {
        $onboarding_status = get_user_meta( $user_id, 'llmsgaa_onboarding_status', true );
        
        if ( $onboarding_status === 'started' ) {
            $data['onboarding'] = [
                'show' => true,
                'current_step' => get_user_meta( $user_id, 'llmsgaa_onboarding_step', true ),
                'steps' => [
                    'Complete your profile',
                    'Watch orientation video',
                    'Join the discussion forum',
                    'Complete first course',
                    'Schedule 1-on-1 with manager'
                ]
            ];
        }
        
        return $data;
    }
}

// Initialize the onboarding system
LLMSGAA_Custom_Onboarding::init();
```

---

## **Best Practices for Using Hooks**

1. **Always check data validity** before processing
2. **Use appropriate priority** (default is 10)
3. **Return the expected data type** from filters
4. **Don't create infinite loops** by calling the same hook within itself
5. **Use namespaced functions or classes** to avoid conflicts
6. **Document your custom hooks** for other developers
7. **Clean up scheduled events** when they're no longer needed
8. **Test with different user roles** and permissions
9. **Handle errors gracefully** with try-catch blocks
10. **Use transients for expensive operations** in filters

---

*This guide covers all hooks created by the LifterLMS Groups Access Add-on with practical implementation examples.*
