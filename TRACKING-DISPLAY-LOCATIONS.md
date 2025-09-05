# Tracking Display Locations

This document lists all locations where tracking information is displayed in the jut-so Shipment Tracking plugin and confirms that multiple tracking numbers are properly handled.

## Display Locations

### 1. Admin Order Meta Box ✅
**File:** `includes/class-jutso-admin.php` (lines 87-120)
- **Status:** Fully supports multiple tracking numbers
- **Implementation:** Each tracking number gets its own button with the tracking number as the button text
- **Code:** Loops through comma-separated tracking numbers and creates individual buttons

### 2. Customer Emails ✅
**File:** `includes/class-jutso-emails.php` (lines 67-156)
- **Status:** Fully supports multiple tracking numbers
- **Implementation:** 
  - Plain text emails: Lists each tracking number with a bullet point and URL
  - HTML emails: Shows individual buttons for each tracking number
- **Hooks:**
  - `woocommerce_email_before_order_table`
  - `woocommerce_email_after_order_table`
  - `woocommerce_email_customer_details`

### 3. Customer Order Page (My Account) ✅
**File:** `includes/class-jutso-emails.php` (line 36, method at lines 63-65)
- **Status:** Fully supports multiple tracking numbers
- **Implementation:** Uses the same `display_tracking_info()` method as emails
- **Hook:** `woocommerce_order_details_after_order_table`

### 4. REST API Endpoints ✅
**File:** `includes/class-jutso-api.php`

#### GET /orders/{id}/tracking (lines 86-110)
- **Status:** Fully supports multiple tracking numbers
- **Response Fields:**
  - `tracking_url`: Single URL for backward compatibility (empty if multiple)
  - `tracking_urls`: Array of tracking numbers mapped to their URLs

#### POST /orders/{id}/tracking (lines 112-183)
- **Status:** Fully supports multiple tracking numbers
- **Response Fields:**
  - `tracking_url`: Single URL for backward compatibility (empty if multiple)
  - `tracking_urls`: Array of tracking numbers mapped to their URLs

#### POST /orders/batch (lines 202-279)
- **Status:** Supports multiple tracking numbers
- **Note:** Does not return tracking URLs (acceptable for batch operations)

## Helper Methods

### `get_tracking_url()` (Single)
- **Used in:** Admin meta box, emails
- **Purpose:** Gets URL for a single tracking number

### `get_tracking_urls()` (Multiple)
- **Used in:** API responses
- **Purpose:** Gets array of all tracking URLs keyed by tracking number

## Data Storage
- Multiple tracking numbers are stored as comma-separated values in the `_jutso_tracking_number` meta field
- Format: `"TRACK001, TRACK002, TRACK003"`
- All tracking numbers share the same carrier

## Summary
✅ **All display locations properly handle multiple tracking numbers**
- Admin interface shows individual buttons
- Emails show individual tracking links/buttons
- Customer order page shows individual tracking links/buttons
- API returns both single URL (backward compatibility) and array of URLs
- No additional display locations found that need updating