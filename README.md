# jut-so Shipment Tracking for WooCommerce

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0%2B-purple)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4)](https://php.net/)
[![License](https://img.shields.io/badge/License-Proprietary-red)]()

A minimalist and professional WordPress plugin that adds shipment tracking functionality to WooCommerce orders with full HPOS compatibility.

## Features

✓ **Order Meta Box** - Add tracking codes directly from the order edit page  
✓ **Multiple Tracking Numbers** - Support for multiple tracking numbers per order (comma-separated)  
✓ **REST API Support** - Full API endpoints for tracking management  
✓ **Email Integration** - Automatically include tracking info in order confirmation emails  
✓ **Dynamic Carrier Management** - Add, edit, and remove carriers via settings  
✓ **Default Carrier Support** - Set a default carrier for API operations  
✓ **Customizable Tracking URLs** - Configure tracking URL templates per carrier  
✓ **HPOS Compatible** - Full support for High-Performance Order Storage  
✓ **Multi-language Ready** - Fully translatable with included POT file  
✓ **Clean Settings Page** - Simple, intuitive configuration interface  
✓ **Validation** - Carrier validation to ensure data integrity

## Installation

1. Upload the plugin files to `/wp-content/plugins/jut-so-shipment-tracking/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings at WooCommerce → Shipment Tracking

## Configuration

### Settings Page
Navigate to **WooCommerce → Shipment Tracking** to configure:
- Enable/disable email integration
- Customize email text
- Set tracking info position in emails
- Configure custom tracking URL template

### Custom Tracking URL
Use `{tracking_number}` as a placeholder in your custom URL:
```
https://track.example.com/?tracking={tracking_number}
```

## REST API Endpoints

### Get Tracking Information
```
GET /wp-json/jutso-tracking/v1/orders/{order_id}/tracking
```

**Response:**
```json
{
    "order_id": 123,
    "tracking_number": "ABC123, DEF456",  // Comma-separated for multiple
    "carrier": "fedex",
    "date_added": "2024-01-20 10:30:00",
    "tracking_url": "...",  // Single URL (backward compatibility)
    "tracking_urls": {      // Multiple URLs when applicable
        "ABC123": "https://track.fedex.com/...",
        "DEF456": "https://track.fedex.com/..."
    }
}
```

### Add/Update Tracking
```
POST /wp-json/jutso-tracking/v1/orders/{order_id}/tracking
{
    "tracking_number": "ABC123, DEF456",  // Single or comma-separated
    "carrier": "fedex"  // optional, uses default if not specified
}
```

**Response:**
```json
{
    "success": true,
    "message": "Tracking information updated successfully",
    "data": {
        "order_id": 123,
        "tracking_number": "ABC123, DEF456",
        "carrier": "fedex",
        "tracking_url": "...",  // Single URL (backward compatibility)
        "tracking_urls": {      // Multiple URLs when applicable
            "ABC123": "https://track.fedex.com/...",
            "DEF456": "https://track.fedex.com/..."
        }
    }
}
```

### Remove Tracking
```
DELETE /wp-json/jutso-tracking/v1/orders/{order_id}/tracking
```

### Batch Update
```
POST /wp-json/jutso-tracking/v1/orders/batch
{
    "orders": [
        {
            "order_id": 123,
            "tracking_number": "ABC123, DEF456",  // Supports multiple
            "carrier": "fedex"
        },
        {
            "order_id": 124,
            "tracking_number": "XYZ789",
            "carrier": "ups"
        }
    ]
}
```

## Multiple Tracking Numbers

The plugin supports multiple tracking numbers per order:
- Enter multiple tracking numbers separated by commas in the admin interface
- All tracking numbers share the same carrier
- Each tracking number gets its own tracking button/link in emails and order pages
- API fully supports multiple tracking numbers in all endpoints

## Carrier Management

The plugin comes with DHL and FedEx pre-configured but allows you to:
- Add unlimited custom carriers
- Configure tracking URL templates for each carrier
- Set carrier keys for API integration
- Define a default carrier for API calls without carrier specification

## Hooks and Filters

The plugin integrates with standard WooCommerce hooks:
- `woocommerce_email_after_order_table`
- `woocommerce_email_before_order_table`
- `woocommerce_email_customer_details`
- `woocommerce_order_details_after_order_table`

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Translation

The plugin is translation-ready. POT file is located at:
`/languages/jut-so-shipment-tracking.pot`

## Uninstallation

The plugin includes a clean uninstall process that removes:
- All plugin options
- All order tracking metadata
- Clears cache

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For issues or feature requests, please use the [GitHub Issues](https://github.com/jut-so-team/jut-so-shipment-tracking/issues) page.

## Author

**Christopher Carus**  
Website: [https://jut-so.de](https://jut-so.de)

## License

This plugin is proprietary software. All rights reserved.

© 2024 jut-so Team. Unauthorized copying, modification, distribution, or use of this software is strictly prohibited without express written permission from jut-so Team.

For licensing inquiries, please contact info@jut-so.de.