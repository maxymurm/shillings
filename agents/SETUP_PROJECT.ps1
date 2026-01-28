<#
.SYNOPSIS
    Automated project setup script for new and existing projects.

.DESCRIPTION
    Executes all 23 steps from PROJECT_INITIALIZATION.md to set up a complete project:
    - Git & GitHub repository
    - Documentation structure
    - Memory system
    - GitHub issues, milestones, project board, labels
    - Phase 1 issues
    
    Supports both NEW projects (from scratch) and EXISTING projects (retroactive).

.PARAMETER ProjectName
    Name of the project (used for GitHub repo and documentation)

.PARAMETER TechStack
    Technology stack: laravel, kotlin, swift, nextjs, vite, other

.PARAMETER Database
    Database type: postgresql, mysql, sqlite, none

.PARAMETER IsPrivate
    Switch to create private GitHub repository (default is private)

.PARAMETER IsPublic
    Switch to create public GitHub repository

.PARAMETER ClientProject
    Switch to indicate this is a client project

.PARAMETER ClientName
    Name of the client (required if ClientProject is set)

.PARAMETER Phases
    Number of project phases (default: 6, range: 4-8)

.PARAMETER ProjectPath
    Absolute path where project should be created (default: current directory)

.PARAMETER SkipGitHub
    Skip GitHub repository creation (local only)

.PARAMETER ExistingProject
    Flag for existing project (retroactive setup)

.EXAMPLE
    .\SETUP_PROJECT.ps1 -ProjectName "my-app" -TechStack laravel -Database postgresql -IsPrivate

.EXAMPLE
    .\SETUP_PROJECT.ps1 -ProjectName "mobile-app" -TechStack kotlin -Database sqlite -ClientProject -ClientName "Acme Corp"

.EXAMPLE
    .\SETUP_PROJECT.ps1 -ProjectName "existing-app" -ExistingProject -TechStack laravel

.NOTES
    Version: 1.0
    Author: Maxwell Murunga (@maxymurm)
    Company: Advent Digital
    Last Updated: January 27, 2026
    
    Requirements:
    - GitHub CLI (gh) installed and authenticated
    - Git installed
    - Global templates at ~/.config/agents/
#>

[CmdletBinding()]
param(
    [Parameter(Mandatory=$true, HelpMessage="Project name (GitHub repo name)")]
    [string]$ProjectName,
    
    [Parameter(Mandatory=$true, HelpMessage="Tech stack: laravel, kotlin, swift, nextjs, vite, other")]
    [ValidateSet("laravel", "kotlin", "swift", "nextjs", "vite", "other")]
    [string]$TechStack,
    
    [Parameter(Mandatory=$false, HelpMessage="Database: postgresql, mysql, sqlite, none")]
    [ValidateSet("postgresql", "mysql", "sqlite", "none")]
    [string]$Database = "postgresql",
    
    [Parameter(Mandatory=$false)]
    [switch]$IsPrivate,
    
    [Parameter(Mandatory=$false)]
    [switch]$IsPublic,
    
    [Parameter(Mandatory=$false)]
    [switch]$ClientProject,
    
    [Parameter(Mandatory=$false)]
    [string]$ClientName,
    
    [Parameter(Mandatory=$false, HelpMessage="Number of phases (4-8)")]
    [ValidateRange(4, 8)]
    [int]$Phases = 6,
    
    [Parameter(Mandatory=$false)]
    [string]$ProjectPath = $PWD.Path,
    
    [Parameter(Mandatory=$false)]
    [switch]$SkipGitHub,
    
    [Parameter(Mandatory=$false)]
    [switch]$ExistingProject
)

# Script configuration
$ErrorActionPreference = "Stop"
$GitHubUsername = "maxymurm"
$GlobalAgentsPath = "$env:USERPROFILE\.config\agents"

# Color functions
function Write-Step { param([string]$Message) Write-Host "`nðŸ“‹ $Message" -ForegroundColor Cyan }
function Write-Success { param([string]$Message) Write-Host "âœ… $Message" -ForegroundColor Green }
function Write-Error { param([string]$Message) Write-Host "âŒ $Message" -ForegroundColor Red }
function Write-Warning { param([string]$Message) Write-Host "âš ï¸  $Message" -ForegroundColor Yellow }
function Write-Info { param([string]$Message) Write-Host "â„¹ï¸  $Message" -ForegroundColor Gray }

