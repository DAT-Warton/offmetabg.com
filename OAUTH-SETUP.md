# OAuth Social Login Implementation Guide

## Overview
This system supports social login with 7 OAuth providers:
- Google
- Facebook
- Instagram
- Discord
- Twitch
- TikTok
- Kick

## Features
- ✅ One-click social authentication
- ✅ Automatic account creation for new users
- ✅ Account linking for existing email addresses
- ✅ Profile picture sync from OAuth providers
- ✅ CSRF protection with state parameter
- ✅ Support for both traditional and OAuth-only accounts
- ✅ Beautiful responsive social login buttons

## Database Changes

### Migration for Existing Installations
Run the migration to add OAuth support to `customers` table:
```sql
-- Run this migration file:
migrations/add-oauth-support.sql
```

This adds:
- `oauth_provider` - Provider name (google, facebook, instagram, discord, twitch, tiktok, kick) or NULL
- `oauth_provider_id` - Unique user ID from the provider
- `avatar` - URL to profile picture
- Makes `password` field nullable for OAuth-only accounts

### New Schema
For new installations, the `postgresql-schema.sql` already includes OAuth columns.

## Environment Configuration

### Setup OAuth Credentials

1. **Copy the environment template** (if not done already):
```bash
cp .env.example .env
```

2. **Configure each OAuth provider** in `.env`:

#### Google OAuth
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a project and enable Google+ API
3. Create OAuth 2.0 credentials
4. Add authorized redirect URI: `https://yourdomain.com/auth-callback.php?provider=google`
5. Copy Client ID and Secret to `.env`:
```
GOOGLE_CLIENT_ID=your_client_id_here.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your_client_secret_here
GOOGLE_REDIRECT_URI=https://yourdomain.com/auth-callback.php?provider=google
```

#### Facebook OAuth
1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create an app with Facebook Login product
3. Add redirect URI: `https://yourdomain.com/auth-callback.php?provider=facebook`
4. Copy App ID and Secret:
```
FACEBOOK_APP_ID=your_app_id_here
FACEBOOK_APP_SECRET=your_app_secret_here
FACEBOOK_REDIRECT_URI=https://yourdomain.com/auth-callback.php?provider=facebook
```

#### Instagram OAuth
1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create Instagram Basic Display app
3. Add redirect URI: `https://yourdomain.com/auth-callback.php?provider=instagram`
4. Copy Client ID and Secret:
```
INSTAGRAM_CLIENT_ID=your_client_id_here
INSTAGRAM_CLIENT_SECRET=your_client_secret_here
INSTAGRAM_REDIRECT_URI=https://yourdomain.com/auth-callback.php?provider=instagram
```

