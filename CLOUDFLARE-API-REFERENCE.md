# Cloudflare API Reference for offmetabg.com

## Zone Information
- **Zone ID:** `726f6033454c792cbe0ec3de8524e462`
- **Domain:** `offmetabg.com`
- **API Base:** `https://api.cloudflare.com/client/v4`

## Authentication
Use one of the following methods:

### Option 1: Global API Key (Legacy)
```bash
-H "X-Auth-Email: datwarton@gmail.com" \
-H "X-Auth-Key: YOUR_GLOBAL_API_KEY"
```

### Option 2: API Token (Recommended)
```bash
-H "Authorization: Bearer YOUR_API_TOKEN"
```

## Cache Management API Endpoints

### 1. Purge Cache by Files, Tags, or Host
**Endpoint:** `POST /zones/726f6033454c792cbe0ec3de8524e462/purge_cache`

#### Purge Everything
```bash
curl -X POST "https://api.cloudflare.com/client/v4/zones/726f6033454c792cbe0ec3de8524e462/purge_cache" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'
```

#### Purge by Files
```bash
curl -X POST "https://api.cloudflare.com/client/v4/zones/726f6033454c792cbe0ec3de8524e462/purge_cache" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  --data '{
    "files": [
      "https://offmetabg.com/uploads/settings/logo_url_1771110380.png",
      "https://offmetabg.com/assets/css/home.css"
    ]
  }'
```

#### Purge by Cache-Tags
```bash
curl -X POST "https://api.cloudflare.com/client/v4/zones/726f6033454c792cbe0ec3de8524e462/purge_cache" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  --data '{"tags":["product","homepage"]}'
```

#### Purge by Host
```bash
curl -X POST "https://api.cloudflare.com/client/v4/zones/726f6033454c792cbe0ec3de8524e462/purge_cache" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  --data '{"hosts":["offmetabg.com","www.offmetabg.com"]}'
```

---

### 2. Get Cache Level Setting
**Endpoint:** `GET /zones/726f6033454c792cbe0ec3de8524e462/settings/cache_level`

```bash
curl -X GET "https://api.cloudflare.com/client/v4/zones/726f6033454c792cbe0ec3de8524e462/settings/cache_level" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json"
```

**Response:**
```json
{
  "result": {
    "id": "cache_level",
    "value": "aggressive",
    "editable": true,
    "modified_on": "2026-02-15T14:00:00.000000Z"
  },
  "success": true,
  "errors": [],
  "messages": []
}
```

**Valid Values:**
- `aggressive` - Cache all static content
- `basic` - Cache most static content (recommended)
- `simplified` - Ignore query strings

---

### 3. Get Browser Cache TTL Setting
**Endpoint:** `GET /zones/726f6033454c792cbe0ec3de8524e462/settings/browser_cache_ttl`

```bash
curl -X GET "https://api.cloudflare.com/client/v4/zones/726f6033454c792cbe0ec3de8524e462/settings/browser_cache_ttl" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json"
```

**Response:**
```json
{
  "result": {
    "id": "browser_cache_ttl",
    "value": 14400,
    "editable": true,
    "modified_on": "2026-02-15T14:00:00.000000Z"
  },
  "success": true,
  "errors": [],
  "messages": []
}
```

---

### 4. Change Browser Cache TTL Setting
**Endpoint:** `PATCH /zones/726f6033454c792cbe0ec3de8524e462/settings/browser_cache_ttl`

```bash
curl -X PATCH "https://api.cloudflare.com/client/v4/zones/726f6033454c792cbe0ec3de8524e462/settings/browser_cache_ttl" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  --data '{"value":31536000}'
```

**Valid Values (in seconds):**
- `0` - Respect Existing Headers
- `120` - 2 minutes
- `300` - 5 minutes
- `1200` - 20 minutes
- `1800` - 30 minutes
- `3600` - 1 hour
- `7200` - 2 hours
- `10800` - 3 hours
- `14400` - 4 hours (default)
- `18000` - 5 hours
- `28800` - 8 hours
- `43200` - 12 hours
- `57600` - 16 hours
- `72000` - 20 hours
- `86400` - 1 day
- `172800` - 2 days
- `259200` - 3 days
- `345600` - 4 days
- `432000` - 5 days
- `691200` - 8 days
- `1382400` - 16 days
- `2073600` - 24 days
- `2678400` - 1 month
- `5356800` - 2 months
- `16070400` - 6 months
- **`31536000` - 1 year (recommended for static assets)**

---

## Common Use Cases