# Banner
function Show-Banner {
    Write-Host "`nâ•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—" -ForegroundColor Cyan
    Write-Host "â•‘         ðŸš€ PROJECT AUTOMATION SETUP SCRIPT ðŸš€                â•‘" -ForegroundColor Cyan
    Write-Host "â•‘                                                               â•‘" -ForegroundColor Cyan
    Write-Host "â•‘  Executes all 23 steps from PROJECT_INITIALIZATION.md        â•‘" -ForegroundColor Cyan
    Write-Host "â•‘  Complete project setup in 5-10 minutes!                     â•‘" -ForegroundColor Cyan
    Write-Host "â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•" -ForegroundColor Cyan
    Write-Host ""
}

# Validation functions
function Test-Prerequisites {
    Write-Step "Phase 0: Prerequisites - Validating setup"
    
    # Check Git
    try {
        $gitVersion = git --version
        Write-Success "Git installed: $gitVersion"
    } catch {
        Write-Error "Git is not installed. Please install Git first."
        exit 1
    }
    
    # Check GitHub CLI
    if (-not $SkipGitHub) {
        try {
            $ghVersion = gh --version | Select-Object -First 1
            Write-Success "GitHub CLI installed: $ghVersion"
        } catch {
            Write-Error "GitHub CLI is not installed. Run: winget install --id GitHub.cli"
            exit 1
        }
        
        # Check GitHub authentication
        try {
            $ghStatus = gh auth status 2>&1
            if ($ghStatus -match "Logged in") {
                Write-Success "GitHub CLI authenticated"
            } else {
                Write-Error "GitHub CLI not authenticated. Run: gh auth login"
                exit 1
            }
        } catch {
            Write-Error "GitHub CLI not authenticated. Run: gh auth login"
            exit 1
        }
    }
    
    # Check global templates
    if (-not (Test-Path "$GlobalAgentsPath\GLOBAL_MEMORY.md")) {
        Write-Warning "Global memory not found at $GlobalAgentsPath\GLOBAL_MEMORY.md"
        Write-Info "Please run global setup first (see agents/README.md)"
        
        $response = Read-Host "Continue anyway? (y/n)"
        if ($response -ne "y") {
            exit 1
        }
    } else {
        Write-Success "Global memory found"
    }
    
    # Validate ClientName if ClientProject
    if ($ClientProject -and [string]::IsNullOrWhiteSpace($ClientName)) {
        Write-Error "ClientName is required when ClientProject is set"
        exit 1
    }
    
    # Set repository visibility
    $script:RepoVisibility = if ($IsPublic) { "public" } else { "private" }
    
    Write-Success "All prerequisites validated"
}

# Phase 0: Issue #2 - Prepare project directory
function Initialize-ProjectDirectory {
    Write-Step "Issue #2: Prepare project directory"
    
    $fullPath = Join-Path $ProjectPath $ProjectName
    
    if ($ExistingProject) {
        if (-not (Test-Path $fullPath)) {
            Write-Error "Project directory does not exist: $fullPath"
            exit 1
        }
        Write-Success "Using existing project directory: $fullPath"
    } else {
        if (Test-Path $fullPath) {
            Write-Warning "Directory already exists: $fullPath"
            $response = Read-Host "Continue and overwrite? (y/n)"
            if ($response -ne "y") {
                exit 1
            }
        } else {
            New-Item -Path $fullPath -ItemType Directory -Force | Out-Null
            Write-Success "Created project directory: $fullPath"
        }
    }
    
    Set-Location $fullPath
    $script:ProjectFullPath = $fullPath
    Write-Info "Working directory: $fullPath"
}

