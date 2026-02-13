# Git-based Deployment Script
# Push to GitHub → Pull on VPS
# Usage: .\git-deploy.ps1 [-Message "commit message"] [-NoPush] [-NoPull]

param(
    [string]$Message = "Deploy update",
    [switch]$NoPush,
    [switch]$NoPull,
    [switch]$Force
)

$ErrorActionPreference = "Stop"

Write-Host ""
Write-Host "╔══════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║      Git Deploy - offmetabg.com                 ║" -ForegroundColor Cyan
Write-Host "╚══════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Load local configuration
$configFile = Join-Path $PSScriptRoot "deploy-config.local.ps1"
if (-not (Test-Path $configFile)) {
    Write-Host "❌ Error: deploy-config.local.ps1 not found!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please create deploy-config.local.ps1 with your VPS settings." -ForegroundColor Yellow
    Write-Host "You can copy from deploy-config.local.ps1.example" -ForegroundColor Yellow
    Write-Host ""
    exit 1
}

. $configFile

# Validate configuration
if ($VPS_IP -eq "YOUR_VPS_IP_HERE") {
    Write-Host "❌ Error: Please configure deploy-config.local.ps1 first!" -ForegroundColor Red
    Write-Host "   Set your VPS IP address" -ForegroundColor Yellow
    exit 1
}

# Step 1: Check Git status
Write-Host "📋 Checking Git status..." -ForegroundColor Yellow
$gitStatus = git status --porcelain

if ($gitStatus -and -not $Force) {
    Write-Host ""
    Write-Host "Uncommitted changes detected:" -ForegroundColor Yellow
    git status --short
    Write-Host ""
    
    $response = Read-Host "Commit and push these changes? (y/n)"
    if ($response -ne 'y') {
        Write-Host "Deployment cancelled." -ForegroundColor Yellow
        exit 0
    }
    
    Write-Host ""
    Write-Host "📝 Committing changes..." -ForegroundColor Yellow
    git add .
    git commit -m $Message
}

# Step 2: Push to GitHub
if (-not $NoPush) {
    Write-Host ""
    Write-Host "📤 Pushing to GitHub ($GIT_BRANCH)..." -ForegroundColor Yellow
    
    try {
        git push origin $GIT_BRANCH
        Write-Host "✅ Pushed to GitHub" -ForegroundColor Green
    } catch {
        Write-Host "❌ Failed to push to GitHub!" -ForegroundColor Red
        Write-Host $_.Exception.Message -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "⏭️  Skipping GitHub push" -ForegroundColor Gray
}

# Step 3: Pull on VPS
if (-not $NoPull) {
    Write-Host ""
    Write-Host "📥 Deploying to VPS ($VPS_IP)..." -ForegroundColor Yellow
    
    # Build SSH command
    $sshCmd = if ($VPS_SSH_KEY) { 
        "ssh -i `"$VPS_SSH_KEY`" ${VPS_USER}@${VPS_IP}" 
    } else { 
        "ssh ${VPS_USER}@${VPS_IP}" 
    }
    
    # Deploy commands
    $deployScript = @"
#!/bin/bash
set -e

echo "🔄 Pulling from GitHub..."
cd $VPS_WEB_ROOT

# Stash any local changes
git stash

# Pull latest code
git pull origin $GIT_BRANCH

echo "✅ Code updated"

# Set permissions
echo "🔒 Setting permissions..."
chown -R www-data:www-data $VPS_WEB_ROOT
chmod -R 755 $VPS_WEB_ROOT
chmod -R 775 $VPS_WEB_ROOT/uploads
chmod -R 775 $VPS_WEB_ROOT/cache
chmod -R 775 $VPS_WEB_ROOT/logs

# Restart services
echo "🔄 Restarting services..."
systemctl restart php$PHP_VERSION-fpm
systemctl reload nginx

echo "✅ Deployment complete!"
"@

    try {
        # Execute deploy script on VPS
        $deployScript | & $sshCmd "bash -s"
        
        Write-Host ""
        Write-Host "╔══════════════════════════════════════════════════╗" -ForegroundColor Green
        Write-Host "║          Deployment Successful! ✅               ║" -ForegroundColor Green
        Write-Host "╚══════════════════════════════════════════════════╝" -ForegroundColor Green
        Write-Host ""
        Write-Host "🌐 Your site: https://$DOMAIN" -ForegroundColor Cyan
        Write-Host ""
        
    } catch {
        Write-Host ""
        Write-Host "❌ Deployment to VPS failed!" -ForegroundColor Red
        Write-Host $_.Exception.Message -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "⏭️  Skipping VPS pull" -ForegroundColor Gray
}

Write-Host ""
Write-Host "✅ Done!" -ForegroundColor Green
Write-Host ""