### Purge Cache After Deployment
```bash
# After git pull and deployment
curl -X POST "https://api.cloudflare.com/client/v4/zones/726f6033454c792cbe0ec3de8524e462/purge_cache" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'
```

### Purge Specific Assets After Update
```bash
# After logo optimization or CSS changes
curl -X POST "https://api.cloudflare.com/client/v4/zones/726f6033454c792cbe0ec3de8524e462/purge_cache" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  --data '{
    "files": [
      "https://offmetabg.com/uploads/settings/logo_url_1771110380_small.png",
      "https://offmetabg.com/assets/css/home.css",
      "https://offmetabg.com/assets/css/themes.css"
    ]
  }'
```

---

## PowerShell Automation Script

```powershell
# Load Cloudflare config
. .\cloudflare-config.local.ps1

# Function to purge everything
function Purge-CloudflareCache {
    $headers = @{
        "Authorization" = "Bearer $CLOUDFLARE_API_TOKEN"
        "Content-Type" = "application/json"
    }
    
    $body = @{
        purge_everything = $true
    } | ConvertTo-Json
    
    $response = Invoke-RestMethod -Uri "$CLOUDFLARE_ZONE_ENDPOINT/purge_cache" `
                                  -Method Post `
                                  -Headers $headers `
                                  -Body $body
    
    if ($response.success) {
        Write-Host "✓ Cloudflare cache purged successfully" -ForegroundColor Green
    } else {
        Write-Host "✗ Failed to purge cache: $($response.errors)" -ForegroundColor Red
    }
}

# Usage
Purge-CloudflareCache
```

---

## Notes

1. **Rate Limits:** Purge cache operations are rate limited
   - Purge Everything: 1 request per 12 minutes
   - Purge by Files: 30 requests per minute
   - Purge by Tags: 30 requests per minute

2. **Cache Busting Alternative:** Instead of purging cache, consider:
   - Adding version query parameters: `file.css?v=123456`
   - Using file modification time: `file.css?v=<?php echo filemtime('file.css'); ?>`
   - Renaming files after updates

3. **Best Practices:**
   - Use granular purges (by file/tag) instead of purge_everything when possible
   - Set appropriate browser cache TTL for different asset types
   - Combine with nginx cache headers for optimal performance

---

## Page Rules API Endpoints

### 1. List All Page Rules
**Endpoint:** `GET /zones/726f6033454c792cbe0ec3de8524e462/pagerules`

```bash
curl -X GET "https://api.cloudflare.com/client/v4/zones/726f6033454c792cbe0ec3de8524e462/pagerules" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json"
```

**Response:**
```json
{
  "result": [
    {
      "id": "page_rule_id_123",
      "targets": [
        {
          "target": "url",
          "constraint": {
            "operator": "matches",
            "value": "offmetabg.com/admin/*"
          }
        }
      ],
      "actions": [
        {
          "id": "cache_level",
          "value": "bypass"
        }
      ],
      "priority": 1,
      "status": "active",
      "created_on": "2026-01-01T00:00:00.000000Z",
      "modified_on": "2026-02-15T14:00:00.000000Z"
    }
  ],
  "success": true,
  "errors": [],
  "messages": []
}
```

---

### 2. Get Page Rule Details
**Endpoint:** `GET /zones/726f6033454c792cbe0ec3de8524e462/pagerules/:identifier`

```bash
curl -X GET "https://api.cloudflare.com/client/v4/zones/726f6033454c792cbe0ec3de8524e462/pagerules/PAGE_RULE_ID" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json"
```

---

### 3. Create a Page Rule
**Endpoint:** `POST /zones/726f6033454c792cbe0ec3de8524e462/pagerules`

```bash
curl -X POST "https://api.cloudflare.com/client/v4/zones/726f6033454c792cbe0ec3de8524e462/pagerules" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  --data '{
    "targets": [
      {
        "target": "url",
        "constraint": {
          "operator": "matches",
          "value": "offmetabg.com/assets/*"
        }
      }
    ],
    "actions": [
      {
        "id": "cache_level",
        "value": "cache_everything"
      },
      {
        "id": "edge_cache_ttl",
        "value": 31536000
      }
    ],
    "priority": 1,
    "status": "active"
  }'