# Phase 1: Issue #4 - Initialize Git
function Initialize-GitRepository {
    if ($ExistingProject -and (Test-Path ".git")) {
        Write-Success "Git repository already exists (existing project)"
        return
    }
    
    Write-Step "Issue #4: Initialize local Git repository"
    
    git init | Out-Null
    git branch -M main | Out-Null
    Write-Success "Initialized Git repository with main branch"
    
    # Create .gitignore
    $gitignoreContent = @"
# Dependencies
node_modules/
vendor/
*.log

# Environment
.env
.env.local
.env.*.local

# IDE
.vscode/
.idea/
*.swp
*.swo

# OS
.DS_Store
Thumbs.db
desktop.ini

# Build outputs
dist/
build/
.next/
out/

# Testing
coverage/
.nyc_output/

# Temp files
*.tmp
*.bak
~`$*

# Agents folder (optional - uncomment to exclude)
# agents/
"@
    
    Set-Content -Path ".gitignore" -Value $gitignoreContent -Encoding UTF8
    Write-Success "Created .gitignore"
}

# Phase 1: Issue #5 - Create GitHub repository
function New-GitHubRepository {
    if ($SkipGitHub) {
        Write-Info "Skipping GitHub repository creation (local only)"
        return
    }
    
    if ($ExistingProject) {
        # Check if remote exists
        try {
            $remote = git remote get-url origin 2>$null
            if ($remote) {
                Write-Success "GitHub repository already linked: $remote"
                return
            }
        } catch {
            # No remote, continue to create
        }
    }
    
    Write-Step "Issue #5: Create GitHub repository"
    
    try {
        if ($script:RepoVisibility -eq "private") {
            gh repo create $ProjectName --private --source=. --remote=origin | Out-Null
        } else {
            gh repo create $ProjectName --public --source=. --remote=origin | Out-Null
        }
        Write-Success "Created $($script:RepoVisibility) GitHub repository: $ProjectName"
    } catch {
        Write-Warning "GitHub repository creation failed. It may already exist."
        Write-Info "Attempting to link existing repository..."
        try {
            git remote add origin "https://github.com/$GitHubUsername/$ProjectName.git" 2>$null
            Write-Success "Linked to existing repository"
        } catch {
            Write-Warning "Could not link remote. You may need to do this manually."
        }
    }
}

# Phase 1: Issue #6 - Create README and LICENSE
function New-ProjectFiles {
    Write-Step "Issue #6: Create README and LICENSE"
    
    # Tech stack mapping
    $techStackFull = switch ($TechStack) {
        "laravel" { "Laravel 11 / PHP 8.3" }
        "kotlin" { "Kotlin / Android" }
        "swift" { "Swift / iOS" }
        "nextjs" { "Next.js / React / TypeScript" }
        "vite" { "Vite / Vue / TypeScript" }
        default { $TechStack }
    }
    
    $databaseFull = switch ($Database) {
        "postgresql" { "PostgreSQL" }
        "mysql" { "MySQL" }
        "sqlite" { "SQLite" }
        "none" { "None" }
        default { $Database }
    }
    
    # README
    $readmeContent = @"
# $ProjectName

## Description
[Brief project description - to be filled in]

## Tech Stack
- **Backend:** $techStackFull
- **Database:** $databaseFull
- **Framework:** [Specify framework]

## Status
ðŸš§ **In Development** - Phase 1 Setup

## Documentation
- [Project Documentation](docs/PROJECT_DOCUMENTATION.md)
- [Agent Automation Guide](agents/AGENTS.md)
- [Architecture Documentation](docs/architecture/)

## Quick Start
See [Setup Guide](docs/guides/setup.md) for local development setup.

## Team
- **Developer:** Maxwell Murunga (@$GitHubUsername)
- **Company:** Advent Digital
$(if ($ClientProject) { "- **Client:** $ClientName" } else { "" })

## License
See [LICENSE](LICENSE) file for details.
"@
    
    Set-Content -Path "README.md" -Value $readmeContent -Encoding UTF8
    Write-Success "Created README.md"
    
    # LICENSE
    $licenseContent = @"
MIT License

Copyright (c) $((Get-Date).Year) Maxwell Murunga / Advent Digital

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
"@
    
    Set-Content -Path "LICENSE" -Value $licenseContent -Encoding UTF8
    Write-Success "Created LICENSE"
}

# Phase 1: Issue #7 - Create develop branch
function New-DevelopBranch {
    Write-Step "Issue #7: Create develop branch"
    
    if ($ExistingProject) {
        try {
            $branches = git branch -a
            if ($branches -match "develop") {
                Write-Success "Develop branch already exists"
                return
            }
        } catch {}
    }
    
    if ($SkipGitHub) {
        git checkout -b develop 2>$null | Out-Null
        git checkout main 2>$null | Out-Null
        Write-Success "Created develop branch (local only)"
    } else {
        git checkout -b develop 2>$null | Out-Null
        try {
            git push -u origin develop 2>$null | Out-Null
            Write-Success "Created and pushed develop branch"
        } catch {
            Write-Warning "Could not push develop branch. Will retry after initial commit."
        }
        git checkout main 2>$null | Out-Null
    }
}

# Phase 2: Issue #8 - Create documentation directories
function New-DocumentationStructure {
    Write-Step "Issue #8: Create documentation directories"
    
    $dirs = @("docs", "docs\architecture", "docs\api", "docs\guides")
    if ($ClientProject) {
        $dirs += "docs\client"
    }
    
    foreach ($dir in $dirs) {
        if (-not (Test-Path $dir)) {
            New-Item -Path $dir -ItemType Directory -Force | Out-Null
        }
    }
    
    Write-Success "Created documentation structure"
}

# Phase 2: Issue #9-11 - Deploy documentation
function New-DocumentationFiles {
    Write-Step "Issues #9-11: Deploy documentation files"
    
    # Copy PROJECT_DOCUMENTATION template
    if (Test-Path "$GlobalAgentsPath\PROJECT_DOCUMENTATION_TEMPLATE.md") {
        Copy-Item "$GlobalAgentsPath\PROJECT_DOCUMENTATION_TEMPLATE.md" "docs\PROJECT_DOCUMENTATION.md"
        
        # Customize template
        $content = Get-Content "docs\PROJECT_DOCUMENTATION.md" -Raw
        $content = $content -replace '\[PROJECT NAME\]', $ProjectName
        $content = $content -replace '\[DATE TIME\]', (Get-Date -Format "MMMM dd, yyyy HH:mm")
        $content = $content -replace '\[username\]', $GitHubUsername
        $content = $content -replace '\[repo-name\]', $ProjectName
        Set-Content "docs\PROJECT_DOCUMENTATION.md" -Value $content -Encoding UTF8
        
        Write-Success "Deployed PROJECT_DOCUMENTATION.md"
    } else {
        Write-Warning "PROJECT_DOCUMENTATION_TEMPLATE.md not found in $GlobalAgentsPath"
    }
    
    # Copy AGENTS.md for reference
    if (Test-Path "$GlobalAgentsPath\AGENTS.md") {
        Copy-Item "$GlobalAgentsPath\AGENTS.md" "docs\AGENTS.md"
        Write-Success "Copied AGENTS.md reference"
    }
    
    # Create placeholder documentation files
    $setupGuide = @"
# Development Setup Guide

## Prerequisites
- $($TechStack.ToUpper()) development environment
- $Database database$(if ($Database -ne "none") { " server" } else { "" })
- Git
- GitHub CLI (optional)

## Local Development Setup
[Steps will be added as project develops]

## Running Tests
[Commands will be added]

## Common Issues
[Troubleshooting will be documented here]
"@
    Set-Content "docs\guides\setup.md" -Value $setupGuide -Encoding UTF8
    
    $archDoc = @"
# Architecture Documentation

[Architecture decisions and diagrams will be documented here as project develops]

## Database Design
[To be documented]

## API Design
[To be documented]

## System Architecture
[To be documented]
"@
    Set-Content "docs\architecture\README.md" -Value $archDoc -Encoding UTF8
    
    $apiDoc = @"
# API Documentation

[API endpoints will be documented here as they are developed]

## Authentication
[To be documented]

## Endpoints
[To be documented]
"@
    Set-Content "docs\api\README.md" -Value $apiDoc -Encoding UTF8
    
    Write-Success "Created documentation placeholders"
}

# Phase 3: Issue #12 - Create project memory
function New-ProjectMemory {
    Write-Step "Issue #12: Create project memory file"
    
    New-Item -Path ".github\instructions" -ItemType Directory -Force | Out-Null
    
    if (Test-Path "$GlobalAgentsPath\MEMORY_TEMPLATE.md") {
        Copy-Item "$GlobalAgentsPath\MEMORY_TEMPLATE.md" ".github\instructions\memory.instruction.md"
        
        # Customize template
        $content = Get-Content ".github\instructions\memory.instruction.md" -Raw
        $content = $content -replace 'PROJECT_NAME_WILL_BE_REPLACED', $ProjectName
        $content = $content -replace 'TIMESTAMP_WILL_BE_REPLACED', (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
        $content = $content -replace 'CURRENT_DATE_WILL_BE_REPLACED', (Get-Date -Format "MMMM dd, yyyy")
        $content = $content -replace 'CURRENT_TIME_WILL_BE_REPLACED', (Get-Date -Format "HH:mm:ss")
        Set-Content ".github\instructions\memory.instruction.md" -Value $content -Encoding UTF8
        
        Write-Success "Created and customized memory.instruction.md"
    } else {
        Write-Warning "MEMORY_TEMPLATE.md not found in $GlobalAgentsPath"
    }
}

# Phase 4: Issues #14-16 - GitHub templates
function New-GitHubTemplates {
    Write-Step "Issues #14-16: Create GitHub issue and PR templates"
    
    New-Item -Path ".github\ISSUE_TEMPLATE" -ItemType Directory -Force | Out-Null
    
    # Note: Templates temporarily simplified due to PowerShell YAML parsing issues
    # Full templates can be added manually later
    
    # Bug report template
    $bugTemplate = @'
---
name: Bug Report
about: Report a bug or issue
title: '[BUG] '
labels: bug
assignees: maxymurm
---

## ðŸ› Bug Description
[Clear description of the bug]

## ðŸ“‹ Steps to Reproduce
1. Go to '...'
2. Click on '...'
3. Scroll down to '...'
4. See error

## âœ… Expected Behavior
[What should happen]

## âŒ Actual Behavior
[What actually happens]

## ðŸ–¼ï¸ Screenshots
[If applicable, add screenshots]

## ðŸ”§ Environment
- **OS:** [Windows / macOS / Linux]
- **Browser:** [Chrome / Firefox / Safari / etc.]
- **Version:** [Application version if applicable]

## ðŸ“ Additional Context
[Any other context about the problem]

## ðŸ’¡ Possible Solution
[If you have ideas about what might be causing this]
'@
    Set-Content ".github\ISSUE_TEMPLATE\bug_report.md" -Value $bugTemplate -Encoding UTF8
    
    # Feature request template
    $featureTemplate = @'
---
name: Feature Request
about: Suggest a new feature or enhancement
title: '[FEATURE] '
labels: enhancement
assignees: maxymurm
---

## ðŸŽ¯ Feature Description
[Clear description of the feature]

## ðŸ” Problem Statement
[What problem does this solve? Why is this needed?]

## ðŸ’¡ Proposed Solution
[How should this feature work?]

## ðŸ”„ Alternatives Considered
[What other approaches did you consider?]

## âœ… Acceptance Criteria
- [ ] Criterion 1
- [ ] Criterion 2
- [ ] Criterion 3
- [ ] Criterion 4

## â±ï¸ Estimate
[Estimated development time: X hours]

## ðŸ”— Dependencies
[Does this depend on other issues? List them here]

## ðŸ“ Additional Context
[Mockups, examples, references, etc.]

## ðŸŽ¨ UI/UX Considerations
[If applicable, describe expected user experience]

## ðŸ§ª Testing Requirements
[What tests should be written?]
'@
    Set-Content ".github\ISSUE_TEMPLATE\feature_request.md" -Value $featureTemplate -Encoding UTF8
    
    # PR template
    $prTemplate = @'
# Pull Request

## ðŸ“ Description
[Describe what this PR does]

## ðŸ”— Related Issue
Closes #[issue number]

## ðŸ”„ Type of Change
- [ ] ðŸ› Bug fix (non-breaking change which fixes an issue)
- [ ] âœ¨ New feature (non-breaking change which adds functionality)
- [ ] ðŸ’¥ Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] ðŸ“ Documentation update
- [ ] â™»ï¸ Code refactoring
- [ ] âš¡ Performance improvement
- [ ] ðŸ§ª Test addition/update

## âœ… Testing Checklist
- [ ] Manual testing completed
- [ ] Unit tests added/updated
- [ ] All tests passing
- [ ] No console errors/warnings

## ðŸ“‹ Code Quality Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Comments added for complex logic
- [ ] Documentation updated (if needed)
- [ ] No unnecessary dependencies added

## ðŸ–¼ï¸ Screenshots (if applicable)
[Add screenshots of UI changes]

## ðŸ“ Additional Notes
[Any additional information for reviewers]

## âœ… Reviewer Checklist
- [ ] Code review completed
- [ ] Tests reviewed and passing
- [ ] Documentation reviewed
- [ ] Ready to merge
'@
    Set-Content ".github\pull_request_template.md" -Value $prTemplate -Encoding UTF8
    
    Write-Success "Created GitHub templates"
}

# Phase 5: Issues #17-18 - Define phases and milestones
function New-ProjectPhases {
    if ($SkipGitHub) {
        Write-Info "Skipping milestone creation (local only mode)"
        return
    }
    
    Write-Step "Issues #17-18: Define project phases and create milestones"
    
    # Phase definitions based on tech stack
    $phaseDefinitions = switch ($TechStack) {
        "laravel" {
            @(
                @{Title="Phase 1: Database & Models"; Description="Set up database schema, create models, establish relationships"},
                @{Title="Phase 2: Business Logic & Services"; Description="Implement core business logic and services"},
                @{Title="Phase 3: API Development"; Description="Build RESTful API endpoints"},
                @{Title="Phase 4: Admin Panel"; Description="Create admin interface with Filament"},
                @{Title="Phase 5: Testing & QA"; Description="Write tests and perform quality assurance"},
                @{Title="Phase 6: Deployment"; Description="Deploy to production and go live"}
            )
        }
        "kotlin" {
            @(
                @{Title="Phase 1: Project Setup & Architecture"; Description="Initialize project structure and architecture"},
                @{Title="Phase 2: Authentication & User Management"; Description="Implement user authentication and management"},
                @{Title="Phase 3: Core Features"; Description="Build main application features"},
                @{Title="Phase 4: UI Polish & Animations"; Description="Refine UI and add animations"},
                @{Title="Phase 5: Testing & Bug Fixes"; Description="Testing and bug resolution"},
                @{Title="Phase 6: App Store Submission"; Description="Prepare and submit to Play Store"}
            )
        }
        default {
            @(
                @{Title="Phase 1: Project Setup"; Description="Initialize project and set up development environment"},
                @{Title="Phase 2: Core Development"; Description="Build core features and functionality"},
                @{Title="Phase 3: Feature Enhancement"; Description="Add additional features and enhancements"},
                @{Title="Phase 4: Testing & QA"; Description="Comprehensive testing and quality assurance"},
                @{Title="Phase 5: Optimization"; Description="Performance optimization and refinement"},
                @{Title="Phase 6: Deployment"; Description="Deploy to production"}
            )
        }
    }
    
    # Use only the specified number of phases
    $phasesToCreate = $phaseDefinitions[0..($Phases-1)]
    
    # Create milestones
    $startDate = Get-Date
    foreach ($i in 0..($phasesToCreate.Length-1)) {
        $phase = $phasesToCreate[$i]
        $dueDate = $startDate.AddDays(($i + 1) * 21).ToString("yyyy-MM-ddT23:59:59Z") # 3 weeks per phase
        
        try {
            gh api "repos/$GitHubUsername/$ProjectName/milestones" `
                -f title="$($phase.Title)" `
                -f description="$($phase.Description)" `
                -f due_on="$dueDate" | Out-Null
            Write-Success "Created milestone: $($phase.Title)"
        } catch {
            Write-Warning "Could not create milestone: $($phase.Title)"
        }
        
        Start-Sleep -Milliseconds 500
    }
}

