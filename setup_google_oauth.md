# Google OAuth Setup Guide for Chamber Request System

## Current Status
✅ **Demo Mode Working**: Google buttons work in demo mode for testing  
⚠️ **Real OAuth Needs Setup**: Replace placeholder credentials with real ones

## Quick Setup Steps

### 1. Get Google OAuth Credentials
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable **Google+ API** and **Google Identity Services**
4. Go to **Credentials** → **Create Credentials** → **OAuth 2.0 Client ID**
5. Choose **Web application**
6. Add authorized origins:
   - `http://localhost`
   - `http://127.0.0.1`
   - `https://yourdomain.com` (your production domain)
7. Add authorized redirect URIs:
   - `http://localhost/chamber_request_system/google_callback.php`
   - `https://yourdomain.com/chamber_request_system/google_callback.php`

### 2. Update Code with Real Credentials

Replace this line in **both** `login.php` and `register.php`:
```javascript
const GOOGLE_CLIENT_ID = '1234567890-abcdefghijklmnopqrstuvwxyz123456.apps.googleusercontent.com';
```

With your real Client ID:
```javascript
const GOOGLE_CLIENT_ID = 'YOUR_ACTUAL_CLIENT_ID_HERE.apps.googleusercontent.com';
```

Update `google_callback.php` with your Client Secret:
```php
$client_secret = 'YOUR_ACTUAL_CLIENT_SECRET_HERE';
```

### 3. Test the Integration
1. Click Google sign-in/sign-up buttons
2. Should open real Google OAuth popup
3. After authorization, redirects to correct dashboard based on role

## Current Demo Mode Features
- ✅ **Auto-creates demo user** after 1.5 seconds
- ✅ **Role-based redirects** work correctly  
- ✅ **Loading animations** and button states
- ✅ **Fallback mechanisms** if OAuth fails
- ✅ **Password visibility toggles** on both pages

## Files Modified
- `login.php` - Smart OAuth detection and demo fallback
- `register.php` - Same OAuth improvements  
- `google_auth.php` - Returns user role for proper redirects
- `google_callback.php` - Handles popup OAuth flow

## Security Notes
- Never commit real credentials to version control
- Use environment variables in production
- Ensure HTTPS for production OAuth flows
- Demo mode automatically disabled when real credentials added