```

**Common Page Rule Actions:**
- `cache_level`: `bypass`, `basic`, `simplified`, `aggressive`, `cache_everything`
- `edge_cache_ttl`: Edge cache TTL in seconds
- `browser_cache_ttl`: Browser cache TTL in seconds
- `security_level`: `off`, `essentially_off`, `low`, `medium`, `high`, `under_attack`
- `ssl`: `off`, `flexible`, `full`, `strict`
- `always_use_https`: `true`, `false`
- `automatic_https_rewrites`: `on`, `off`
- `minify`: `{"html": "on", "css": "on", "js": "on"}`
- `rocket_loader`: `on`, `off`
- `email_obfuscation`: `on`, `off`

---

### 4. Update a Page Rule (Full Replace)
**Endpoint:** `PUT /zones/726f6033454c792cbe0ec3de8524e462/pagerules/:identifier`

```bash
curl -X PUT "https://api.cloudflare.com/client/v4/zones/726f6033454c792cbe0ec3de8524e462/pagerules/PAGE_RULE_ID" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  --data '{
    "targets": [
      {
        "target": "url",
        "constraint": {
          "operator": "matches",
          "value": "offmetabg.com/uploads/*"
        }
      }
    ],
    "actions": [
      {
        "id": "cache_level",
        "value": "cache_everything"
      }
    ],
    "priority": 2,
    "status": "active"
  }'
```

---

### 5. Edit a Page Rule (Partial Update)
**Endpoint:** `PATCH /zones/726f6033454c792cbe0ec3de8524e462/pagerules/:identifier`

```bash
# Update only priority
curl -X PATCH "https://api.cloudflare.com/client/v4/zones/726f6033454c792cbe0ec3de8524e462/pagerules/PAGE_RULE_ID" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  --data '{"priority": 3}'
```

```bash
# Enable/disable page rule
curl -X PATCH "https://api.cloudflare.com/client/v4/zones/726f6033454c792cbe0ec3de8524e462/pagerules/PAGE_RULE_ID" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  --data '{"status": "disabled"}'
```

---

### 6. Delete a Page Rule
**Endpoint:** `DELETE /zones/726f6033454c792cbe0ec3de8524e462/pagerules/:identifier`

```bash
curl -X DELETE "https://api.cloudflare.com/client/v4/zones/726f6033454c792cbe0ec3de8524e462/pagerules/PAGE_RULE_ID" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json"
```

---

## Common Page Rule Examples

### Cache Everything for Static Assets
```json
{
  "targets": [{
    "target": "url",
    "constraint": {
      "operator": "matches",
      "value": "offmetabg.com/assets/*"
    }
  }],
  "actions": [
    {"id": "cache_level", "value": "cache_everything"},
    {"id": "edge_cache_ttl", "value": 31536000},
    {"id": "browser_cache_ttl", "value": 31536000}
  ],
  "priority": 1,
  "status": "active"
}
```

### Bypass Cache for Admin Area
```json
{
  "targets": [{
    "target": "url",
    "constraint": {
      "operator": "matches",
      "value": "offmetabg.com/admin/*"
    }
  }],
  "actions": [
    {"id": "cache_level", "value": "bypass"}
  ],
  "priority": 2,
  "status": "active"
}
```

### Force HTTPS for Entire Site
```json
{
  "targets": [{
    "target": "url",
    "constraint": {
      "operator": "matches",
      "value": "offmetabg.com/*"
    }
  }],
  "actions": [
    {"id": "always_use_https", "value": true}
  ],
  "priority": 3,
  "status": "active"
}
```

### Optimize Performance for Product Images
```json
{
  "targets": [{
    "target": "url",
    "constraint": {
      "operator": "matches",
      "value": "offmetabg.com/uploads/products/*"
    }
  }],
  "actions": [
    {"id": "cache_level", "value": "cache_everything"},
    {"id": "edge_cache_ttl", "value": 2678400},
    {"id": "browser_cache_ttl", "value": 2678400},
    {"id": "polish", "value": "lossless"}
  ],
  "priority": 4,
  "status": "active"
}
```

---

## PowerShell Helper Functions

```powershell
# Load Cloudflare config
. .\cloudflare-config.local.ps1

# List all page rules
function Get-CloudflarePageRules {
    $headers = @{
        "Authorization" = "Bearer $CLOUDFLARE_API_TOKEN"
        "Content-Type" = "application/json"
    }
    
    $response = Invoke-RestMethod -Uri "$CLOUDFLARE_ZONE_ENDPOINT/pagerules" `
                                  -Method Get `
                                  -Headers $headers
    
    return $response.result
}

