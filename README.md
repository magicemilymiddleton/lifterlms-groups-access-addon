Documentation for LLMSGAA

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
