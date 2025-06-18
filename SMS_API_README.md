# SMS Blast API Integration

## Overview
This SMS blast system supports multiple SMS gateway providers to ensure reliable message delivery. The current implementation includes three SMS APIs:
1. Infobip
2. Twilio
3. TextMagic

## Configuration

### API Credentials
Open `sms_blast_handler.php` and replace the following placeholders with your actual API credentials:

#### Infobip
```php
define('INFOBIP_API_KEY', 'your_infobip_api_key');
define('INFOBIP_API_BASE_URL', 'your_infobip_base_url');
```

#### Twilio
```php
define('TWILIO_ACCOUNT_SID', 'your_twilio_account_sid');
define('TWILIO_AUTH_TOKEN', 'your_twilio_auth_token');
define('TWILIO_PHONE_NUMBER', '+1234567890'); // Your Twilio phone number
```

#### TextMagic
```php
define('TEXTMAGIC_USERNAME', 'your_textmagic_username');
define('TEXTMAGIC_API_KEY', 'your_textmagic_api_key');
```

## Fallback Mechanism
The SMS sending process uses a fallback mechanism:
1. First, it attempts to send via Infobip
2. If Infobip fails, it tries Twilio
3. If Twilio fails, it attempts TextMagic
4. If all APIs fail, it returns the last error

## Phone Number Formatting
- The system automatically formats phone numbers to the Philippines country code (+63)
- Removes non-numeric characters
- Adds the +63 prefix if not already present

## Error Handling
- All API errors are logged in the system error log
- Failed SMS attempts are tracked for troubleshooting

## Recommended Setup
1. Sign up for accounts with multiple SMS gateway providers
2. Configure API credentials in the script
3. Test each API integration individually
4. Monitor error logs for any delivery issues

## Troubleshooting
- Check error logs for specific API error messages
- Verify API credentials
- Ensure sufficient credits/balance in your SMS gateway accounts
- Validate phone number formats

## Security Notes
- Keep API credentials confidential
- Use environment variables or a secure configuration management system in production
- Regularly rotate API keys

## Future Improvements
- Add more SMS gateway providers
- Implement more sophisticated fallback and retry mechanisms
- Create a configuration interface for managing SMS API settings 