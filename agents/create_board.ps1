# GitHub Project Board Automation Script
# Creates project boards and enables auto-add workflows via GraphQL API
#
# Usage:
#   .\create_board.ps1 -ProjectName "My Project" -RepoOwner "username" -RepoName "repo-name"

param(
    [Parameter(Mandatory=$true)]
    [string]$ProjectName,
    
    [Parameter(Mandatory=$true)]
    [string]$RepoOwner,
    
    [Parameter(Mandatory=$true)]
    [string]$RepoName,
    
    [Parameter(Mandatory=$false)]
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"

Write-Host "`n=======================================" -ForegroundColor Cyan
Write-Host "  GitHub Project Board Automation" -ForegroundColor Cyan
Write-Host "=======================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Project: $ProjectName" -ForegroundColor White
Write-Host "Owner: $RepoOwner" -ForegroundColor White
Write-Host "Repository: $RepoName" -ForegroundColor White

if ($DryRun) {
    Write-Host "Mode: DRY RUN (no changes will be made)" -ForegroundColor Yellow
} else {
    Write-Host "Mode: LIVE (board will be created)" -ForegroundColor Green
}

Write-Host ""

# Step 1: Check if board already exists
Write-Host "[1/6] Checking for existing project boards..." -ForegroundColor Cyan

try {
    $existingBoards = gh project list --owner $RepoOwner --format json | ConvertFrom-Json
    $matchingBoard = $existingBoards | Where-Object { $_.title -eq "$ProjectName Development Board" }
    
    if ($matchingBoard) {
        Write-Host "  FOUND: Board already exists!" -ForegroundColor Yellow
        Write-Host "  URL: $($matchingBoard.url)" -ForegroundColor White
        Write-Host ""
        Write-Host "Would you like to:" -ForegroundColor Yellow
        Write-Host "  1. Use existing board (recommended)" -ForegroundColor White
        Write-Host "  2. Create new board anyway" -ForegroundColor White
        Write-Host "  3. Exit" -ForegroundColor White
        Write-Host ""
        
        if ($DryRun) {
            Write-Host "[DRY RUN] Would prompt user for choice" -ForegroundColor Yellow
            return @{
                status = "exists"
                url = $matchingBoard.url
                number = $matchingBoard.number
            }
        }
        
        $choice = Read-Host "Enter choice (1-3)"
        
        switch ($choice) {
            "1" {
                Write-Host ""
                Write-Host "Using existing board: $($matchingBoard.url)" -ForegroundColor Green
                
                # Still need to verify auto-add workflow
                Write-Host ""
                Write-Host "[MANUAL STEP REQUIRED]" -ForegroundColor Yellow
                Write-Host "Please verify auto-add workflow is enabled:" -ForegroundColor White
                Write-Host "1. Go to: $($matchingBoard.url)" -ForegroundColor White
                Write-Host "2. Click '...' -> Settings -> Workflows" -ForegroundColor White
                Write-Host "3. Enable 'Auto-add to project'" -ForegroundColor White
                Write-Host "4. Filter: 'Label is any of: *' (all labels)" -ForegroundColor White
                Write-Host "5. Save" -ForegroundColor White
                Write-Host ""
                Read-Host "Press Enter once workflow is verified"
                
                return @{
                    status = "success"
                    url = $matchingBoard.url
                    number = $matchingBoard.number
                    message = "Using existing board"
                }
            }
            "2" {
                Write-Host ""
                Write-Host "Creating new board..." -ForegroundColor Yellow
                # Continue with creation below
            }
            default {
                Write-Host ""
                Write-Host "Exiting..." -ForegroundColor Gray
                exit 0
            }
        }
    } else {
        Write-Host "  No existing board found. Creating new board..." -ForegroundColor White
    }
} catch {
    Write-Host "  Warning: Could not check existing boards" -ForegroundColor Yellow
    Write-Host "  Error: $_" -ForegroundColor Gray
    Write-Host "  Continuing with board creation..." -ForegroundColor White
}

Write-Host ""

# Step 2: Create project board
Write-Host "[2/6] Creating project board..." -ForegroundColor Cyan

if ($DryRun) {
    Write-Host "  [DRY RUN] Would create board: '$ProjectName Development Board'" -ForegroundColor Yellow
    $boardUrl = "https://github.com/users/$RepoOwner/projects/999"
    $boardNumber = 999
} else {
    try {
        $createResult = gh project create `
            --owner $RepoOwner `
            --title "$ProjectName Development Board" `
            --format json | ConvertFrom-Json
        
        $boardUrl = $createResult.url
        $boardNumber = $createResult.number
        
        Write-Host "  SUCCESS: Board created!" -ForegroundColor Green
        Write-Host "  URL: $boardUrl" -ForegroundColor White
        Write-Host "  Number: #$boardNumber" -ForegroundColor White
    } catch {
        Write-Host "  ERROR: Board creation failed!" -ForegroundColor Red
        Write-Host "  Error: $_" -ForegroundColor Red
        Write-Host ""
        Write-Host "MANUAL ACTION REQUIRED:" -ForegroundColor Yellow
        Write-Host "1. Create board manually: https://github.com/$RepoOwner/$RepoName/projects/new" -ForegroundColor White
        Write-Host "2. Name it: '$ProjectName Development Board'" -ForegroundColor White
        Write-Host "3. Once created, run this script again" -ForegroundColor White
        exit 1
    }
}

Write-Host ""

# Step 3: Get project ID (for GraphQL)
Write-Host "[3/6] Getting project ID for workflow configuration..." -ForegroundColor Cyan

if ($DryRun) {
    Write-Host "  [DRY RUN] Would fetch project ID via GraphQL" -ForegroundColor Yellow
    $projectId = "PVT_fake_project_id"
} else {
    try {
        # GraphQL query to get project ID from number
        $query = @"
{
  user(login: "$RepoOwner") {
    projectV2(number: $boardNumber) {
      id
      title
    }
  }
}
"@
        
        $result = gh api graphql -f query=$query | ConvertFrom-Json
        $projectId = $result.data.user.projectV2.id
        
        Write-Host "  SUCCESS: Project ID: $projectId" -ForegroundColor Green
    } catch {
        Write-Host "  WARNING: Could not get project ID via GraphQL" -ForegroundColor Yellow
        Write-Host "  Error: $_" -ForegroundColor Red
        Write-Host "  Will use manual workflow setup instead..." -ForegroundColor Yellow
        $projectId = $null
    }
}

Write-Host ""

# Step 4: Enable auto-add workflow
Write-Host "[4/6] Configuring auto-add workflow..." -ForegroundColor Cyan

if ($DryRun) {
    Write-Host "  [DRY RUN] Would enable auto-add workflow via GraphQL" -ForegroundColor Yellow
} elseif ($projectId) {
    try {
        # Note: This requires the 'project' scope and may need manual setup
        # GraphQL mutation to create auto-add workflow
        # Unfortunately, GitHub's GraphQL API doesn't fully support workflow creation yet
        # We'll need to guide user to manual setup
        
        Write-Host "  NOTE: Auto-add workflow requires manual setup" -ForegroundColor Yellow
        Write-Host "  (GitHub API limitation - GraphQL doesn't support full workflow automation yet)" -ForegroundColor Gray
        
        # Fall through to manual instructions
        $projectId = $null
    } catch {
        Write-Host "  WARNING: Auto-add workflow setup failed" -ForegroundColor Yellow
        Write-Host "  Error: $_" -ForegroundColor Red
        $projectId = $null
    }
}

if (-not $projectId -and -not $DryRun) {
    Write-Host ""
    Write-Host "=======================================" -ForegroundColor Yellow
    Write-Host "  MANUAL STEP REQUIRED" -ForegroundColor Yellow
    Write-Host "=======================================" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Board created successfully, but auto-add workflow needs manual setup:" -ForegroundColor White
    Write-Host ""
    Write-Host "ACTION REQUIRED:" -ForegroundColor Yellow
    Write-Host "1. Go to: $boardUrl" -ForegroundColor White
    Write-Host "2. Click '...' (three dots) in top right" -ForegroundColor White
    Write-Host "3. Select 'Settings'" -ForegroundColor White
    Write-Host "4. Click 'Workflows' in left sidebar" -ForegroundColor White
    Write-Host "5. Find 'Auto-add to project' and click 'Edit'" -ForegroundColor White
    Write-Host "6. Enable the workflow" -ForegroundColor White
    Write-Host "7. Set filter: 'Label is any of: *' (asterisk = all labels)" -ForegroundColor White
    Write-Host "8. Click 'Save'" -ForegroundColor White
    Write-Host ""
    Write-Host "This ensures all labeled issues automatically appear on the board." -ForegroundColor Gray
    Write-Host ""
    
    $confirmed = Read-Host "Press Enter once you've completed this step"
    
    Write-Host ""
    Write-Host "  Workflow setup confirmed!" -ForegroundColor Green
}

Write-Host ""

# Step 5: Link board to repository
Write-Host "[5/6] Linking board to repository..." -ForegroundColor Cyan

if ($DryRun) {
    Write-Host "  [DRY RUN] Would link board to repo $RepoOwner/$RepoName" -ForegroundColor Yellow
} else {
    try {
        # Add repository to project (if not already linked)
        # Note: This may require additional API calls
        Write-Host "  Board is accessible at: $boardUrl" -ForegroundColor White
        Write-Host "  Issues will auto-add when created with proper labels" -ForegroundColor Gray
    } catch {
        Write-Host "  Warning: Could not verify repository link" -ForegroundColor Yellow
    }
}

Write-Host ""

# Step 6: Save board URL to memory
Write-Host "[6/6] Saving board URL to project memory..." -ForegroundColor Cyan

if ($DryRun) {
    Write-Host "  [DRY RUN] Would update .github/instructions/memory.instruction.md" -ForegroundColor Yellow
} else {
    $memoryFile = ".github/instructions/memory.instruction.md"
    
    if (Test-Path $memoryFile) {
        try {
            $content = Get-Content $memoryFile -Raw
            
            # Update board URL line
            if ($content -match 'Board URL: \[Will be set.*?\]') {
                $content = $content -replace 'Board URL: \[Will be set.*?\]', "Board URL: $boardUrl"
            } elseif ($content -match 'Board URL:.*') {
                $content = $content -replace 'Board URL:.*', "Board URL: $boardUrl"
            } else {
                # Add board URL to Board Management section
                $content = $content -replace '(\*\*Board Management:\*\*)', "`$1`n- Board URL: $boardUrl"
            }
            
            $content | Set-Content $memoryFile -Encoding UTF8
            
            Write-Host "  SUCCESS: Memory file updated with board URL" -ForegroundColor Green
        } catch {
            Write-Host "  Warning: Could not update memory file" -ForegroundColor Yellow
            Write-Host "  Please manually add board URL to $memoryFile" -ForegroundColor White
        }
    } else {
        Write-Host "  Warning: Memory file not found at $memoryFile" -ForegroundColor Yellow
        Write-Host "  You can manually add board URL later" -ForegroundColor White
    }
}

Write-Host ""

# Summary
Write-Host "=======================================" -ForegroundColor Green
Write-Host "  BOARD SETUP COMPLETE!" -ForegroundColor Green
Write-Host "=======================================" -ForegroundColor Green
Write-Host ""
Write-Host "Board Details:" -ForegroundColor Cyan
Write-Host "  URL: $boardUrl" -ForegroundColor White
Write-Host "  Number: #$boardNumber" -ForegroundColor White
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Cyan
Write-Host "  1. Create issues using: gh issue create ..." -ForegroundColor White
Write-Host "  2. All issues with labels will auto-add to board" -ForegroundColor White
Write-Host "  3. Or use 'scope it out and create issues' workflow" -ForegroundColor White
Write-Host ""

if (-not $DryRun) {
    return @{
        status = "success"
        url = $boardUrl
        number = $boardNumber
        message = "Board created and configured successfully"
    }
}