# Create page rule for static assets
function New-CloudflareStaticCacheRule {
    param(
        [string]$UrlPattern = "offmetabg.com/assets/*",
        [int]$Priority = 1
    )
    
    $headers = @{
        "Authorization" = "Bearer $CLOUDFLARE_API_TOKEN"
        "Content-Type" = "application/json"
    }
    
    $body = @{
        targets = @(
            @{
                target = "url"
                constraint = @{
                    operator = "matches"
                    value = $UrlPattern
                }
            }
        )
        actions = @(
            @{ id = "cache_level"; value = "cache_everything" },
            @{ id = "edge_cache_ttl"; value = 31536000 },
            @{ id = "browser_cache_ttl"; value = 31536000 }
        )
        priority = $Priority
        status = "active"
    } | ConvertTo-Json -Depth 10
    
    $response = Invoke-RestMethod -Uri "$CLOUDFLARE_ZONE_ENDPOINT/pagerules" `
                                  -Method Post `
                                  -Headers $headers `
                                  -Body $body
    
    if ($response.success) {
        Write-Host "✓ Page rule created successfully" -ForegroundColor Green
        return $response.result
    } else {
        Write-Host "✗ Failed to create page rule: $($response.errors)" -ForegroundColor Red
    }
}

# Delete page rule
function Remove-CloudflarePageRule {
    param([string]$RuleId)
    
    $headers = @{
        "Authorization" = "Bearer $CLOUDFLARE_API_TOKEN"
        "Content-Type" = "application/json"
    }
    
    $response = Invoke-RestMethod -Uri "$CLOUDFLARE_ZONE_ENDPOINT/pagerules/$RuleId" `
                                  -Method Delete `
                                  -Headers $headers
    
    if ($response.success) {
        Write-Host "✓ Page rule deleted successfully" -ForegroundColor Green
    } else {
        Write-Host "✗ Failed to delete page rule: $($response.errors)" -ForegroundColor Red
    }
}

# Usage examples
# Get-CloudflarePageRules
# New-CloudflareStaticCacheRule -UrlPattern "offmetabg.com/uploads/*" -Priority 2
# Remove-CloudflarePageRule -RuleId "page_rule_id_123"
```

---

## WAF (Web Application Firewall) API Endpoints

### Update Zone WAF Ruleset
**Endpoint:** `PUT /zones/726f6033454c792cbe0ec3de8524e462/rulesets/phases/http_request_firewall_custom/entrypoint`

**Required Permissions:** API Token with `Zone > Zone WAF` or broader permissions

This endpoint allows you to configure custom firewall rules for your zone. You can block, challenge, or allow traffic based on various conditions.

```bash
curl -X PUT "https://api.cloudflare.com/client/v4/zones/726f6033454c792cbe0ec3de8524e462/rulesets/phases/http_request_firewall_custom/entrypoint" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "rules": [
      {
        "description": "Block bad bots",
        "expression": "(cf.client.bot) and not (cf.verified_bot_category in {\"Search Engine Crawler\" \"Preview Bot\"})",
        "action": "block"
      }
    ]
  }'
```

---

## Common WAF Rule Examples

### 1. Block Specific Countries
```json
{
  "rules": [
    {
      "description": "Block traffic from specific countries",
      "expression": "(ip.geoip.country in {\"CN\" \"RU\" \"KP\"})",
      "action": "block"
    }
  ]
}
```

### 2. Challenge Suspicious Traffic
```json
{
  "rules": [
    {
      "description": "Challenge suspicious user agents",
      "expression": "(http.user_agent contains \"bot\" or http.user_agent contains \"crawler\") and not cf.verified_bot_category",
      "action": "managed_challenge"
    }
  ]
}
```

### 3. Rate Limiting
```json
{
  "rules": [
    {
      "description": "Rate limit login attempts",
      "expression": "(http.request.uri.path eq \"/auth.php\" and http.request.method eq \"POST\")",
      "action": "challenge",
      "ratelimit": {
        "characteristics": ["ip.src"],
        "period": 60,
        "requests_per_period": 5,
        "mitigation_timeout": 600
      }
    }
  ]
}
```

### 4. Block Admin Access from Outside Bulgaria
```json
{
  "rules": [
    {
      "description": "Restrict admin area to Bulgaria only",
      "expression": "(http.request.uri.path matches \"^/admin/\" and ip.geoip.country ne \"BG\")",
      "action": "block"
    }
  ]
}
```

### 5. Allow Only Verified Bots
```json
{
  "rules": [
    {
      "description": "Block unverified bots",
      "expression": "(cf.client.bot and not cf.verified_bot_category)",
      "action": "block"
    }
  ]
}
```

### 6. Protect Against SQL Injection
```json
{
  "rules": [
    {
      "description": "Block SQL injection attempts",
      "expression": "(http.request.uri.query contains \"union select\" or http.request.uri.query contains \"' or '1'='1\")",
      "action": "block"
    }
  ]
}
```

### 7. Protect API Endpoints
```json
{
  "rules": [
    {
      "description": "Challenge API requests without valid token",
      "expression": "(http.request.uri.path matches \"^/api/\" and not http.request.headers[\"authorization\"][0] contains \"Bearer\")",
      "action": "managed_challenge"
    }
  ]
}
```

---

## WAF Expression Fields

### Common Fields:
- `ip.src` - Source IP address
- `ip.geoip.country` - Two-letter country code (ISO 3166-1 Alpha 2)
- `ip.geoip.continent` - Continent code
- `http.host` - HTTP Host header
- `http.request.method` - HTTP method (GET, POST, etc.)
- `http.request.uri` - Full URI
- `http.request.uri.path` - URI path only
- `http.request.uri.query` - Query string
- `http.user_agent` - User-Agent header
- `http.referer` - Referer header
- `cf.client.bot` - Is request from a bot
- `cf.verified_bot_category` - Verified bot category
- `cf.threat_score` - Threat score (0-100)

### Operators:
- `eq` - Equals
- `ne` - Not equals
- `contains` - Contains substring
- `matches` - Regex match
- `in` - In list
- `not` - Logical NOT
- `and` - Logical AND
- `or` - Logical OR

### Actions:
- `block` - Block the request
- `challenge` - Present CAPTCHA challenge
- `managed_challenge` - Smart challenge (invisible for humans)
- `js_challenge` - JavaScript challenge
- `allow` - Allow the request
- `log` - Log only (no action)
- `skip` - Skip other rules

---

## PowerShell WAF Management

```powershell
# Load Cloudflare config
. .\cloudflare-config.local.ps1

# Update WAF rules
function Update-CloudflareWAFRules {
    param([array]$Rules)
    
    $headers = @{
        "Authorization" = "Bearer $CLOUDFLARE_API_TOKEN"
        "Content-Type" = "application/json"
    }
    
    $body = @{ rules = $Rules } | ConvertTo-Json -Depth 10
    
    $response = Invoke-RestMethod `
        -Uri "$CLOUDFLARE_ZONE_ENDPOINT/rulesets/phases/http_request_firewall_custom/entrypoint" `
        -Method Put `
        -Headers $headers `
        -Body $body
    
    if ($response.success) {
        Write-Host "✓ WAF rules updated successfully" -ForegroundColor Green
        return $response.result
    } else {
        Write-Host "✗ Failed to update WAF rules: $($response.errors)" -ForegroundColor Red
    }
}

# Example: Block suspicious traffic and protect admin area
$wafRules = @(
    @{
        description = "Block unverified bots"
        expression = "(cf.client.bot and not cf.verified_bot_category)"
        action = "block"
    },
    @{
        description = "Restrict admin to Bulgaria"
        expression = "(http.request.uri.path matches `"^/admin/`" and ip.geoip.country ne `"BG`")"
        action = "block"
    },
    @{
        description = "Rate limit login attempts"
        expression = "(http.request.uri.path eq `"/auth.php`" and http.request.method eq `"POST`")"
        action = "managed_challenge"
    }
)

