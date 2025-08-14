
<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Ensure all data is available
$group_id = $group_id ?? get_the_ID();
$passes   = $passes   ?? [];

// Get data using our new functions
$all_members = LLMSGAA\Feature\Shortcodes\UnifiedMemberManager::get_all_group_members( $group_id );
$available_licenses = LLMSGAA\Feature\Shortcodes\UnifiedMemberManager::get_available_licenses( $group_id );
?>

<div id="llmsgaa-unified-wrapper" class="space-y-6">

<div class="llmsgaa-box">
  <h2 class="text-xl font-semibold">Your Orders</h2>

  <!-- Keep existing modal HTML -->
  <div id="llmsgaa-pass-modal" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div style="border:2px solid #666; border-radius:10px; background:#f9f9f9; padding:20px; position:relative;">
      <span class="llmsgaa-modal-close" style="cursor:pointer; position:absolute; top:10px; right:14px; font-size:24px;">&times;</span>
      <div class="llmsgaa-modal-body"></div>
    </div>
  </div>

<!-- Replace the existing redeem modal in passes.php with this improved version -->

<div id="llmsgaa-redeem-modal" class="llmsgaa-modal-overlay" style="position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; display: none; align-items: center !important; justify-content: center !important;">
  <div class="llmsgaa-modal-container" style="position: relative !important; margin: auto !important;">
    <div class="llmsgaa-modal-header">
      <h3 class="llmsgaa-modal-title">Select Start Date</h3>
      <button type="button" class="llmsgaa-modal-close" aria-label="Close">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
    
    <div class="llmsgaa-modal-body">
      <p class="llmsgaa-modal-description">
        Please select when you would like the course access to begin. The license will be activated on this date.
      </p>
      
      <form id="llmsgaa-redeem-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'llmsgaa_redeem_pass_action', 'llmsgaa_redeem_pass_nonce' ); ?>
        <input type="hidden" name="action" value="llmsgaa_redeem_pass" />
        <input type="hidden" name="pass_id" value="" />
        
        <div class="llmsgaa-form-group">
          <label for="llmsgaa-start-date" class="llmsgaa-form-label">
            Start Date
          </label>
          <input 
            type="date" 
            id="llmsgaa-start-date"
            name="start_date" 
            required 
            class="llmsgaa-form-input"
            min="<?php echo date('Y-m-d'); ?>"
          />
          <span class="llmsgaa-form-hint">Access will begin on this date</span>
        </div>
        
        <div class="llmsgaa-modal-footer">
          <button type="button" class="llmsgaa-btn llmsgaa-btn-secondary llmsgaa-modal-cancel">
            Cancel
          </button>
          <button type="submit" class="llmsgaa-btn llmsgaa-btn-primary">
            <span class="llmsgaa-btn-text">Confirm Start Date</span>
            <svg class="llmsgaa-btn-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
              <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 1.414L10.586 9.5H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z"/>
            </svg>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
/* Modal Overlay */
.llmsgaa-modal-overlay {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  width: 100% !important;
  height: 100% !important;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  z-index: 99999;
  display: none;
  align-items: center !important;
  justify-content: center !important;
  padding: 20px;
  animation: llmsgaa-fade-in 0.2s ease-out;
}

.llmsgaa-modal-overlay.is-visible {
  display: flex !important;
}

/* Modal Container */
.llmsgaa-modal-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  width: 100%;
  max-width: 440px;
  max-height: 90vh;
  overflow: hidden;
  animation: llmsgaa-slide-up 0.3s ease-out;
  position: relative !important;
  margin: auto !important;
}

/* Modal Header */
.llmsgaa-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24px 24px 0;
  border-bottom: none;
}

.llmsgaa-modal-title {
  font-size: 24px;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.llmsgaa-modal-close {
  background: none;
  border: none;
  padding: 8px;
  cursor: pointer;
  color: #6b7280;
  border-radius: 6px;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.llmsgaa-modal-close:hover {
  background: #f3f4f6;
  color: #111827;
}

/* Modal Body */
.llmsgaa-modal-body {
  padding: 20px 24px 24px;
}

.llmsgaa-modal-description {
  color: #6b7280;
  font-size: 14px;
  line-height: 1.5;
  margin: 0 0 24px 0;
}

/* Form Elements */
.llmsgaa-form-group {
  margin-bottom: 24px;
}

.llmsgaa-form-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  margin-bottom: 8px;
}

.llmsgaa-form-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 15px;
  color: #111827;
  background: white;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.llmsgaa-form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.llmsgaa-form-hint {
  display: block;
  font-size: 12px;
  color: #9ca3af;
  margin-top: 6px;
}

/* Modal Footer */
.llmsgaa-modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

/* Buttons */
.llmsgaa-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  font-size: 14px;
  font-weight: 500;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  line-height: 1;
}

.llmsgaa-btn-primary {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
  box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
}

.llmsgaa-btn-primary:hover {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
  transform: translateY(-1px);
}

.llmsgaa-btn-secondary {
  background: white;
  color: #6b7280;
  border: 1px solid #e5e7eb;
}

.llmsgaa-btn-secondary:hover {
  background: #f9fafb;
  color: #374151;
  border-color: #d1d5db;
}

.llmsgaa-btn-icon {
  width: 16px;
  height: 16px;
}