# Phase 6: Issues #19-21 - Project board and labels
function New-GitHubProjectBoard {
    if ($SkipGitHub) {
        Write-Info "Skipping project board creation (local only mode)"
        return
    }
    
    Write-Step "Issues #19-21: Create GitHub project board and labels"
    
    # Create project board
    try {
        gh project create --title "$ProjectName" --owner $GitHubUsername | Out-Null
        Write-Success "Created GitHub project board"
    } catch {
        Write-Warning "Could not create project board. May already exist or require additional permissions."
    }
    
    # Create labels
    $labels = @(
        @{Name="phase-1"; Description="Phase 1 tasks"; Color="0E8A16"},
        @{Name="phase-2"; Description="Phase 2 tasks"; Color="1D76DB"},
        @{Name="phase-3"; Description="Phase 3 tasks"; Color="5319E7"},
        @{Name="phase-4"; Description="Phase 4 tasks"; Color="C2E0C6"},
        @{Name="phase-5"; Description="Phase 5 tasks"; Color="FBB040"},
        @{Name="phase-6"; Description="Phase 6 tasks"; Color="D93F0B"},
        @{Name="enhancement"; Description="New feature"; Color="84B6EB"},
        @{Name="bug"; Description="Bug fix"; Color="D93F0B"},
        @{Name="documentation"; Description="Documentation"; Color="0075CA"},
        @{Name="refactor"; Description="Code refactoring"; Color="FBB040"},
        @{Name="test"; Description="Testing"; Color="1CD15D"},
        @{Name="blocked"; Description="Blocked by dependency"; Color="B60205"},
        @{Name="needs-review"; Description="Needs code review"; Color="FBCA04"},
        @{Name="in-progress"; Description="Currently being worked on"; Color="0E8A16"},
        @{Name="database"; Description="Database related"; Color="C5DEF5"},
        @{Name="api"; Description="API related"; Color="BFD4F2"},
        @{Name="frontend"; Description="Frontend related"; Color="D4C5F9"},
        @{Name="backend"; Description="Backend related"; Color="C2E0C6"}
    )
    
    foreach ($label in $labels) {
        try {
            gh label create $label.Name --description $label.Description --color $label.Color 2>$null | Out-Null
        } catch {
            # Label might already exist
        }
    }
    Write-Success "Created standard labels"
}

