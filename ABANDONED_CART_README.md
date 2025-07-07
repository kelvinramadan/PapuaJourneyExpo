# Abandoned Cart Tracking System - Papua Journey

## Overview
A comprehensive abandoned cart tracking system that monitors user behavior, captures abandonment data, and provides analytics and recovery tools for the Papua Journey e-commerce platform.

## Features Implemented

### 1. Data Collection
- **Cart Session Tracking**: Monitors user sessions and cart activities
- **Abandonment Detection**: Automatically detects when users abandon their carts
- **Reason Collection**: Survey popup to capture why users abandon carts
- **Comprehensive Logging**: Tracks timestamps, session duration, user actions

### 2. Analytics Dashboard
- **Real-time Statistics**: View abandonment rates, recovery rates, and lost revenue
- **Product Analysis**: See which products are most frequently abandoned
- **Timeline Analysis**: Track abandonment patterns over time
- **Reason Analytics**: Understand why customers abandon carts

### 3. Recovery System
- **Email Reminders**: Automated email reminders for abandoned carts
- **Recovery Tracking**: Monitor which recovery methods work best
- **Manual Triggers**: Admin can manually send reminder emails

## Installation

### 1. Database Setup
Run the SQL script to create the required tables:
```sql
-- Execute the contents of config/abandoned_cart_tables.sql
source config/abandoned_cart_tables.sql;
```

### 2. File Structure
The system adds the following files to your existing project:

```
/admin/
  ├── abandoned_cart_analytics.php      # Main admin dashboard
  ├── export_abandoned_cart_data.php    # CSV export functionality
  ├── send_cart_reminders.php          # Manual reminder trigger
  └── scripts/
      └── abandoned_cart_reminder.php   # Email reminder system

/api/
  ├── track_cart_session.php           # Session tracking API
  ├── track_cart_action.php            # Cart action tracking API
  └── track_abandonment.php            # Abandonment event API

/assets/
  ├── css/
  │   └── abandoned-cart-modal.css      # Survey modal styles
  └── js/
      └── abandoned-cart-tracker.js     # Frontend tracking script

/config/
  └── abandoned_cart_tables.sql        # Database schema
```

### 3. Configuration
Update the email reminder script with your domain:
```php
// In admin/scripts/abandoned_cart_reminder.php
private $base_url = 'https://yourdomain.com'; // Update this
```

## Usage

### For Administrators

1. **Access Analytics Dashboard**
   - Navigate to `/admin/abandoned_cart_analytics.php`
   - View comprehensive abandonment statistics
   - Filter data by time periods (1 day, 7 days, 30 days, 90 days)

2. **Send Manual Reminders**
   - Click "Kirim Email Reminder" button in the dashboard
   - System will send emails to users with abandoned carts from 1-24 hours ago

3. **Export Data**
   - Click "Export Data" to download CSV report
   - Includes all abandonment details and user information

### For Automated Reminders

Set up a cron job to run the reminder system:
```bash
# Send reminders every 30 minutes
*/30 * * * * /usr/bin/php /path/to/admin/scripts/abandoned_cart_reminder.php

# Or run daily at 9 AM
0 9 * * * /usr/bin/php /path/to/admin/scripts/abandoned_cart_reminder.php
```

## How It Works

### 1. Tracking User Behavior
- JavaScript automatically tracks when users visit cart pages
- Records all cart actions (add, remove, quantity changes)
- Monitors session activity and detects abandonment

### 2. Abandonment Detection
- Triggers after 5 minutes on cart page without checkout
- Triggers on page exit or 30 minutes of inactivity
- Shows survey popup to collect abandonment reasons

### 3. Recovery Process
- Email reminders sent 1-24 hours after abandonment
- Recovery tracked when users complete checkout
- Analytics show effectiveness of different recovery methods

## Database Schema

### Tables Created
- **`abandoned_carts`**: Main abandonment tracking
- **`cart_abandonment_reasons`**: Survey responses
- **`cart_recovery_attempts`**: Email/reminder tracking
- **`user_cart_sessions`**: Session and activity tracking

### Key Metrics Tracked
- Total abandonments and recovery rate
- Average cart value and total lost revenue
- Session duration and user behavior patterns
- Product-specific abandonment rates
- Reason analysis and trends

## Customization

### Survey Questions
Edit the survey modal in `assets/js/abandoned-cart-tracker.js`:
```javascript
// Modify the reason options in createAbandonmentSurveyModal()
<label><input type="radio" name="abandon_reason" value="custom_reason"> Your Custom Reason</label>
```

### Email Templates
Customize email content in `admin/scripts/abandoned_cart_reminder.php`:
```php
// Modify the HTML template in sendReminderEmail()
$message = "Your custom email template here...";
```

### Tracking Thresholds
Adjust timing in `assets/js/abandoned-cart-tracker.js`:
```javascript
this.inactivityThreshold = 30 * 60 * 1000; // 30 minutes
this.pageVisitThreshold = 5 * 60 * 1000;   // 5 minutes
```

## Performance Considerations

- Cart actions are batched and sent efficiently
- Database queries are optimized with proper indexes
- Email reminders are limited to prevent spam (max 50 per run)
- JSON data is used for flexible cart item storage

## Security Features

- All APIs require user authentication
- Admin functions require admin authentication
- SQL injection protection via prepared statements
- XSS protection with proper data sanitization

## Troubleshooting

### Common Issues

1. **JavaScript Not Loading**
   - Ensure `abandoned-cart-tracker.js` is included in cart pages
   - Check browser console for errors

2. **Emails Not Sending**
   - Verify PHP mail configuration
   - Check email server settings
   - Consider using PHPMailer for production

3. **Database Errors**
   - Ensure all tables are created properly
   - Check MySQL version compatibility (5.7+ recommended for JSON support)

### Debug Mode
Enable debug logging by adding to tracking APIs:
```php
error_log("Debug: " . json_encode($data));
```

## Support
For technical support or feature requests, please refer to the main project documentation or create an issue in the project repository.