/* Animations */
@keyframes llmsgaa-fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes llmsgaa-slide-up {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

/* Responsive */
@media (max-width: 480px) {
  .llmsgaa-modal-container {
    max-width: 100%;
    margin: 20px;
  }
  
  .llmsgaa-modal-header {
    padding: 20px 20px 0;
  }
  
  .llmsgaa-modal-body {
    padding: 16px 20px 20px;
  }
  
  .llmsgaa-modal-title {
    font-size: 20px;
  }
}
</style>

  <!-- Available Licenses Summary (moved from below) -->
  <?php if ( isset( $available_licenses ) && count( $available_licenses ) > 0 ): ?>
    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded">
      <strong>Available Licenses Summary:</strong>
      <ul class="mt-2 text-sm">
        <?php 
        $license_summary = [];
        foreach ( $available_licenses as $license ) {
          $key = $license->course_title . ' (Start: ' . $license->start_date_formatted . ')';
          $license_summary[$key] = ($license_summary[$key] ?? 0) + 1;
        }
        foreach ( $license_summary as $course => $count ): ?>
          <li><?php echo esc_html( $course . ' - ' . $count . ' license' . ($count > 1 ? 's' : '') . ' available to assign' ); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- Restructured passes table with 2 columns -->
  <div class="overflow-x-auto">
    <table class="llmsgaa-table w-full text-sm">
      <thead>
        <tr>
          <th style="width: 25%;">Date Purchased</th>
          <th style="width: 75%;">Information</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        // Get the SKU map from wp_options
        $sku_map = get_option( 'llmsgaa_sku_map', array() );
        
        foreach ( $passes as $p ):
          $items = get_post_meta( $p->ID, 'llmsgaa_pass_items', true );
          if ( is_string( $items ) ) { $items = json_decode( $items, true ); }
          $total_seats = array_sum( wp_list_pluck( (array) $items, 'quantity' ) );
          $items_json  = wp_json_encode( $items );
          $is_redeemed = get_post_meta( $p->ID, 'llmsgaa_redeemed', true );
          $buyer_email = get_post_meta( $p->ID, 'buyer_id', true );
          
          // Calculate used seats (this is an approximation - you may need to adjust based on your actual data structure)
          // Assuming you track assigned licenses per pass somehow
          $assigned_licenses = get_post_meta( $p->ID, 'llmsgaa_assigned_licenses', true );
          $used_seats = is_array( $assigned_licenses ) ? count( $assigned_licenses ) : 0;
          $available_seats = $total_seats - $used_seats;
          
          // Check if any item contains 'renewal' in the SKU and build product list
          $is_renewal = false;
          $products = array();
          
          if ( is_array( $items ) ) {
            foreach ( $items as $item ) {
              if ( isset( $item['sku'] ) ) {
                // Check for renewal
                if ( stripos( $item['sku'], 'renewal' ) !== false ) {
                  $is_renewal = true;
                }
                
                // Look up product name from SKU map
                if ( isset( $sku_map[ $item['sku'] ] ) ) {
                  $product_id = $sku_map[ $item['sku'] ];
                  $product_title = get_the_title( $product_id );
                  if ( $product_title ) {
                    $product_info = $product_title;
                    if ( isset( $item['quantity'] ) && $item['quantity'] > 1 ) {
                      $product_info .= ' (' . $item['quantity'] . ' seats)';
                    }
                    $products[] = $product_info;
                  }
                }
              }
            }
          }
        ?>
        <tr>
          <td style="width: 25%; vertical-align: top;">
            <?php echo esc_html( get_the_date( 'F j, Y', $p->ID ) ); ?>
          </td>
          <td style="width: 75%;">
            <div>
              <!-- Title of the post -->
              <strong><?php echo esc_html( get_the_title( $p ) ); ?></strong>
              
              <!-- Display renewal badge if applicable -->
              <?php if ( $is_renewal ) : ?>
                <span style="display: inline-block; background-color: #10b981; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; margin-left: 8px;">RENEWAL</span>
              <?php endif; ?>
              
              <!-- Buyer email -->
              <?php if ( $buyer_email ) : ?>
                <div style="color: #666; font-size: 0.875rem; margin-top: 4px;">
                  Purchaser: <?php echo esc_html( $buyer_email ); ?>
                </div>
              <?php endif; ?>
              
              <!-- Products purchased -->
              <?php if ( ! empty( $products ) ) : ?>
                <div style="color: #444; font-size: 0.875rem; margin-top: 4px;">
                  <strong>Products:</strong>
                  <?php foreach ( $products as $product ) : ?>
                    <div style="margin-left: 12px;">• <?php echo esc_html( $product ); ?></div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              
              <!-- Seats info -->
              <div style="color: #444; font-size: 0.875rem; margin-top: 4px;">
                <strong>Seats:</strong> 
                <?php echo esc_html( $total_seats ); ?> total
              </div>
              
              <!-- Redeem or Redeemed status -->
              <div style="margin-top: 8px;">
                <?php if ( $is_redeemed ) : ?>
                  <span class="text-green-600 font-medium">Redeemed ✅</span>
                <?php else : ?>
                  <a href="#" class="llmsgaa-redeem-btn text-blue-600 hover:text-blue-800 hover:underline" data-pass-id="<?php echo esc_attr( $p->ID ); ?>">
                    Choose Start Date
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>


<!-- Members Management Header -->
<div class="llmsgaa-box">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 class="text-xl font-semibold">Members & License Management</h2>
    
    <!-- Action Buttons -->
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
      <!-- Add Member Button -->
      <button id="llmsgaa-add-member-btn" class="btn btn-primary">
        <span class="btn-icon">➕</span> Add Member
      </button>
      
      <!-- Bulk Assign Button -->
      <button id="llmsgaa-bulk-assign-btn" class="btn btn-primary" style="display: none;">
        <span class="btn-icon">📋</span> Bulk Assign
      </button>
      
      <!-- CSV Import Button -->
      <button id="llmsgaa-csv-import-btn" class="btn btn-secondary">
        <span class="btn-icon">📁</span> Import CSV
      </button>
    </div>
  </div>
  
  <!-- Bulk Actions Bar (shows when members are selected) -->
  <div id="bulk-actions-bar" style="display: none; padding: 15px; background: #e3f2fd; border-radius: 6px; margin-bottom: 15px;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <div>
        <strong><span id="selected-count">0</span> member(s) selected</strong>
      </div>
      <div style="display: flex; gap: 10px;">
        <button id="bulk-assign-licenses-btn" class="btn btn-sm btn-success">
          <span class="btn-icon">🎫</span> Assign Licenses
        </button>
        <button id="bulk-remove-members-btn" class="btn btn-sm btn-danger">
          <span class="btn-icon">🗑</span> Remove Selected
        </button>
        <button id="clear-selection-btn" class="btn btn-sm btn-outline">
          <span class="btn-icon">✖</span> Clear Selection
        </button>
      </div>
    </div>
  </div>
  
  <!-- Available Licenses Summary (if you want to show it here too) -->
  <?php if ( isset( $available_licenses ) && count( $available_licenses ) > 0 ): ?>
    <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded" style="margin-bottom: 15px;">
      <strong>📊 Quick Stats:</strong>
      <ul class="mt-2 text-sm" style="margin: 8px 0; padding-left: 20px;">
        <li>Total Members: <?php echo count( $all_members ); ?></li>
        <li>Available Licenses: <?php echo count( $available_licenses ); ?></li>
        <?php 
        $license_summary = [];
        foreach ( $available_licenses as $license ) {
          $key = $license->course_title;
          $license_summary[$key] = ($license_summary[$key] ?? 0) + 1;
        }
        foreach ( $license_summary as $course => $count ): ?>
          <li><?php echo esc_html( $course . ': ' . $count . ' license' . ($count > 1 ? 's' : '') ); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>









  
    <!-- Members Table -->
    <div class="overflow-x-auto">
      <table class="llmsgaa-table w-full text-sm">

      <thead>
  <tr>
    <th><input type="checkbox" id="select-all-members" /></th>
    <th>Name / Email</th>
    <th>Role</th>
    <th>Last Login</th>
    <th>Course Access & Dates</th>
    <th>Licenses</th>
    <th>Actions</th>
  </tr>
</thead>
        <tbody>
          <?php if ( empty( $all_members ) ): ?>
            <tr>
<td colspan="7" class="text-center text-gray-500 py-4">
                No members found. Add your first member using the "Add Member" button above.
              </td>
            </tr>
          <?php else: ?>
<?php foreach ( $all_members as $member ): 
  // Get detailed course access for this member
  $course_access = LLMSGAA\Feature\Shortcodes\UnifiedMemberManager::get_member_course_access( $group_id, $member['email'] );
?>
<tr class="member-row" data-email="<?php echo esc_attr( $member['email'] ); ?>" data-user-id="<?php echo esc_attr( $member['user_id'] ?: '' ); ?>">
  <td>
    <input type="checkbox" class="member-checkbox" value="<?php echo esc_attr( $member['email'] ); ?>" />
  </td>
  <td>
    <div>
      <strong><?php echo esc_html( $member['name'] ); ?></strong><br>
      <span class="text-gray-600"><?php echo esc_html( $member['email'] ); ?></span>
    </div>
  </td>
  <td>
    <select class="role-select border rounded px-2 py-1" data-email="<?php echo esc_attr( $member['email'] ); ?>" data-user-id="<?php echo esc_attr( $member['user_id'] ?: '' ); ?>">
        <option value="member" <?php selected( $member['role'], 'member' ); ?>>Member</option>
        <option value="admin" <?php selected( $member['role'], 'admin' ); ?>>Admin</option>
    </select>
    <?php if ( $member['role'] === 'admin' ): ?>
        <span class="role-badge" style="background: #007cba; color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px; margin-left: 5px;">ADMIN</span>
    <?php endif; ?>
  </td>
<td>
  <?php 
  // Get the last login from the member array
  $last_login = $member['last_login'] ?? 'Never';
  
  // Determine color and icon based on recency
  $color_class = '';
  $icon = '';
  
  if ($last_login === 'Just now' || strpos($last_login, 'min ago') !== false) {
      $color_class = 'color: #059669; font-weight: 600;'; // Green - online/recent
      $icon = '🟢';
  } elseif (strpos($last_login, 'hour') !== false || $last_login === 'Yesterday') {
      $color_class = 'color: #2563eb;'; // Blue - recent
  } elseif (strpos($last_login, 'days ago') !== false) {
      $color_class = 'color: #6b7280;'; // Gray - few days
  } elseif (strpos($last_login, 'week') !== false) {
      $color_class = 'color: #ea580c;'; // Orange - weeks
  } elseif ($last_login === 'Never') {
      $color_class = 'color: #dc2626; font-weight: 600;'; // Red - never
      $icon = '⚠️';
  } elseif ($last_login === 'Invite pending') {
      $color_class = 'color: #ca8a04;'; // Yellow - pending
      $icon = '📧';
  } else {
      $color_class = 'color: #6b7280;'; // Gray - old dates
  }
  ?>
  <span style="<?php echo $color_class; ?> font-size: 13px;">
    <?php echo esc_html($last_login); ?> <?php echo $icon; ?>
  </span>
</td>
  
  <!-- NEW: Course Access & Dates Column -->
  <td class="course-access-cell">
    <?php if ( empty( $course_access ) ): ?>
      <div class="no-access">
        <span class="text-gray-500 text-sm">No course access</span>
      </div>
    <?php else: ?>
      <div class="course-list">
        <?php foreach ( $course_access as $access ): ?>
          <div class="course-item">
            <div class="course-name">
              <strong><?php echo esc_html( $access['course_title'] ); ?></strong>
              <span class="status-indicator"><?php echo $access['status_indicator']; ?></span>
            </div>
            <div class="course-dates">
              <?php if ( $access['start_date'] ): ?>
                <span class="date-range">
                  📅 <?php echo esc_html( $access['start_date'] ); ?>
                  <?php if ( $access['end_date'] ): ?>
                    → <?php echo esc_html( $access['end_date'] ); ?>
                  <?php else: ?>
                    → Ongoing
                  <?php endif; ?>
                </span>
              <?php else: ?>
                <span class="date-range text-gray-500">No dates set</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </td>
  
  <td>
    <span class="license-count font-medium"><?php echo esc_html( $member['license_count'] ); ?></span>
    <?php if ( $member['license_count'] > 0 ): ?>
      <button class="btn btn-xs btn-outline view-licenses-btn" data-email="<?php echo esc_attr( $member['email'] ); ?>">
        <span class="btn-icon">👁</span> View
      </button>
    <?php endif; ?>
  </td>
  <td class="action-buttons">
    <button class="btn btn-sm btn-primary assign-license-btn" data-email="<?php echo esc_attr( $member['email'] ); ?>">
      <span class="btn-icon">🎫</span> Assign
    </button>
    
    <?php if ( $member['status'] === 'pending' ): ?>
      <button class="btn btn-sm btn-danger cancel-invite-btn" data-invite-id="<?php echo esc_attr( $member['invite_id'] ); ?>">
        <span class="btn-icon">✖</span> Cancel
      </button>
    <?php else: ?>
      <button class="btn btn-sm btn-danger remove-member-btn" data-user-id="<?php echo esc_attr( $member['user_id'] ); ?>">
        <span class="btn-icon">🗑</span> Remove
      </button>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Modals and JavaScript will go here next -->
<style>
/* Enhanced Button System */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  text-decoration: none;
}

