# PWA Symlink Setup Script
# Creates symlinks for Shillings PWA assets
# Run with Administrator privileges

Write-Host "Creating Shillings PWA Symlinks..." -ForegroundColor Cyan

$shillingsPath = "c:\Users\maxmm\Herd\shillings"

# Check if running with admin privileges
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")
if (-not $isAdmin) {
    Write-Host "ERROR: This script requires Administrator privileges" -ForegroundColor Red
    exit 1
}

# Create pwa-symlinks directory
if (-not (Test-Path "$shillingsPath\pwa-symlinks")) {
    New-Item -ItemType Directory "$shillingsPath\pwa-symlinks" -Force -ErrorAction Continue | Out-Null
}

Write-Host "✓ pwa-symlinks directory exists" -ForegroundColor Green

# Create symlinks
Write-Host "Creating symlinks..." -ForegroundColor Yellow

New-Item -ItemType SymbolicLink -Path "$shillingsPath\pwa-symlinks\manifest.json" -Target "$shillingsPath\public\manifest.json" -Force -ErrorAction Continue | Out-Null
Write-Host "  ✓ manifest.json" -ForegroundColor Green

New-Item -ItemType SymbolicLink -Path "$shillingsPath\pwa-symlinks\sw.js" -Target "$shillingsPath\public\sw.js" -Force -ErrorAction Continue | Out-Null
Write-Host "  ✓ sw.js" -ForegroundColor Green

New-Item -ItemType SymbolicLink -Path "$shillingsPath\pwa-symlinks\offline.html" -Target "$shillingsPath\public\offline.html" -Force -ErrorAction Continue | Out-Null
Write-Host "  ✓ offline.html" -ForegroundColor Green

New-Item -ItemType SymbolicLink -Path "$shillingsPath\pwa-symlinks\offline-modules" -Target "$shillingsPath\resources\js\offline" -Force -ErrorAction Continue | Out-Null
Write-Host "  ✓ offline-modules" -ForegroundColor Green

New-Item -ItemType SymbolicLink -Path "$shillingsPath\pwa-symlinks\pwa.js" -Target "$shillingsPath\resources\js\pwa.js" -Force -ErrorAction Continue | Out-Null
Write-Host "  ✓ pwa.js" -ForegroundColor Green

Write-Host "`n✓ All PWA symlinks created successfully!" -ForegroundColor Green
Write-Host "`nNext steps:"
Write-Host "  1. Navigate to: $shillingsPath\pwa-symlinks"
Write-Host "  2. Review PWA_SYMLINKS.md for full documentation"