# Usage
# Update-CloudflareWAFRules -Rules $wafRules
```

---

## Security Best Practices

1. **Start with Log Mode:**
   - Test rules with `"action": "log"` first
   - Monitor false positives
   - Switch to blocking after testing

2. **Layer Security:**
   - Combine multiple rules for defense in depth
   - Use rate limiting for login pages
   - Challenge suspicious traffic before blocking

3. **Geographic Restrictions:**
   - Consider your actual user base
   - Don't block entire countries unless necessary
   - Use challenges instead of blocks when possible

4. **Bot Management:**
   - Allow verified search engine bots
   - Block unverified bots claiming to be search engines
   - Use managed challenges for gray area traffic

5. **Monitor and Adjust:**
   - Regularly review blocked traffic
   - Adjust rules based on attack patterns
   - Keep expressions up to date

---

## Related Documentation
- [Cloudflare API Docs](https://developers.cloudflare.com/api/)
- [Cache Configuration Guide](https://developers.cloudflare.com/cache/)
- [Performance Optimization](https://developers.cloudflare.com/fundamentals/speed/)
- [Page Rules Documentation](https://developers.cloudflare.com/support/page-rules/)
- [WAF Rules Reference](https://developers.cloudflare.com/waf/)
- [Firewall Rules Language](https://developers.cloudflare.com/ruleset-engine/rules-language/)
