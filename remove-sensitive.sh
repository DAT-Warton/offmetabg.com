#!/bin/bash
# Remove sensitive files from Git history

export FILTER_BRANCH_SQUELCH_WARNING=1

echo "Removing sensitive files from Git history..."

git filter-branch --force --index-filter \
  'git rm --cached --ignore-unmatch \
    purge-cloudflare-cache-backup.ps1 \
    purge-cloudflare-cache-temp.ps1 \
    deploy-mobile-fix.ps1 \
    git-deploy.ps1 \
    quick-deploy.ps1 \
    add-email-dns-records.local.php' \
  --prune-empty --tag-name-filter cat -- --all

echo ""
echo "Cleaning up repository..."
rm -rf .git/refs/original/
git reflog expire --expire=now --all
git gc --prune=now --aggressive

echo ""
echo "✓ Sensitive files removed from history!"
echo ""
echo "⚠️  Next steps:"
echo "1. Review changes with: git log --oneline"
echo "2. Force push with: git push origin --force --all"
echo "3. ВАЖНО: Всички които са clone-вали repo-то трябва да направят fresh clone!"