#### Discord OAuth
1. Go to [Discord Developer Portal](https://discord.com/developers/applications)
2. Create an application
3. Add redirect URI: `https://yourdomain.com/auth-callback.php?provider=discord`
4. Copy Client ID and Secret:
```
DISCORD_CLIENT_ID=your_client_id_here
DISCORD_CLIENT_SECRET=your_client_secret_here
DISCORD_REDIRECT_URI=https://yourdomain.com/auth-callback.php?provider=discord
```

#### Twitch OAuth
1. Go to [Twitch Developers](https://dev.twitch.tv/console)
2. Register an application
3. Add redirect URI: `https://yourdomain.com/auth-callback.php?provider=twitch`
4. Copy Client ID and Secret:
```
TWITCH_CLIENT_ID=your_client_id_here
TWITCH_CLIENT_SECRET=your_client_secret_here
TWITCH_REDIRECT_URI=https://yourdomain.com/auth-callback.php?provider=twitch
```

#### TikTok OAuth
1. Go to [TikTok Developers](https://developers.tiktok.com/)
2. Create an app with Login Kit
3. Add redirect URI: `https://yourdomain.com/auth-callback.php?provider=tiktok`
4. Copy Client Key and Secret:
```
TIKTOK_CLIENT_KEY=your_client_key_here
TIKTOK_CLIENT_SECRET=your_client_secret_here
TIKTOK_REDIRECT_URI=https://yourdomain.com/auth-callback.php?provider=tiktok
```

#### Kick OAuth
1. Go to Kick developers portal (when available)
2. Register your application
3. Add redirect URI: `https://yourdomain.com/auth-callback.php?provider=kick`
4. Copy credentials:
```
KICK_CLIENT_ID=your_client_id_here
KICK_CLIENT_SECRET=your_client_secret_here
KICK_REDIRECT_URI=https://yourdomain.com/auth-callback.php?provider=kick
```

## File Structure

### New Files
- `includes/oauth.php` - OAuth handler class (450+ lines)
- `auth-callback.php` - OAuth callback handler
- `migrations/add-oauth-support.sql` - Database migration for OAuth support
- `OAUTH-SETUP.md` - This documentation file

### Modified Files
- `auth.php` - Added social login buttons for both login and register forms
- `assets/css/auth.css` - Added social button styling with brand colors
- `migrations/postgresql-schema.sql` - Added OAuth columns for new installations
- `.env.example` - Added OAuth credential placeholders

## How It Works

### Authentication Flow

1. **User clicks social login button** (e.g., "Continue with Google")
   - Links to `auth-callback.php?provider=google`

2. **Initial redirect** (no code parameter)
   - `OAuth` class generates authorization URL with:
     - Client ID
     - Redirect URI
     - Scopes (email, profile, etc.)
     - State parameter (CSRF protection)
   - User redirected to provider's login page

3. **User authorizes** on provider's site
   - Provider redirects back to `auth-callback.php?provider=google&code=xxx&state=xxx`

4. **Callback processing** (with code parameter)
   - Verify state parameter (CSRF protection)
   - Exchange authorization code for access token
   - Use access token to fetch user info from provider
   - Normalize user data to common format

5. **Account handling**
   - Check if OAuth user exists (by `oauth_provider` + `oauth_provider_id`)
   - If exists: Log them in
   - If not exists but email matches: Link OAuth to existing account
   - If new: Create new customer account with:
     - Auto-generated username (from email or provider name)
     - Email from provider
     - Profile picture from provider
     - OAuth provider info
     - No password (OAuth-only account)
     - Email automatically verified

6. **Session setup**
   - Set customer session variables
   - Mark as OAuth authenticated
   - Redirect to homepage

### Security Features

- **CSRF Protection**: State parameter validated on callback
- **Token Validation**: Access tokens verified with provider APIs
- **Secure Storage**: No OAuth tokens stored in database (stateless)
- **Email Verification**: OAuth accounts automatically verified
- **Account Linking**: Prevents duplicate accounts with same email

## Testing

### Local Testing with ngrok
OAuth providers require HTTPS redirect URIs. For local testing:

1. **Install ngrok**: `choco install ngrok` (Windows)
2. **Start your local server**: Port 8000
3. **Create tunnel**: `ngrok http 8000`
4. **Use ngrok URL** in OAuth app settings and `.env`:
   ```
   GOOGLE_REDIRECT_URI=https://abc123.ngrok.io/auth-callback.php?provider=google
   ```

### Testing Flow
1. Navigate to `auth.php`
2. Click a social login button
3. Authorize on provider's site
4. Should redirect back and create/login user
5. Check database for new customer with `oauth_provider` field set

### Debug Mode
Enable detailed logging in `includes/oauth.php`:
```php
// In OAuth class, add debug logging:
error_log("OAuth Debug: " . json_encode($userData));
```

Check PHP error logs for OAuth flow debugging.

## UI Customization

### Button Colors
Social button colors are in [assets/css/auth.css](assets/css/auth.css#L185-L245):
- Google: White background
- Facebook: #1877f2
- Instagram: Gradient (pink/purple)
- TikTok: Black
- Discord: #5865F2
- Twitch: #9146FF
- Kick: #53fc18 (bright green)

### Button Layout
Currently 2x4 grid (2 columns, 4 rows). Change in `auth.css`:
```css
.social-login {
    grid-template-columns: repeat(2, 1fr); /* Change to 3 for 3 columns */
}
```

### Icons
SVG icons embedded in [auth.php](auth.php#L140-L180). Replace with custom icons if needed.

## Account Management

### Mixed Authentication
Users can have both traditional password AND OAuth accounts linked to same email:
- Traditional login: username/password
- OAuth login: Any configured provider
- Account profile shows authentication methods available

### OAuth-Only Accounts
Accounts created via OAuth don't have passwords:
- `password` field is NULL in database
- Traditional login not available
- Can add password later via account settings (future feature)

### Profile Pictures
OAuth providers supply profile picture URL:
- Stored in `customers.avatar` column
- Displayed in user profile/navbar
- Updated on each OAuth login (stays fresh)

## Troubleshooting

### "Invalid OAuth provider" Error
- Provider name in URL doesn't match supported providers
- Check URL: `auth-callback.php?provider=google` (lowercase)

### "Failed to initialize OAuth" Error
- Missing environment variables in `.env`
- Check provider credentials are set correctly
- Verify `.env` file is loaded (check `includes/functions.php` for auto-load)

### "Invalid state parameter" Error
- CSRF protection triggered
- Usually happens if callback takes too long
- Session might have expired
- Clear cookies and try again

### "Failed to obtain access token" Error
- Authorization code expired (use immediately)
- Invalid client credentials
- Redirect URI mismatch between OAuth app and `.env`
- Check provider's developer console for error details

### "Failed to obtain user information" Error
- Access token invalid or expired
- Insufficient scopes requested
- Provider API temporarily down
- Check provider's API status page

### Redirect URI Mismatch
Most common OAuth error:
- Exact match required: `https://domain.com/auth-callback.php?provider=google`
- No trailing slashes
- HTTPS required in production
- Port numbers must match (for localhost)

### Provider-Specific Issues

**Google**: Requires verified domain for production. Use test users during development.

**Facebook/Instagram**: Requires app review for `email` permission in production. Use test users.

**Discord**: No special restrictions, works immediately.

**Twitch**: Requires verified redirect URIs, exact match critical.

**TikTok**: Login Kit has specific scopes, requires app approval.

**Kick**: API may have limited availability or require application.

## Production Deployment Checklist

- [ ] All OAuth apps created and configured
- [ ] Redirect URIs updated to production domain (HTTPS)
- [ ] `.env` file configured with production credentials
- [ ] `.env` file added to `.gitignore` (never commit secrets!)
- [ ] Database migration executed (`add-oauth-support.sql`)
- [ ] HTTPS enabled (required for OAuth)
- [ ] Test each OAuth provider
- [ ] Monitor error logs for OAuth failures
- [ ] Submit apps for review (Facebook, TikTok) if needed

## Future Enhancements

Possible improvements:
- [ ] Account settings page to link/unlink OAuth providers
- [ ] Add more providers (GitHub, Microsoft, LinkedIn)
- [ ] Profile picture upload for non-OAuth users
- [ ] OAuth refresh token support for extended sessions
- [ ] Admin dashboard to view OAuth usage statistics
- [ ] Email notification when OAuth provider is linked/unlinked

## Support

- OAuth class: [includes/oauth.php](includes/oauth.php)
- Callback handler: [auth-callback.php](auth-callback.php)
- UI implementation: [auth.php](auth.php#L140-L180)
- Database schema: [migrations/postgresql-schema.sql](migrations/postgresql-schema.sql#L46-L74)

For issues, check PHP error logs and OAuth provider developer consoles.