# Phase 7: Issue #23 - Initial commit
function New-InitialCommit {
    Write-Step "Issue #23: Initial commit and push"
    
    # Stage all files
    git add . | Out-Null
    
    # Create commit message
    $commitMessage = @"
chore: initial project setup

- Initialize git and GitHub repository
- Set up documentation structure (docs/)
- Deploy PROJECT_DOCUMENTATION and AGENTS templates
- Initialize project memory system (.github/instructions/)
- Create GitHub issue and PR templates
- Define project phases and create milestones
- Create GitHub project board with labels

Project: $ProjectName
Tech Stack: $TechStack
Database: $Database
$(if ($ClientProject) { "Client: $ClientName" } else { "" })

Project is ready for Phase 1 development!
Automated by SETUP_PROJECT.ps1
"@
    
    git commit -m $commitMessage | Out-Null
    Write-Success "Created initial commit"
    
    if (-not $SkipGitHub) {
        try {
            git push -u origin main 2>&1 | Out-Null
            Write-Success "Pushed to main branch"
            
            # Update develop
            git checkout develop 2>$null | Out-Null
            git merge main 2>$null | Out-Null
            git push origin develop 2>&1 | Out-Null
            git checkout main 2>$null | Out-Null
            Write-Success "Updated develop branch"
        } catch {
            Write-Warning "Could not push to GitHub. You may need to push manually."
        }
    }
}

