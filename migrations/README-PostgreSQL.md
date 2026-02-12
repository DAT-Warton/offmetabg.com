# PostgreSQL Setup Guide for Render

## 🚀 Quick Setup Steps

### 1. Create PostgreSQL Database on Render

1. Login to [Render Dashboard](https://dashboard.render.com/)
2. Click **"New +"** → **"PostgreSQL"**
3. Configure:
   - **Name**: `offmetabg-db` (or any name)
   - **Database**: `offmetabg`
   - **User**: `offmetabg_user`
   - **Region**: Same as your web service
   - **Instance Type**: **Free** (512MB RAM, 1GB storage)
4. Click **"Create Database"**
5. Wait 2-3 minutes for provisioning

### 2. Get DATABASE_URL

After database is created:

1. Go to your PostgreSQL instance in Render
2. Find **"Internal Database URL"** (starts with `postgres://`)
3. Copy the entire URL - it looks like:
   ```
   postgres://username:password@hostname:5432/database
   ```

### 3. Connect Database to Web Service

1. Go to your **Web Service** (`offmetabg` app) in Render
2. Click **"Environment"** tab
3. Click **"Add Environment Variable"**
4. Add:
   - **Key**: `DATABASE_URL`
   - **Value**: Paste the Internal Database URL from step 2
5. Click **"Save Changes"**
6. Render will automatically redeploy your app

### 4. Run Migration Script

**Option A: From Render Shell (Recommended)**

1. In your web service, go to **"Shell"** tab
2. Run:
   ```bash
   php migrations/migrate-json-to-pgsql.php
   ```

**Option B: From Local Terminal (if you have DATABASE_URL locally)**

1. Set DATABASE_URL in PowerShell:
   ```powershell
   $env:DATABASE_URL = "postgres://username:password@hostname:5432/database"
   php migrations/migrate-json-to-pgsql.php
   ```

### 5. Verify Database

1. In Render PostgreSQL dashboard, click **"Connect"**
2. Choose **"psql"** and run the command in your terminal:
   ```bash
   psql postgres://username:password@hostname:5432/database
   ```
3. Check tables:
   ```sql
   \dt              -- List all tables
   SELECT COUNT(*) FROM products;
   SELECT COUNT(*) FROM categories;
   \q               -- Exit
   ```

## 📊 What Gets Migrated

The migration script copies all data from JSON files to PostgreSQL:

- ✅ Products
- ✅ Categories  
- ✅ Customers
- ✅ Admins
- ✅ Orders
- ✅ Inquiries
- ✅ Discounts
- ✅ Promotions
- ✅ Pages
- ✅ Posts (Blog)
- ✅ Options (Settings)

**Note**: JSON files are kept as backup and not deleted.

## 🔄 Automatic Behavior

Once `DATABASE_URL` is set in Render:

- ✅ App automatically uses PostgreSQL
- ✅ All reads/writes go to database
- ✅ JSON files are ignored (but kept as backup)
- ✅ No code changes needed!

## 🆓 Free Tier Limits

Render PostgreSQL Free Plan:

- **Storage**: 1GB
- **RAM**: 512MB
- **Expires**: After 90 days (warning at 85 days)
- **Connections**: 97 max concurrent
- **Backup**: Not included (manual exports only)

**Note**: Database is deleted after 90 days on free plan. Upgrade to paid plan before expiration to keep data.

## 📤 Backup Your Database

### Manual Backup (Free Plan)

```bash
# Dump entire database to SQL file
pg_dump DATABASE_URL > backup.sql

# Restore from backup
psql DATABASE_URL < backup.sql
```

### Automatic Backups (Paid Plan)

Upgrade to Starter plan ($7/month) for:
- Daily automatic backups
- Point-in-time recovery
- Longer retention (90+ days database lifespan)

## 🔧 Troubleshooting

### Migration Script Errors

**Problem**: `DATABASE_URL environment variable not set`

**Solution**: Make sure DATABASE_URL is set in Render environment or locally

---

**Problem**: `Connection failed: could not translate host name`

**Solution**: Check DATABASE_URL is correct and database is running in Render

---

**Problem**: `Schema error (might already exist)`

**Solution**: This is normal - tables already created. Migration continues.

---

### App Not Using PostgreSQL

**Check #1**: DATABASE_URL is set in Render Environment tab

**Check #2**: Restart web service after adding DATABASE_URL

**Check #3**: Check logs for database connection errors:
```
Settings → Logs → Filter "Database"
```

---

### Data Not Showing

**Check #1**: Verify data was migrated:
```bash
psql DATABASE_URL
SELECT COUNT(*) FROM products;
```

**Check #2**: Make sure you ran migration script AFTER setting DATABASE_URL

---

## 🔐 Security Best Practices

1. **Use Internal Database URL** in Render (not External URL)
   - Faster connection (same network)
   - More secure (not exposed to internet)

2. **Never commit DATABASE_URL** to Git
   - Already in `.gitignore`
   - Only store in Render environment

3. **Rotate credentials** if exposed
   - Delete and recreate database
   - Or rotate password in database settings

## 📈 Upgrading to Paid Plan

When you outgrow free tier:

1. Go to PostgreSQL instance in Render
2. Click **"Upgrade"**
3. Choose **Starter** ($7/mo) or **Standard** ($20/mo)
4. Benefits:
   - No 90-day expiration
   - Automatic daily backups
   - More storage (10GB - 256GB)
   - Better performance

## 🔄 Migration Back to JSON (If Needed)

If you want to go back to JSON:

1. Export data from PostgreSQL (optional backup):
   ```bash
   pg_dump DATABASE_URL > backup.sql
   ```

2. Remove DATABASE_URL from Render environment

3. Redeploy app - will automatically use JSON files

Data in JSON files is still intact (never deleted).

## 💡 Tips

- **Test locally first**: Set DATABASE_URL locally and test migration
- **Monitor usage**: Check database metrics in Render dashboard
- **Plan ahead**: Upgrade before hitting free tier limits
- **Keep JSON backup**: Don't delete JSON files until 100% sure PostgreSQL works

## 🆘 Need Help?

- Render Docs: https://render.com/docs/databases
- PostgreSQL Docs: https://www.postgresql.org/docs/
- Check app logs in Render dashboard
- Review migration script output for errors
