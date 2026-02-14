# Quick VPS Update Script
# Pulls latest code from GitHub to VPS

Write-Host "Deploying to VPS..." -ForegroundColor Cyan

# Try with SSH key
$result = ssh -i "C:\Users\Warton\.ssh\id_ed25519_hetzner" -o "StrictHostKeyChecking=no" root@46.225.71.94 "cd /var/www/offmetabg && git pull origin master && echo 'SUCCESS'"

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Deployment successful!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Test your diagnostic page at:" -ForegroundColor Yellow
    Write-Host "https://offmetabg.com/admin/diagnostic.php" -ForegroundColor Cyan
} else {
    Write-Host "❌ Deployment failed. You may need to pull manually on the server:" -ForegroundColor Red
    Write-Host ""
    Write-Host "  ssh root@46.225.71.94" -ForegroundColor Gray
    Write-Host "  cd /var/www/offmetabg" -ForegroundColor Gray
    Write-Host "  git pull origin master" -ForegroundColor Gray
}