# Summary
function Show-Summary {
    Write-Host "`nâ•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—" -ForegroundColor Green
    Write-Host "â•‘                 âœ… SETUP COMPLETE! âœ…                        â•‘" -ForegroundColor Green
    Write-Host "â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•" -ForegroundColor Green
    
    Write-Host "`nðŸ“Š Project Summary:" -ForegroundColor Cyan
    Write-Host "   Name: $ProjectName" -ForegroundColor White
    Write-Host "   Tech Stack: $TechStack" -ForegroundColor White
    Write-Host "   Database: $Database" -ForegroundColor White
    if ($ClientProject) {
        Write-Host "   Client: $ClientName" -ForegroundColor White
    }
    Write-Host "   Location: $ProjectFullPath" -ForegroundColor White
    if (-not $SkipGitHub) {
        Write-Host "   Repository: https://github.com/$GitHubUsername/$ProjectName" -ForegroundColor White
    }
    
    Write-Host "`nâœ… What was created:" -ForegroundColor Cyan
    Write-Host "   âœ“ Git repository (main and develop branches)" -ForegroundColor Green
    Write-Host "   âœ“ GitHub repository ($($script:RepoVisibility))" -ForegroundColor Green
    Write-Host "   âœ“ Documentation structure (docs/)" -ForegroundColor Green
    Write-Host "   âœ“ Memory system (.github/instructions/)" -ForegroundColor Green
    Write-Host "   âœ“ GitHub templates (issues and PRs)" -ForegroundColor Green
    Write-Host "   âœ“ Project milestones ($Phases phases)" -ForegroundColor Green
    Write-Host "   âœ“ GitHub project board" -ForegroundColor Green
    Write-Host "   âœ“ Standard labels" -ForegroundColor Green
    Write-Host "   âœ“ README.md and LICENSE" -ForegroundColor Green
    
    Write-Host "`nðŸš€ Next Steps:" -ForegroundColor Cyan
    Write-Host "   1. Review docs/PROJECT_DOCUMENTATION.md" -ForegroundColor White
    Write-Host "   2. Check GitHub project board: gh browse" -ForegroundColor White
    Write-Host "   3. Start Phase 1 development!" -ForegroundColor White
    Write-Host "   4. AI agent will use .github/instructions/memory.instruction.md for context" -ForegroundColor White
    
    Write-Host "`nTip: Say to agent 'Continue with Phase 1' to start development!" -ForegroundColor Yellow
    Write-Host ""
}