.btn:active {
  transform: translateY(0);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Button Sizes */
.btn-xs {
  padding: 4px 8px;
  font-size: 12px;
  gap: 4px;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 13px;
  gap: 4px;
}

/* Button Colors */
.btn-primary {
  background: linear-gradient(135deg, #0073aa, #005a87);
  color: white;
}

.btn-primary:hover {
  background: linear-gradient(135deg, #005a87, #004568);
  color: white;
}

.btn-success {
  background: linear-gradient(135deg, #28a745, #1e7e34);
  color: white;
}

.btn-success:hover {
  background: linear-gradient(135deg, #1e7e34, #155724);
  color: white;
}

.btn-danger {
  background: linear-gradient(135deg, #dc3545, #c82333);
  color: white;
}

.btn-danger:hover {
  background: linear-gradient(135deg, #c82333, #a71e2a);
  color: white;
}

.btn-secondary {
  background: linear-gradient(135deg, #6c757d, #545b62);
  color: white;
}

.btn-secondary:hover {
  background: linear-gradient(135deg, #545b62, #383d41);
  color: white;
}

.btn-outline {
  background: transparent;
  border: 1px solid #dee2e6;
  color: #495057;
}

.btn-outline:hover {
  background: #f8f9fa;
  border-color: #adb5bd;
  color: #495057;
}

/* Button Icons */
.btn-icon {
  font-size: 0.9em;
  line-height: 1;
}

/* Action Buttons Container */
.action-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.action-buttons .btn {
  margin: 0;
}

/* Modal Buttons */
.modal-btn-primary {
  background: linear-gradient(135deg, #0073aa, #005a87);
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.modal-btn-primary:hover {
  background: linear-gradient(135deg, #005a87, #004568);
  transform: translateY(-1px);
}

.modal-btn-secondary {
  background: #6c757d;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-right: 10px;
}

.modal-btn-secondary:hover {
  background: #545b62;
  transform: translateY(-1px);
}

/* Existing styles (keep these) */
.llmsgaa-box {
  background: white;
  border: 1px solid #e1e5e9;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
}

.llmsgaa-table {
  border-collapse: collapse;
}

.llmsgaa-table th,
.llmsgaa-table td {
  border: 1px solid #e1e5e9;
  padding: 8px 12px;
  text-align: left;
  vertical-align: middle;
}

.llmsgaa-table th {
  background-color: #f8f9fa;
  font-weight: 600;
}

.member-row:hover {
  background-color: #f8f9fa;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .action-buttons {
    flex-direction: column;
    align-items: stretch;
    gap: 4px;
  }
  
  .action-buttons .btn {
    width: 100%;
    justify-content: center;
  }
  
  .flex.gap-3 {
    flex-direction: column;
    gap: 8px;
  }
  
  .flex.gap-3 .btn {
    width: 100%;
    justify-content: center;
  }

  /* Add this to your existing CSS section */

/* Table Scrolling */
.overflow-x-auto {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.llmsgaa-table {
  min-width: 1200px; /* Ensure table is wide enough to trigger scroll */
  border-collapse: collapse;
}

/* Course Access Column Styling */
.course-access-cell {
  min-width: 250px;
  max-width: 300px;
  padding: 8px 12px;
}

.course-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.course-item {
  padding: 8px;
  background: #f8f9fa;
  border-radius: 4px;
  border-left: 3px solid #0073aa;
  font-size: 12px;
}

.course-name {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 4px;
  font-weight: 600;
  color: #333;
}

.status-indicator {
  font-size: 10px;
  white-space: nowrap;
}

.course-dates {
  color: #666;
  font-size: 11px;
}

.date-range {
  display: inline-block;
  padding: 2px 6px;
  background: #e9ecef;
  border-radius: 3px;
  white-space: nowrap;
}

.no-access {
  text-align: center;
  padding: 16px 8px;
  color: #6c757d;
  font-style: italic;
}

/* Status-based border colors */
.course-item.expired {
  border-left-color: #dc3545;
}

.course-item.active {
  border-left-color: #28a745;
}

.course-item.pending {
  border-left-color: #ffc107;
}

/* Responsive table improvements */
@media (max-width: 768px) {
  .llmsgaa-table {
    min-width: 800px; /* Smaller minimum on mobile */
  }
  
  .course-access-cell {
    min-width: 200px;
    max-width: 250px;
  }
  
  .course-item {
    padding: 6px;
    font-size: 11px;
  }
}

/* Scroll hint for users */
.overflow-x-auto::after {
  content: "← Scroll horizontally to see all columns →";
  display: block;
  text-align: center;
  font-size: 12px;
  color: #6c757d;
  padding: 8px;
  background: #f8f9fa;
  border-top: 1px solid #dee2e6;
}

@media (min-width: 1300px) {
  .overflow-x-auto::after {
    display: none; /* Hide scroll hint on large screens */
  }
}
}
</style>

<script>
// Pass PHP data to JavaScript
window.llmsgaa_group_id = <?php echo json_encode( $group_id ); ?>;
window.llmsgaa_nonce = <?php echo json_encode( wp_create_nonce( 'llmsgaa_unified_actions' ) ); ?>;

if (typeof ajaxurl === 'undefined') {
    var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
}

const groupId = <?php echo json_encode( $group_id ); ?>;
const ajaxNonce = '<?php echo wp_create_nonce( 'llmsgaa_unified_actions' ); ?>';

jQuery(document).ready(function($) {
    
    // ========== EXISTING HANDLERS ==========
    
    // Add Member Button
    $('#llmsgaa-add-member-btn').on('click', function(e) {
        e.preventDefault();
        showAddMemberModal();
    });
    
    // Assign License Button
    $('.assign-license-btn').on('click', function(e) {
        e.preventDefault();
        const email = $(this).data('email');
        const memberName = $(this).closest('.member-row').find('strong').text();
        loadAndShowLicenses(email, memberName);
    });
    
    // View Licenses Button
    $('.view-licenses-btn').on('click', function(e) {
        e.preventDefault();
        const email = $(this).data('email');
        const memberName = $(this).closest('.member-row').find('strong').text();
        showMemberLicenses(email, memberName);
    });
    
    // ========== NEW BULK ASSIGN & CSV HANDLERS ==========
    
    // Bulk Assign Button Handler
    $(document).on('click', '#llmsgaa-bulk-assign-btn', function(e) {
        e.preventDefault();
        
        // Get selected members
        const selectedEmails = $('.member-checkbox:checked').map(function() {
            return this.value;
        }).get();
        
        if (selectedEmails.length === 0) {
            alert('Please select at least one member to assign licenses to.');
            return;
        }
        
        showBulkAssignModal(selectedEmails);
    });

    // CSV Import Button Handler
    $(document).on('click', '#llmsgaa-csv-import-btn', function(e) {
        e.preventDefault();
        showCSVImportModal();
    });

    // Select All Checkbox Handler
    $(document).on('change', '#select-all-members', function() {
        $('.member-checkbox').prop('checked', this.checked);
        updateBulkAssignButton();
    });



// Update selection summary
function updateLicenseSelectionSummary() {
    let totalSelected = 0;
    
    // Count from quantity selectors
    $('.license-quantity-selector').each(function() {
        totalSelected += parseInt($(this).val()) || 0;
    });
    
    // Count from single checkboxes
    $('.license-single-checkbox:checked').each(function() {
        totalSelected += 1;
    });
    
    $('#selected-license-count').text(totalSelected);
    
    if (totalSelected > 0) {
        $('#license-selection-summary').show();
    } else {
        $('#license-selection-summary').hide();
    }
}

// Process grouped license assignment
window.assignGroupedLicenses = function(email) {
    const selectedLicenses = [];
    
    // Collect from quantity selectors
    $('.license-quantity-selector').each(function() {
        const quantity = parseInt($(this).val()) || 0;
        if (quantity > 0) {
            const groupKey = $(this).data('group');
            const licenseIds = JSON.parse($(`.license-group-data[data-group="${groupKey}"]`).attr('data-licenses'));
            // Take only the quantity requested
            for (let i = 0; i < quantity && i < licenseIds.length; i++) {
                selectedLicenses.push(licenseIds[i]);
            }
        }
    });
    
    // Collect from single checkboxes
    $('.license-single-checkbox:checked').each(function() {
        const groupKey = $(this).data('group');
        const licenseIds = JSON.parse($(`.license-group-data[data-group="${groupKey}"]`).attr('data-licenses'));
        if (licenseIds.length > 0) {
            selectedLicenses.push(licenseIds[0]);
        }
    });
    
    if (selectedLicenses.length === 0) {
        alert('Please select at least one license to assign.');
        return;
    }
    
    // Disable submit button and show loading
    const submitBtn = $('.modal-btn-primary');
    const originalText = submitBtn.html();
    submitBtn.prop('disabled', true).html('⏳ Assigning...');
    
    $.post(ajaxurl, {
        action: 'llmsgaa_assign_licenses',
        email: email,
        license_ids: selectedLicenses,
        nonce: ajaxNonce
    }, function(response) {
        if (response.success) {
            showSuccessMessage(response.data);
            closeModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('Error: ' + response.data);
            submitBtn.prop('disabled', false).html(originalText);
        }
    }).fail(function() {
        alert('Network error occurred');
        submitBtn.prop('disabled', false).html(originalText);
    });
};





    // Individual Checkbox Handler
    $(document).on('change', '.member-checkbox', function() {
        updateBulkAssignButton();
        
        // Update select-all checkbox state
        const totalCheckboxes = $('.member-checkbox').length;
        const checkedCheckboxes = $('.member-checkbox:checked').length;
        
        $('#select-all-members').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
        $('#select-all-members').prop('checked', checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0);
    });
    
    // Store initial role values when page loads
    $('.role-select').each(function() {
        $(this).data('previous-value', $(this).val());
    });
    
    // ========== MODAL FUNCTIONS ==========
    
    // Add Member Modal
    function showAddMemberModal() {
        const html = `
            <h3>Add New Member</h3>
            <form id="add-member-form">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Email Address:</label>
                    <input type="email" id="member-email" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Role:</label>
                    <select id="member-role" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                        <option value="member">Member</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div style="text-align: right; border-top: 1px solid #eee; padding-top: 15px;">
                    <button type="button" onclick="closeModal()" class="modal-btn-secondary">Cancel</button>
                    <button type="submit" class="modal-btn-primary">✅ Add Member</button>
                </div>
            </form>
        `;
        showModal(html);
        
        $('#add-member-form').on('submit', function(e) {
            e.preventDefault();
            const email = $('#member-email').val();
            const role = $('#member-role').val();
            
            // Disable submit button
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.prop('disabled', true).html('⏳ Adding...');
            
            $.post(ajaxurl, {
                action: 'llmsgaa_add_member',
                group_id: groupId,
                email: email,
                role: role,
                nonce: ajaxNonce
            }, function(response) {
                if (response.success) {
                    showSuccessMessage('Member added successfully!');
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Error: ' + response.data);
                    submitBtn.prop('disabled', false).html(originalText);
                }
            }).fail(function() {
                alert('Network error occurred');
                submitBtn.prop('disabled', false).html(originalText);
            });
        });
    }
    
    // Load and Show Available Licenses
    function loadAndShowLicenses(email, memberName) {
        $.post(ajaxurl, {
            action: 'llmsgaa_get_available_licenses',
            group_id: groupId,
            nonce: ajaxNonce
        }, function(response) {
            if (response.success) {
                showAssignLicenseModal(email, memberName, response.data);
            } else {
                alert('Error: ' + response.data);
            }
        });
    }
    
    // Show Assign License Modal
    function showAssignLicenseModal(email, memberName, licenses) {
    let html = `<h3>Assign License to ${memberName}</h3>`;
    html += `<div style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 6px; font-size: 13px; color: #666;">📧 ${email}</div>`;
    
    if (licenses.length === 0) {
        html += '<div style="text-align: center; padding: 40px; color: #666;">No available licenses to assign.</div>';
        html += '<div style="text-align: right; border-top: 1px solid #eee; padding-top: 15px;">';
        html += '<button onclick="closeModal()" class="modal-btn-secondary">Close</button></div>';
    } else {
        // Group licenses by course and start date
        const groupedLicenses = groupLicensesByCourseAndDate(licenses);
        
        html += '<div style="margin-bottom: 15px; font-weight: 600;">Select licenses to assign:</div>';
        html += '<div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 6px; padding: 10px;">';
        
        // Display grouped licenses
        Object.keys(groupedLicenses).forEach(groupKey => {
            const group = groupedLicenses[groupKey];
            const isMultiple = group.licenses.length > 1;
            
            html += '<div style="margin-bottom: 15px; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fafafa; transition: all 0.2s;" onmouseover="this.style.background=\'#f0f8ff\'; this.style.borderColor=\'#007cba\';" onmouseout="this.style.background=\'#fafafa\'; this.style.borderColor=\'#e0e0e0\';">';
            
            // Group header with quantity selector
            html += '<div style="display: flex; align-items: start; gap: 12px;">';
            
            // Course info
            html += '<div style="flex: 1;">';
            html += `<div style="font-weight: 600; color: #333; margin-bottom: 4px; font-size: 15px;">📚 ${group.courseTitle}</div>`;
            html += `<div style="color: #666; font-size: 13px;">🗓 Starts: ${group.startDate || 'Not set'}</div>`;
            if (group.endDate) {
                html += `<div style="color: #666; font-size: 13px;">⏰ Ends: ${group.endDate}</div>`;
            }
            html += `<div style="color: #007cba; font-size: 13px; margin-top: 4px;">✨ ${group.licenses.length} license${group.licenses.length > 1 ? 's' : ''} available</div>`;
            html += '</div>';
            
            // Quantity selector
            if (isMultiple) {
                html += '<div style="text-align: right; min-width: 120px;">';
                html += '<label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px;">Quantity:</label>';
                html += `<select class="license-quantity-selector" data-group="${groupKey}" style="padding: 4px 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">`;
                html += '<option value="0">None</option>';
                for (let i = 1; i <= group.licenses.length; i++) {
                    html += `<option value="${i}">${i} license${i > 1 ? 's' : ''}</option>`;
                }
                html += '</select>';
                html += '</div>';
            } else {
                // Single license checkbox
                html += '<div style="min-width: 40px; text-align: center; padding-top: 8px;">';
                html += `<input type="checkbox" class="license-single-checkbox" data-group="${groupKey}" style="transform: scale(1.3); cursor: pointer;">`;
                html += '</div>';
            }
            
            html += '</div>';
            
            // Store license IDs as data attribute
            html += `<div class="license-group-data" data-group="${groupKey}" data-licenses='${JSON.stringify(group.licenses.map(l => l.ID))}'></div>`;
            
            html += '</div>';
        });
        
        html += '</div>';
        
        // Summary section
        html += '<div id="license-selection-summary" style="margin-top: 15px; padding: 10px; background: #e3f2fd; border-radius: 6px; display: none;">';
        html += '<strong>Selected:</strong> <span id="selected-license-count">0</span> license(s)';
        html += '</div>';
        
        html += '<div style="text-align: right; border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px;">';
        html += '<button onclick="closeModal()" class="modal-btn-secondary">Cancel</button>';
        html += `<button onclick="assignGroupedLicenses('${email}')" class="modal-btn-primary">🎫 Assign Selected</button>`;
        html += '</div>';
    }
    
    showModal(html);
    
    // Add event listeners for selection changes
    setTimeout(() => {
        updateLicenseSelectionSummary();
        
        // Quantity selector change
        $('.license-quantity-selector').on('change', function() {
            updateLicenseSelectionSummary();
        });
        
        // Single checkbox change
        $('.license-single-checkbox').on('change', function() {
            updateLicenseSelectionSummary();
        });
    }, 100);
}

// Helper function to group licenses by course and start date
function groupLicensesByCourseAndDate(licenses) {
    const groups = {};
    
    licenses.forEach(license => {
        // Create a unique key for grouping
        const groupKey = `${license.product_id}_${license.start_date || 'no-date'}`;
        
        if (!groups[groupKey]) {
            groups[groupKey] = {
                courseTitle: license.course_title,
                productId: license.product_id,
                startDate: license.start_date_formatted || 'Not set',
                endDate: license.end_date_formatted || null,
                licenses: []
            };
        }
        
        groups[groupKey].licenses.push(license);
    });
    
    return groups;
}
    
    // Show Member's Current Licenses
    function showMemberLicenses(email, memberName) {
        $.post(ajaxurl, {
            action: 'llmsgaa_get_member_licenses',
            group_id: groupId,
            email: email,
            nonce: ajaxNonce
        }, function(response) {
            if (response.success) {
                let html = `<h3>Licenses for ${memberName}</h3>`;
                html += `<div style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 6px; font-size: 13px; color: #666;">📧 ${email}</div>`;
                
                if (response.data.length === 0) {
                    html += '<div style="text-align: center; padding: 40px; color: #666;">No licenses assigned yet.</div>';
                } else {
                    html += '<div style="max-height: 300px; overflow-y: auto;">';
                    response.data.forEach(license => {
                        html += '<div style="padding: 15px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 6px; background: #fafafa;">';
                        html += `<div style="font-weight: 600; color: #333; margin-bottom: 5px;">🎫 ${license.course_title}</div>`;
                        html += `<div style="font-size: 13px; color: #666; margin-bottom: 8px;">🗓 Start: ${license.start_date_formatted || 'Not set'}</div>`;
                        if (license.end_date_formatted) {
                            html += `<div style="font-size: 13px; color: #666; margin-bottom: 8px;">⏰ End: ${license.end_date_formatted}</div>`;
                        }
                        html += `<div style="font-size: 13px; color: #666; margin-bottom: 10px;">📊 Status: ${license.status || 'Active'}</div>`;
                        html += `<button onclick="removeLicense(${license.ID})" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#c82333'" onmouseout="this.style.background='#dc3545'">🗑 Remove License</button>`;
                        html += '</div>';
                    });
                    html += '</div>';
                }
                
                html += '<div style="text-align: right; border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px;">';
                html += '<button onclick="closeModal()" class="modal-btn-primary">Close</button></div>';
                showModal(html);
            } else {
                alert('Error: ' + response.data);
            }
        });
    }
    
    // ========== NEW BULK ASSIGN FUNCTIONS ==========
    
    // Update bulk assign button state
    function updateBulkAssignButton() {
        const selectedCount = $('.member-checkbox:checked').length;
        const bulkBtn = $('#llmsgaa-bulk-assign-btn');
        
        if (selectedCount > 0) {
            bulkBtn.removeClass('btn-primary').addClass('btn-success');
            bulkBtn.html(`<span class="btn-icon">📋</span> Bulk Assign (${selectedCount} selected)`);
        } else {
            bulkBtn.removeClass('btn-success').addClass('btn-primary');
            bulkBtn.html('<span class="btn-icon">📋</span> Bulk Assign');
        }
    }

    // Show Bulk Assign Modal
    function showBulkAssignModal(selectedEmails) {
        // Load available licenses first
        $.post(ajaxurl, {
            action: 'llmsgaa_get_available_licenses',
            group_id: groupId,
            nonce: ajaxNonce
        }, function(response) {
            if (response.success) {
                renderBulkAssignModal(selectedEmails, response.data);
            } else {
                alert('Error loading licenses: ' + response.data);
            }
        });
    }

    function renderBulkAssignModal(selectedEmails, licenses) {
        const memberCount = selectedEmails.length;
        const licenseCount = licenses.length;
        const maxAssignments = Math.min(memberCount, licenseCount);
        
        let html = `
            <h3>Bulk Assign Licenses</h3>
            <div style="margin-bottom: 15px; padding: 10px; background: #e3f2fd; border-radius: 6px; font-size: 14px;">
                📋 Assigning licenses to <strong>${memberCount} selected member${memberCount > 1 ? 's' : ''}</strong>
            </div>
            
            <div style="margin-bottom: 20px;">
                <h4 style="margin-bottom: 10px;">Selected Members:</h4>
                <div style="max-height: 100px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 13px;">
                    ${selectedEmails.map(email => `<div>📧 ${email}</div>`).join('')}
                </div>
            </div>
        `;
        
        if (licenses.length === 0) {
            html += '<div style="text-align: center; padding: 40px; color: #666;">No available licenses to assign.</div>';
            html += '<div style="text-align: right; border-top: 1px solid #eee; padding-top: 15px;">';
            html += '<button onclick="closeModal()" class="modal-btn-secondary">Close</button></div>';
        } else {
            // Show assignment preview
            html += '<div style="margin-bottom: 20px; padding: 15px; background: #fff3cd; border-radius: 6px;">';
            html += '<h4 style="margin: 0 0 10px 0; color: #856404;">📊 Assignment Preview:</h4>';
            html += '<p style="margin: 0 0 10px 0; color: #856404;">Licenses will be assigned in order:</p>';
            html += '<ul style="margin: 0; padding-left: 20px; color: #856404; font-size: 13px;">';
            html += `<li><strong>${licenseCount}</strong> available license${licenseCount > 1 ? 's' : ''}</li>`;
            html += `<li><strong>${memberCount}</strong> selected member${memberCount > 1 ? 's' : ''}</li>`;
            html += `<li><strong>${maxAssignments}</strong> assignment${maxAssignments > 1 ? 's' : ''} will be made</li>`;
            
            if (memberCount > licenseCount) {
                html += `<li style="color: #dc3545;">⚠️ ${memberCount - licenseCount} member${(memberCount - licenseCount) > 1 ? 's' : ''} won't receive a license</li>`;
            } else if (licenseCount > memberCount) {
                html += `<li style="color: #28a745;">✅ ${licenseCount - memberCount} license${(licenseCount - memberCount) > 1 ? 's' : ''} will remain available</li>`;
            }
            
            html += '</ul>';
            html += '</div>';
            
            // Show the licenses that will be assigned
            html += '<div style="margin-bottom: 15px;">';
            html += '<h4 style="margin-bottom: 10px;">🎫 Licenses to be assigned (in order):</h4>';
            html += '<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 6px; padding: 10px; background: #fafafa;">';
            
            licenses.slice(0, maxAssignments).forEach((license, index) => {
                const assignedTo = selectedEmails[index];
                html += '<div style="padding: 10px; border-bottom: 1px solid #eee; font-size: 13px;">';
                html += `<strong>${index + 1}. ${license.course_title}</strong><br>`;
                html += `<small style="color: #666;">🗓 Start: ${license.start_date_formatted || 'Not set'}</small><br>`;
                html += `<small style="color: #007cba;">→ Will be assigned to: ${assignedTo}</small>`;
                html += '</div>';
            });
            
            html += '</div>';
            html += '</div>';
            
            html += '<div style="text-align: right; border-top: 1px solid #eee; padding-top: 15px;">';
            html += '<button onclick="closeModal()" class="modal-btn-secondary">Cancel</button>';
            html += `<button onclick="processBulkAssignSequential()" class="modal-btn-primary">🎫 Confirm & Assign ${maxAssignments} License${maxAssignments > 1 ? 's' : ''}</button>`;
            html += '</div>';
        }
        
        showModal(html);
    }
    
    // ========== NEW CSV IMPORT FUNCTIONS ==========
    
    // Show CSV Import Modal
    function showCSVImportModal() {
        const html = `
            <h3>Import Members from CSV</h3>
            
            <div style="margin-bottom: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                <h4 style="margin: 0 0 10px 0; color: #856404;">📋 CSV Format Instructions</h4>
                <p style="margin: 0 0 10px 0; color: #856404; font-size: 14px;">
                    Your CSV should have one column with header: <strong>email</strong>
                </p>
                <div style="background: #fff; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; color: #333;">
                    email<br>
                    john@example.com<br>
                    sarah@example.com<br>
                    mike@example.com
                </div>
            </div>
            
            <form id="csv-import-form" enctype="multipart/form-data">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Select CSV File:</label>
                    <input type="file" id="csv-file" accept=".csv" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Default Role for New Members:</label>
                    <select id="csv-default-role" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="member">Member</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="csv-assign-licenses" style="transform: scale(1.2);">
                        <span style="font-weight: 600;">🎫 Assign licenses during import</span>
                    </label>
                    <small style="color: #666; margin-left: 30px; display: block; margin-top: 5px;">
                        Licenses will be assigned in order. If you have fewer licenses than members, some won't get assigned.
                    </small>
                </div>
                
                <div style="text-align: right; border-top: 1px solid #eee; padding-top: 15px;">
                    <button type="button" onclick="closeModal()" class="modal-btn-secondary">Cancel</button>
                    <button type="submit" class="modal-btn-primary">📤 Import Members</button>
                </div>
            </form>
        `;
        
        showModal(html);
        
        // Handle form submission
        $('#csv-import-form').on('submit', function(e) {
            e.preventDefault();
            processCSVImport();
        });
    }

    // Process CSV Import
    function processCSVImport() {
        const fileInput = document.getElementById('csv-file');
        const defaultRole = $('#csv-default-role').val();
        const assignLicenses = $('#csv-assign-licenses').is(':checked');
        
        if (!fileInput.files[0]) {
            alert('Please select a CSV file.');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'llmsgaa_import_csv');
        formData.append('csv_file', fileInput.files[0]);
        formData.append('default_role', defaultRole);
        formData.append('assign_licenses', assignLicenses ? '1' : '0');
        formData.append('group_id', groupId);
        formData.append('nonce', ajaxNonce);
        
        const submitBtn = $('#csv-import-form button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('⏳ Processing CSV...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showCSVResults(response.data);
                } else {
                    alert('Error: ' + response.data);
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert('Network error occurred');
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    }

    // Show CSV Import Results
    function showCSVResults(results) {
        let html = `
            <h3>CSV Import Results</h3>
            
            <div style="margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div style="text-align: center; padding: 15px; background: #d4edda; border-radius: 6px;">
                        <div style="font-size: 24px; font-weight: bold; color: #155724;">${results.added}</div>
                        <div style="font-size: 12px; color: #155724;">Members Added</div>
                    </div>
                    <div style="text-align: center; padding: 15px; background: #fff3cd; border-radius: 6px;">
                        <div style="font-size: 24px; font-weight: bold; color: #856404;">${results.existing}</div>
                        <div style="font-size: 12px; color: #856404;">Already Members</div>
                    </div>
                    <div style="text-align: center; padding: 15px; background: #e3f2fd; border-radius: 6px;">
                        <div style="font-size: 24px; font-weight: bold; color: #1565c0;">${results.licenses_assigned}</div>
                        <div style="font-size: 12px; color: #1565c0;">Licenses Assigned</div>
                    </div>
                    ${results.errors > 0 ? `
                    <div style="text-align: center; padding: 15px; background: #f8d7da; border-radius: 6px;">
                        <div style="font-size: 24px; font-weight: bold; color: #721c24;">${results.errors}</div>
                        <div style="font-size: 12px; color: #721c24;">Errors</div>
                    </div>` : ''}
                </div>
            </div>
        `;
        
        if (results.messages && results.messages.length > 0) {
            html += '<div style="margin-bottom: 20px;">';
            html += '<h4 style="margin-bottom: 10px;">📋 Detailed Results:</h4>';
            html += '<div style="max-height: 200px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 6px; font-size: 13px;">';
            results.messages.forEach(message => {
                const icon = message.includes('Error') ? '❌' : message.includes('exists') ? '⚠️' : '✅';
                html += `<div style="margin-bottom: 5px;">${icon} ${message}</div>`;
            });
            html += '</div>';
            html += '</div>';
        }
        
        html += '<div style="text-align: right; border-top: 1px solid #eee; padding-top: 15px;">';
        html += '<button onclick="closeModalAndReload()" class="modal-btn-primary">🎉 Done - Refresh Page</button>';
        html += '</div>';
        
        showModal(html);
    }
    
    // Generic Modal Function
    function showModal(html) {
        closeModal(); // Close any existing modal
        $('body').append(`
            <div id="llmsgaa-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">
                <div style="background: white; padding: 20px; border-radius: 8px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto;">
                    ${html}
                </div>
            </div>
        `);
    }
    
    // ========== GLOBAL FUNCTIONS ==========
    
    window.assignSelectedLicenses = function(email) {
        const selectedLicenses = $('.license-checkbox:checked').map(function() {
            return this.value;
        }).get();
        
        if (selectedLicenses.length === 0) {
            alert('Please select at least one license.');
            return;
        }
        
        $.post(ajaxurl, {
            action: 'llmsgaa_assign_licenses',
            email: email,
            license_ids: selectedLicenses,
            nonce: ajaxNonce
        }, function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    };
    
    // Process Bulk Assignment - OLD VERSION (kept for backwards compatibility)
    window.processBulkAssign = function() {
        const selectedLicense = $('input[name="bulk-license"]:checked').val();
        const selectedEmails = $('.member-checkbox:checked').map(function() {
            return this.value;
        }).get();
        
        if (!selectedLicense) {
            alert('Please select a license to assign.');
            return;
        }
        
        const submitBtn = $('.modal-btn-primary');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('⏳ Assigning licenses...');
        
        $.post(ajaxurl, {
            action: 'llmsgaa_bulk_assign_licenses',
            emails: selectedEmails,
            license_id: selectedLicense,
            group_id: groupId,
            nonce: ajaxNonce
        }, function(response) {
            if (response.success) {
                showSuccessMessage(`License assigned to ${selectedEmails.length} members!`);
                closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('Error: ' + response.data);
                submitBtn.prop('disabled', false).html(originalText);
            }
        }).fail(function() {
            alert('Network error occurred');
            submitBtn.prop('disabled', false).html(originalText);
        });
    };
    
    // NEW: Process Sequential Bulk Assignment
    window.processBulkAssignSequential = function() {
        const selectedEmails = $('.member-checkbox:checked').map(function() {
            return this.value;
        }).get();
        
        const submitBtn = $('.modal-btn-primary');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('⏳ Assigning licenses...');
        
        $.post(ajaxurl, {
            action: 'llmsgaa_bulk_assign_sequential',
            emails: selectedEmails,
            group_id: groupId,
            nonce: ajaxNonce
        }, function(response) {
            if (response.success) {
                showSuccessMessage(response.data.message || 'Licenses assigned successfully!');
                closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('Error: ' + response.data);
                submitBtn.prop('disabled', false).html(originalText);
            }
        }).fail(function(xhr) {
            console.error('Bulk assign failed:', xhr);
            alert('Network error occurred');
            submitBtn.prop('disabled', false).html(originalText);
        });
    };
    
    window.removeLicense = function(orderId) {
        if (!confirm('Are you sure you want to remove this license?')) return;
        
        $.post(ajaxurl, {
            action: 'llmsgaa_unassign_license',
            order_id: orderId,
            nonce: ajaxNonce
        }, function(response) {
            if (response.success) {
                alert('License removed successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    };
    
    window.closeModal = function() {
        $('#llmsgaa-modal').remove();
    };
    
    // Close modal and reload page
    window.closeModalAndReload = function() {
        closeModal();
        location.reload();
    };
    
    // Helper function to show success messages
    function showSuccessMessage(message) {
        // Remove any existing success messages
        $('.llmsgaa-success-message').remove();
        
        // Create new success message
        const successDiv = $(`
            <div class="llmsgaa-success-message" style="
                position: fixed; 
                top: 50px; 
                right: 20px; 
                z-index: 10000; 
                padding: 12px 20px; 
                background: #d4edda; 
                border: 1px solid #c3e6cb; 
                border-radius: 6px; 
                color: #155724;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                font-weight: 500;
            ">
                ✅ ${message}
            </div>
        `);
        
        $('body').append(successDiv);
        
        // Animate in
        successDiv.hide().fadeIn(200);
        
        // Remove after 4 seconds
        setTimeout(function() {
            successDiv.fadeOut(300, function() {
                successDiv.remove();
            });
        }, 4000);
    }
    
    // Helper function to update visual indicators
    function updateMemberRoleDisplay(memberRow, newRole) {
        // Example: Add a small admin badge
        const existingBadge = memberRow.find('.role-badge');
        existingBadge.remove();
        
        if (newRole === 'admin') {
            const adminBadge = $('<span class="role-badge" style="background: #007cba; color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px; margin-left: 5px;">ADMIN</span>');
            memberRow.find('strong').after(adminBadge);
        }
    }
});

// ========== ROLE CHANGE HANDLERS (Outside document ready) ==========

// Handle role changes
jQuery(document).on('change', '.role-select', function() {
    const selectElement = jQuery(this);
    const newRole = selectElement.val();
    const memberRow = selectElement.closest('.member-row');
    const email = memberRow.data('email');
    const userId = memberRow.data('user-id');
    const memberName = memberRow.find('strong').text();
    
    // Confirm the change
    if (!confirm(`Are you sure you want to change ${memberName}'s role to ${newRole}?`)) {
        // Reset to previous value if user cancels
        selectElement.val(selectElement.data('previous-value') || 'member');
        return;
    }
    
    // Disable the select while processing
    selectElement.prop('disabled', true);
    
    // Make AJAX request
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'llmsgaa_update_member_role',
            user_id: userId,
            group_id: groupId,
            email: email,
            role: newRole,
            nonce: ajaxNonce
        },
        success: function(response) {
            if (response.success) {
                // Show success message
                showSuccessMessage(`${memberName}'s role updated to ${newRole}`);
                
                // Store the new value as previous for next time
                selectElement.data('previous-value', newRole);
                
                // Optional: Update any visual indicators
                updateMemberRoleDisplay(memberRow, newRole);
                
            } else {
                // Reset to previous value on error
                selectElement.val(selectElement.data('previous-value') || 'member');
                alert('Error updating role: ' + response.data);
            }
        },
        error: function(xhr, status, error) {
            // Reset to previous value on error
            selectElement.val(selectElement.data('previous-value') || 'member');
            alert('AJAX error: ' + error);
        },
        complete: function() {
            // Re-enable the select
            selectElement.prop('disabled', false);
        }
    });
});

// Remove Member Button Handler
jQuery(document).on('click', '.remove-member-btn', function(e) {
    e.preventDefault();
    
    const button = jQuery(this);
    const memberRow = button.closest('.member-row');
    const email = memberRow.data('email');
    const userId = memberRow.data('user-id');
    const memberName = memberRow.find('strong').text();
    
    // Confirm removal
    const confirmMessage = `Are you sure you want to remove ${memberName} (${email}) from this group?\n\nThis will:\n- Remove them from the group\n- Unassign all their licenses\n- Cannot be undone`;
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    // Disable button and show loading
    const originalText = button.text();
    button.prop('disabled', true).text('Removing...');
    
    // Make AJAX request
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'llmsgaa_remove_member',
            user_id: userId,
            group_id: groupId,
            email: email,
            nonce: ajaxNonce
        },
        success: function(response) {
            if (response.success) {
                // Show success message
                showSuccessMessage(`${memberName} removed from group`);
                
                // Remove the row from the table with animation
                memberRow.fadeOut(300, function() {
                    memberRow.remove();
                    
                    // Check if table is now empty
                    const remainingRows = jQuery('.member-row').length;
                    if (remainingRows === 0) {
                        const emptyMessage = `
                            <tr>
                                <td colspan="6" class="text-center text-gray-500 py-4">
                                    No members found. Add your first member using the "Add Member" button above.
                                </td>
                            </tr>
                        `;
                        jQuery('.llmsgaa-table tbody').html(emptyMessage);
                    }
                });
                
            } else {
                alert('Error removing member: ' + response.data);
                button.prop('disabled', false).text(originalText);
            }
        },
        error: function(xhr, status, error) {
            console.error('Remove member AJAX error:', {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            
            alert('Error removing member: ' + error);
            button.prop('disabled', false).text(originalText);
        }
    });
});

// Cancel Invite Button Handler
jQuery(document).on('click', '.cancel-invite-btn', function(e) {
    e.preventDefault();
    
    const button = jQuery(this);
    const memberRow = button.closest('.member-row');
    const email = memberRow.data('email');
    const inviteId = button.data('invite-id');
    
    // Confirm cancellation
    if (!confirm(`Are you sure you want to cancel the invitation for ${email}?`)) {
        return;
    }
    
    // Disable button and show loading
    const originalText = button.text();
    button.prop('disabled', true).text('Cancelling...');
    
    // Make AJAX request
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'llmsgaa_cancel_invite',
            invite_id: inviteId,
            group_id: groupId,
            email: email,
            nonce: ajaxNonce
        },
        success: function(response) {
            if (response.success) {
                // Show success message
                showSuccessMessage(`Invitation for ${email} cancelled`);
                
                // Remove the row from the table with animation
                memberRow.fadeOut(300, function() {
                    memberRow.remove();
                    
                    // Check if table is now empty
                    const remainingRows = jQuery('.member-row').length;
                    if (remainingRows === 0) {
                        const emptyMessage = `
                            <tr>
                                <td colspan="6" class="text-center text-gray-500 py-4">
                                    No members found. Add your first member using the "Add Member" button above.
                                </td>
                            </tr>
                        `;
                        jQuery('.llmsgaa-table tbody').html(emptyMessage);
                    }
                });
                
            } else {
                alert('Error cancelling invitation: ' + response.data);
                button.prop('disabled', false).text(originalText);
            }
        },
        error: function(xhr, status, error) {
            console.error('Cancel invite AJAX error:', {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            
            alert('Error cancelling invitation: ' + error);
            button.prop('disabled', false).text(originalText);
        }
    });
});

// Declare these functions in global scope for the handlers to access
function showSuccessMessage(message) {
    // Remove any existing success messages
    jQuery('.llmsgaa-success-message').remove();
    
    // Create new success message
    const successDiv = jQuery(`
        <div class="llmsgaa-success-message" style="
            position: fixed; 
            top: 50px; 
            right: 20px; 
            z-index: 10000; 
            padding: 12px 20px; 
            background: #d4edda; 
            border: 1px solid #c3e6cb; 
            border-radius: 6px; 
            color: #155724;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            font-weight: 500;
        ">
            ✅ ${message}
        </div>
    `);
    
    jQuery('body').append(successDiv);
    
    // Animate in
    successDiv.hide().fadeIn(200);
    
    // Remove after 4 seconds
    setTimeout(function() {
        successDiv.fadeOut(300, function() {
            successDiv.remove();
        });
    }, 4000);
}

function updateMemberRoleDisplay(memberRow, newRole) {
    // Example: Add a small admin badge
    const existingBadge = memberRow.find('.role-badge');
    existingBadge.remove();
    
    if (newRole === 'admin') {
        const adminBadge = jQuery('<span class="role-badge" style="background: #007cba; color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px; margin-left: 5px;">ADMIN</span>');
        memberRow.find('strong').after(adminBadge);
    }
}
</script>