# Main execution
try {
    Show-Banner
    
    Write-Host "ðŸ“‹ Configuration:" -ForegroundColor Cyan
    Write-Host "   Project Name: $ProjectName" -ForegroundColor White
    Write-Host "   Tech Stack: $TechStack" -ForegroundColor White
    Write-Host "   Database: $Database" -ForegroundColor White
    Write-Host "   Visibility: $($script:RepoVisibility)" -ForegroundColor White
    Write-Host "   Phases: $Phases" -ForegroundColor White
    if ($ClientProject) {
        Write-Host "   Client: $ClientName" -ForegroundColor White
    }
    if ($ExistingProject) {
        Write-Host "   Mode: Existing Project (Retroactive Setup)" -ForegroundColor Yellow
    } else {
        Write-Host "   Mode: New Project" -ForegroundColor White
    }
    Write-Host ""
    
    $response = Read-Host "Proceed with setup? (y/n)"
    if ($response -ne "y") {
        Write-Warning "Setup cancelled by user"
        exit 0
    }
    
    # Execute all phases
    Test-Prerequisites                # Phase 0: Issues #1
    Initialize-ProjectDirectory       # Phase 0: Issue #2
    # Issue #3 (Define scope) is handled by parameters
    
    Initialize-GitRepository          # Phase 1: Issue #4
    New-GitHubRepository             # Phase 1: Issue #5
    New-ProjectFiles                 # Phase 1: Issue #6
    New-DevelopBranch               # Phase 1: Issue #7
    
    New-DocumentationStructure       # Phase 2: Issue #8
    New-DocumentationFiles          # Phase 2: Issues #9-11
    
    New-ProjectMemory               # Phase 3: Issue #12
    # Issue #13 (Verify global memory) done in prerequisites
    
    New-GitHubTemplates             # Phase 4: Issues #14-16
    
    New-ProjectPhases               # Phase 5: Issues #17-18
    
    New-GitHubProjectBoard          # Phase 6: Issues #19-21
    
    # Phase 7: Issue #22 (Break down Phase 1) is interactive - skip in script
    New-InitialCommit               # Phase 7: Issue #23
    
    Show-Summary
    
} catch {
    Write-Error "Setup failed: $_"
    Write-Host "`nError details:" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    Write-Host "`nStack trace:" -ForegroundColor Red
    Write-Host $_.ScriptStackTrace -ForegroundColor Red
    exit 1
}
