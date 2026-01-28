# 🤖 AI Agent Automation Guide

**Version:** 2.0  
**Last Updated:** January 26, 2026  
**Purpose:** Comprehensive instructions for AI agents to automate project workflows, GitHub integration, and documentation

---

## 📋 Table of Contents

1. [Philosophy & Principles](#philosophy--principles)
2. [Memory System - Context Persistence](#memory-system---context-persistence)
3. [One-Time Global Setup](#one-time-global-setup)
4. [Project Initialization Workflow](#project-initialization-workflow)
5. [Daily Development Workflow](#daily-development-workflow)
6. [GitHub Automation](#github-automation)
   - [Issue Scoping & Creation Workflow](#-issue-scoping--creation-workflow) ⭐ **PRIMARY METHOD**
   - [Label Enforcement Rules](#label-enforcement-critical)
   - [Auto-Add to Project Board](#auto-add-to-project-board)
   - [Programmatic Issue Creation](#creating-issues-programmatically)
   - [Project Board Automation](#project-board-automation)
   - [Milestone Management](#milestone-management)
   - [Label Management](#label-management)
7. [Documentation Automation](#documentation-automation)
8. [Phase Management](#phase-management)
9. [Client Project Workflows](#client-project-workflows)
10. [Existing Project Adoption](#existing-project-adoption)
11. [Templates & Examples](#templates--examples)
12. [Troubleshooting](#troubleshooting)
13. [Quick Reference Checklists](#quick-reference-checklists)

---

## 🎯 Philosophy & Principles

### Core Values
1. **Automate Everything**: Git commits, pushes, issue creation, documentation updates
2. **Phase-Based Development**: All projects broken into logical phases from start to finish
3. **Documentation First**: Generate and update documentation as you build
4. **GitHub as Source of Truth**: Issues, milestones, and project boards track all work
5. **Conventional Commits**: Standardized commit messages with issue references

### Project Types Supported
- **Web Applications** (Laravel/PHP primary)
- **Mobile Applications** (Kotlin/Android, Swift/iOS)
- **APIs & Backend Services**
- **Mixed: Java, Objective-C, Xcode, Android Studio**

### Key Automation Goals
- ✅ Auto-create GitHub repos, issues, milestones, project boards
- ✅ Auto-commit and push at logical checkpoints
- ✅ Auto-update PROJECT_DOCUMENTATION.md throughout development
- ✅ Auto-close issues with commit messages
- ✅ Auto-move cards on project boards based on status
- ✅ Retrospectively document existing projects
- ✅ Maintain context across chat sessions with memory files

---

## 🧠 Memory System - Context Persistence

### Why Memory Files?

**Problem:** Every new chat session requires re-reading entire codebase, wasting tokens and time.  
**Solution:** Memory files store recent context, decisions, preferences, and file mappings.

**Benefits:**
- 💰 **Save Tokens:** Avoid re-reading entire codebase
- 🎯 **Faster Context:** Jump straight to current focus
- 📝 **Decision History:** Remember why decisions were made
- 🔄 **Cross-Session Continuity:** Pick up exactly where you left off
- 👥 **Team Context:** Share project knowledge with new team members

### Two-Tier Memory System

#### 1. Global Memory (Cross-Project)
**Location:** `~/.config/agents/GLOBAL_MEMORY.md`  
**Committed to Git:** ❌ No (personal preferences)  
**Scope:** Universal preferences across ALL projects

**Contains:**
- Developer profile and contact info
- Technology stack preferences (Laravel, Kotlin, Swift, etc.)
- Coding style conventions (PSR-12, Android/iOS guidelines)
- Git workflow preferences (branch strategy, commit format)
- Project management preferences (always phase-based, issue structure)
- Documentation preferences (update frequency, format)
- Client work patterns (meeting notes, quote styling)
- Common code patterns and snippets
- Security and performance best practices
- What NOT to do (common mistakes)

**Update Frequency:** When establishing new global preferences

#### 2. Per-Project Memory (Project-Specific)
**Location:** `.github/instructions/memory.instruction.md`  
**Committed to Git:** ✅ Yes (team visibility)  
**Scope:** This specific project only

**Contains:**
- Current focus (active phase, active issue, current branch)
- Recent decisions and context (timestamped)
- Project file map (what's where, frequently modified files)
- Feature → file mappings (where is X implemented?)
- User preferences specific to this project
- Common patterns used in this project
- Things to remember (client preferences, next invoice number, etc.)
- Current blockers and issues
- Recent conversations (last 10 significant interactions)
- Lessons learned on this project

**Update Frequency:** 
- After completing each task
- After making significant decisions
- When user says "remember this"
- At end of chat session

### Memory File Structure

#### Front Matter (Required)
```yaml
---
applyTo: '**'
lastUpdated: '2026-01-26 15:45'
chatSession: 'session-001'
projectName: 'Project Name'
---
```

#### Standard Sections

1. **🎯 Current Focus**
   - Active phase, issue, branch
   - What we're working on right now
   - Next immediate steps

2. **👤 User Preferences** (per-project specifics)
   - Coding style for this project
   - Git workflow for this project
   - Project management approach
   - Documentation preferences

3. **📁 Project File Map**
   - Critical files (frequently modified)
   - Feature → file mappings
   - Where is X implemented?

4. **💭 Recent Decisions & Context**
   - Timestamped decision log
   - Why decisions were made
   - Impact of decisions

5. **🧩 Patterns & Architecture**
   - Architecture patterns used here
   - Code organization structure
   - Common code snippets for this project

6. **🔧 Things to Remember**
   - Client-specific info
   - Next quote/invoice numbers
   - Deployment locations
   - Configuration details

7. **🚧 Current Blockers & Issues**
   - Active blockers
   - Known issues

8. **📝 Recent Conversations**
   - Last 10 significant interactions
   - Context for continuity

9. **🎓 Lessons Learned**
   - What worked well
   - What to improve
   - Patterns to reuse

10. **📊 Project Statistics**
    - File counts, metrics
    - Phase status

### Agent Instructions for Memory

#### CRITICAL: Always Read Memory First

**At the START of EVERY new conversation:**

1. **Read Global Memory FIRST**
   ```
   Location: ~/.config/agents/GLOBAL_MEMORY.md
   Purpose: Get universal preferences
   ```

2. **Then Read Project Memory**
   ```
   Location: .github/instructions/memory.instruction.md
   Purpose: Get project-specific context
   ```

3. **Then Begin Work**
   - You now have full context without reading entire codebase
   - You know current focus, recent decisions, where files are
   - You understand user preferences and project patterns

#### When to Update Memory

**Update Immediately After:**
- ✅ Completing a TODO item
- ✅ Making a significant decision
- ✅ Completing a phase
- ✅ User says "remember this" or "add to memory"
- ✅ Establishing a new pattern or preference
- ✅ Encountering a blocker or issue
- ✅ Client meeting or significant conversation
- ✅ At end of chat session (summary)

**Update Process:**
1. Read current memory file
2. Add new entry with timestamp
3. Keep recent entries (last 30 days)
4. Archive older entries if needed
5. Update "lastUpdated" timestamp
6. Increment chat session if new session

#### Memory Update Template

```markdown
### YYYY-MM-DD

#### HH:MM - Decision/Task Name (Issue #N if applicable)
**Type:** Decision | Task Complete | Pattern Established | Blocker  
**Context:** [Brief context]

**What Happened:**
- Bullet point 1
- Bullet point 2

**Why This Matters:**
- Impact 1
- Impact 2

**Files Affected:** (if applicable)
- path/to/file.ext

**Remember For Next Time:**
- Key takeaway 1
- Key takeaway 2
```

### Token Optimization Strategy

Memory files help save tokens by:

1. **File Path → Purpose Mapping**
   ```markdown
   ### Feature → File Mapping
   - User authentication: app/Services/AuthService.php
   - User model: app/Models/User.php
   - Auth API: routes/api.php (lines 50-75)
   ```
   → Agent knows where to look without searching

2. **Recently Modified Files**
   ```markdown
   ### Frequently Modified (Last 7 Days)
   - app/Models/User.php (5 edits)
   - config/database.php (3 edits)
   - docs/PROJECT_DOCUMENTATION.md (10 edits)
   ```
   → Agent focuses on active areas

3. **Current Focus Summary**
   ```markdown
   **What We're Working On:**
   - Implementing user authentication (Issue #5)
   - Files: AuthService.php, AuthController.php
   - Progress: 75% complete, just need testing
   ```
   → Agent knows exact context immediately

4. **Decision History**
   ```markdown
   **Why We Use Repository Pattern:**
   - Decided 2026-01-20 (Issue #3)
   - Rationale: Testability, separation of concerns
   - Location: app/Repositories/
   ```
   → Agent understands architecture without re-analysis

### Memory Maintenance

#### Archiving Old Memory

When memory file gets too large (>100KB):

1. **Create Archive**
   ```bash
   mkdir -p .github/instructions/archives
   cp .github/instructions/memory.instruction.md .github/instructions/archives/memory-2026-01.md
   ```

2. **Keep Recent Context**
   - Keep last 30 days of conversations
   - Keep all "Things to Remember" section
   - Keep current focus and file mappings
   - Archive older conversation history

3. **Update Memory File**
   ```markdown
   **Archived Memory:**
   - January 2026: .github/instructions/archives/memory-2026-01.md
   ```

#### Syncing with PROJECT_DOCUMENTATION.md

Memory and PROJECT_DOCUMENTATION.md serve different purposes:

**Memory (Short-term, Conversational):**
- Recent conversations and decisions
- Current focus and active work
- "Working memory" for agents
- Updated constantly throughout development

**PROJECT_DOCUMENTATION.md (Long-term, Comprehensive):**
- Complete project overview
- All phases and their status
- Complete change log (all time)
- Architecture decisions (permanent)
- Setup instructions
- API documentation

**Sync Strategy:**
- Major decisions in memory → Eventually move to PROJECT_DOCUMENTATION.md
- Completed tasks in memory → Summarized in PROJECT_DOCUMENTATION.md change log
- Memory provides detail, PROJECT_DOCUMENTATION.md provides overview

### Creating Memory Files

#### For New Projects

**During Project Initialization:**

```bash
# Create directory
mkdir -p .github/instructions

# Copy template
cp ~/.config/agents/MEMORY_TEMPLATE.md .github/instructions/memory.instruction.md

# Initialize with project details
# Agent will update with:
# - Project name
# - Tech stack
# - Initial focus (Phase 1 planning)
# - User preferences for this project
```

#### For Existing Projects

**During Project Adoption:**

```bash
# Create directory
mkdir -p .github/instructions

# Copy template
cp ~/.config/agents/MEMORY_TEMPLATE.md .github/instructions/memory.instruction.md

# Agent will analyze and populate:
# - Git history for context
# - Existing file structure
# - Current state of project
# - Retrospective phases completed
# - Current focus (what's next)
```

### Example Memory Usage

#### Scenario: New Chat Session

**Without Memory:**
```
Agent: "I need to understand the project. Let me read..."
[Reads 50+ files, 10,000+ lines]
[Analyzes git history]
[Figures out current state]
[20,000 tokens used]
```

**With Memory:**
```
Agent: "Reading memory..."
[Reads GLOBAL_MEMORY.md - 500 lines]
[Reads memory.instruction.md - 800 lines]
Agent: "I see we're working on Issue #5, implementing authentication.
       Files are AuthService.php and AuthController.php.
       We decided on JWT approach on 2026-01-20.
       Progress is 75%, just need testing. Let me continue."
[2,000 tokens used, ready to work immediately]
```

**Token Savings: 90%**

#### Scenario: Remembering Client Preference

**User says:** "Remember that this client prefers blue color scheme (#2563eb)"

**Agent does:**
1. Reads `.github/instructions/memory.instruction.md`
2. Finds "Things to Remember" section
3. Adds entry:
   ```markdown
   ### Client Preferences
   - Color scheme: Blue (#2563eb primary, #1e40af dark)
   - Applied to: Quotes, invoices, documentation
   - Requested: 2026-01-26
   ```
4. Saves and commits memory update

**Next session:** Agent automatically uses blue color scheme without asking

### Best Practices

#### DO:
- ✅ Read memory files FIRST in every new conversation
- ✅ Update memory after completing tasks
- ✅ Add timestamps to all memory entries
- ✅ Keep "Current Focus" section up to date
- ✅ Archive old memory when file gets large (>100KB)
- ✅ Commit project memory to git
- ✅ Use memory to store file path mappings
- ✅ Update memory when user says "remember this"
- ✅ Use memory to avoid re-analyzing codebase

#### DON'T:
- ❌ Skip reading memory at start of conversation
- ❌ Forget to update memory after decisions
- ❌ Let memory file become stale
- ❌ Duplicate PROJECT_DOCUMENTATION.md content
- ❌ Store secrets in memory files
- ❌ Commit global memory to git (personal preferences)
- ❌ Make memory file too verbose (be concise)

### Memory File Templates

**Templates are located:**
- `~/.config/agents/GLOBAL_MEMORY.md` (template deployed during setup)
- `~/.config/agents/MEMORY_TEMPLATE.md` (copy to `.github/instructions/` for new projects)

**Included in this documentation package:**
- GLOBAL_MEMORY_TEMPLATE.md (created in docs/)
- memory.instruction.md (example for Pinsoft project in .github/instructions/)

---

## 🚀 One-Time Global Setup

### Step 1: Install GitHub CLI

**Windows (PowerShell):**
```powershell
winget install --id GitHub.cli
```

**macOS:**
```bash
brew install gh
```

**Linux:**
```bash
curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg | sudo dd of=/usr/share/keyrings/githubcli-archive-keyring.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | sudo tee /etc/apt/sources.list.d/github-cli.list > /dev/null
sudo apt update
sudo apt install gh
```

### Step 2: Authenticate GitHub CLI

```bash
gh auth login
```

**Select:**
- Account: `maxymurm` (personal) or `maximurm` (organization work)
- Protocol: HTTPS
- Authenticate: Use classic token with **all permissions** (repo, issues, projects, workflows, admin)

**Create Token:** https://github.com/settings/tokens/new
- Scopes: `repo`, `workflow`, `project`, `admin:org`, `user`
- Save token in: `~/.config/gh/token.txt` (add to .gitignore)

### Step 3: Set Global Git Configuration

```bash
git config --global user.name "Maxwell Murunga"
git config --global user.email "maxmm@adventit.digital"
git config --global init.defaultBranch main
```

### Step 4: Create Global Agent Templates Directory

**Windows:**
```powershell
mkdir $env:USERPROFILE\.config\agents -Force
```

**macOS/Linux:**
```bash
mkdir -p ~/.config/agents
```

### Step 5: Deploy Global Templates

Copy these files to `~/.config/agents/`:
- `AGENTS.md` (this file)
- `PROJECT_DOCUMENTATION_TEMPLATE.md`
- `ISSUE_TEMPLATE_BUG.md`
- `ISSUE_TEMPLATE_FEATURE.md`
- `PR_TEMPLATE.md`
- `CLIENT_MEETING_TEMPLATE.md`
- `QUOTE_TEMPLATE.html`

**Command:**
```powershell
# Windows
Copy-Item "docs\AGENTS.md" "$env:USERPROFILE\.config\agents\" -Force
Copy-Item "docs\.github\*" "$env:USERPROFILE\.config\agents\" -Recurse -Force
```

```bash
# macOS/Linux
cp docs/AGENTS.md ~/.config/agents/
cp -r docs/.github/* ~/.config/agents/
```

---

## 🆕 Project Initialization Workflow

**When to use:** Starting a brand new project from scratch

### Step 1: Create Project Directory

```powershell
# Navigate to projects directory
cd C:\Users\maxmm\OneDrive\المستندات\Projects

# Create project folder
mkdir project-name
cd project-name
```

### Step 2: Initialize Git Repository

```bash
git init
git branch -M main
```

### Step 3: Create GitHub Repository

```bash
# Create repo on GitHub (--public or --private)
gh repo create project-name --private --source=. --remote=origin

# Verify remote
git remote -v
```

### Step 4: Create Initial Files

Create these files in order:

**1. .gitignore**
```bash
# Copy language-specific template
gh api /gitignore/templates/Laravel > .gitignore

# Add agent-specific ignores
echo "# Agent Configuration" >> .gitignore
echo ".agent-state/" >> .gitignore
echo "*.token.txt" >> .gitignore
```

**2. README.md**
```bash
# Create from template
echo "# Project Name" > README.md
echo "" >> README.md
echo "## Overview" >> README.md
echo "Brief project description..." >> README.md
```

**3. LICENSE**
```bash
# MIT License by default
gh api /licenses/mit | jq -r .body > LICENSE
```

### Step 5: Create Documentation Structure

```bash
mkdir docs
mkdir docs/architecture
mkdir docs/api
mkdir docs/guides
mkdir docs/client
```

### Step 6: Initialize PROJECT_DOCUMENTATION.md

```bash
# Copy global template to project
cp ~/.config/agents/PROJECT_DOCUMENTATION_TEMPLATE.md docs/PROJECT_DOCUMENTATION.md

# Update with project name
# (Agent: Replace placeholders with actual project details)
```

**Template will include:**
- Project Overview
- Technology Stack
- Phase Breakdown
- Current Phase Status
- Completed Tasks
- Pending Tasks
- Architecture Decisions
- API Documentation
- Setup Instructions

### Step 7: Create GitHub Issue Templates

```bash
mkdir -p .github/ISSUE_TEMPLATE
cp ~/.config/agents/ISSUE_TEMPLATE_BUG.md .github/ISSUE_TEMPLATE/bug_report.md
cp ~/.config/agents/ISSUE_TEMPLATE_FEATURE.md .github/ISSUE_TEMPLATE/feature_request.md
cp ~/.config/agents/PR_TEMPLATE.md .github/pull_request_template.md
```

### Step 8: Create Project Phases

**Agent Instructions:**
1. Analyze project scope
2. Break down into logical phases (typically 4-8 phases)
3. Create milestone for each phase with due dates

**Example for Laravel Web App:**
- Phase 1: Database Design & Migration (Week 1)
- Phase 2: Laravel Foundation & Models (Week 2)
- Phase 3: API Development (Week 3-4)
- Phase 4: UI/Frontend Implementation (Week 5-6)
- Phase 5: Testing & QA (Week 7)
- Phase 6: Deployment & Go-Live (Week 8)

**Create Milestones:**
```bash
gh api repos/maxymurm/project-name/milestones -f title="Phase 1: Database Design" -f due_on="2026-02-07T23:59:59Z"
gh api repos/maxymurm/project-name/milestones -f title="Phase 2: Laravel Foundation" -f due_on="2026-02-14T23:59:59Z"
# Continue for all phases...
```

### Step 9: Create Project Board

```bash
# Create project board
gh project create --title "project-name" --owner maxymurm

# Get project ID
PROJECT_ID=$(gh project list --owner maxymurm --format json | jq -r '.projects[] | select(.title=="project-name") | .number')

# Add default columns (GitHub Projects v2 uses fields)
gh project field-create $PROJECT_ID --owner maxymurm --name Status --data-type SINGLE_SELECT --single-select-options "Backlog,Todo,In Progress,Review,Done"
```

### Step 10: Break Down Phase 1 into Issues

**Agent Instructions:**
For the current phase (Phase 1), create detailed issues:

1. **List all tasks** for Phase 1
2. **Estimate time** for each task (hours)
3. **Identify dependencies** between tasks
4. **Create GitHub issue** for each task

**Example:**
```bash
# Create issue with template
gh issue create \
  --title "1.1: Design database schema for users table" \
  --body "**Description:**
Design and document the users table schema including:
- Fields: id, name, email, password, created_at, updated_at
- Indexes: unique email, id primary key
- Relationships: one-to-many with posts

**Acceptance Criteria:**
- [ ] Schema documented in docs/database/users.md
- [ ] Migration file created
- [ ] Model relationships defined

**Estimate:** 2 hours
**Dependencies:** None" \
  --milestone "Phase 1: Database Design" \
  --assignee maxymurm \
  --label "enhancement,database,phase-1" \
  --project "project-name"

# Add to Todo column
ISSUE_ID=$(gh issue list --limit 1 --json number --jq '.[0].number')
gh project item-add $PROJECT_ID --owner maxymurm --url "https://github.com/maxymurm/project-name/issues/$ISSUE_ID"
```

### Step 11: Initial Commit

```bash
git add .
git commit -m "chore: initial project setup with documentation structure

- Initialize repository with .gitignore, README, LICENSE
- Create documentation structure in /docs
- Set up GitHub issue templates and PR template
- Create PROJECT_DOCUMENTATION.md from template
- Configure project phases and milestones

Related: Initial setup"

git push -u origin main
```

### Step 12: Create Development Branch

```bash
git checkout -b develop
git push -u origin develop

# Create feature branch for first task
git checkout -b feature/issue-1-users-table
```

---

## 💻 Daily Development Workflow

**When to use:** During active development on the project

### Workflow Loop

```
1. Pick issue from Todo column
2. Move issue to "In Progress"
3. Create/checkout feature branch
4. Implement changes
5. Update PROJECT_DOCUMENTATION.md
6. Commit with conventional commit + issue ref
7. Push to feature branch
8. Move issue to "Review" or "Done"
9. Merge to develop (if complete)
10. Repeat
```

### Step-by-Step Process

#### 1. **Start Working on Issue #5**

```bash
# Get issue details
gh issue view 5

# Move to "In Progress" on project board
gh project item-edit --id <ITEM_ID> --field-id <STATUS_FIELD_ID> --project-id $PROJECT_ID --single-select-option-id <IN_PROGRESS_ID>

# Or use simplified command (if available)
gh issue edit 5 --add-label "in-progress"
```

#### 2. **Create Feature Branch**

```bash
# Branch naming: feature/issue-NUMBER-short-description
git checkout develop
git pull origin develop
git checkout -b feature/issue-5-user-authentication
```

#### 3. **Implement Changes**

Work on the code, create files, make edits...

**Agent Checkpoint:** After significant logical unit of work (e.g., one file, one function, one component)

#### 4. **Update PROJECT_DOCUMENTATION.md**

**Agent Instructions:**
After EVERY significant change, update PROJECT_DOCUMENTATION.md:

```markdown
## Current Phase: Phase 2 - Laravel Foundation

### Tasks Completed (2026-01-26)
- ✅ 1.1: Design database schema for users table
- ✅ 1.2: Create migration for users table
- ✅ 2.1: Set up Laravel authentication scaffolding
- 🔄 2.2: Implement User model with relationships (IN PROGRESS)

### Recent Changes
#### 2026-01-26 15:30 - User Authentication Setup
- Created `app/Models/User.php` with Eloquent relationships
- Added `hasMany` relationship to Posts
- Implemented password hashing in model
- **Files Changed:** `app/Models/User.php`
- **Related Issue:** #5

### Next Steps
- [ ] Complete User model testing
- [ ] Create UserController for API endpoints
- [ ] Set up JWT authentication middleware
```

**Update Command:**
```powershell
# Agent: Read current docs/PROJECT_DOCUMENTATION.md
# Agent: Append new section with timestamp
# Agent: Mark issue as completed if done
```

#### 5. **Commit Changes (Conventional Commit)**

```bash
git add app/Models/User.php docs/PROJECT_DOCUMENTATION.md

git commit -m "feat: implement User model with relationships

- Add User Eloquent model with fillable fields
- Define hasMany relationship with Posts
- Implement password hashing in setPasswordAttribute
- Add email verification and remember token support

Closes #5"
```

**Commit Message Format:**
```
<type>: <short summary>

<detailed description>
- Bullet point changes
- More details

Closes #<issue-number>
```

**Types:**
- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation only
- `style:` - Code style (formatting, no logic change)
- `refactor:` - Code refactoring
- `test:` - Adding or updating tests
- `chore:` - Maintenance tasks (dependencies, config)

#### 6. **Push to Remote**

```bash
git push origin feature/issue-5-user-authentication
```

**Agent Note:** Push after EVERY commit (auto-push enabled)

#### 7. **Auto-Close Issue**

The commit message `Closes #5` will automatically close issue #5 when merged to main/develop.

**Manual close if needed:**
```bash
gh issue close 5 --comment "Implemented in feature/issue-5-user-authentication"
```

#### 8. **Move Issue on Project Board**

```bash
# Move to "Done" column
gh project item-edit --project-id $PROJECT_ID --id <ITEM_ID> --field-id <STATUS_FIELD_ID> --single-select-option-id <DONE_ID>

# Or use label-based automation
gh issue edit 5 --remove-label "in-progress" --add-label "done"
```

#### 9. **Merge Feature Branch (When Phase Complete)**

```bash
# Switch to develop
git checkout develop
git pull origin develop

# Merge feature branch
git merge --no-ff feature/issue-5-user-authentication -m "Merge feature/issue-5: User authentication

- Completes Phase 2 milestone
- All tests passing
- Documentation updated"

git push origin develop

# Delete feature branch
git branch -d feature/issue-5-user-authentication
git push origin --delete feature/issue-5-user-authentication
```

#### 10. **Phase Completion**

When all issues in a phase milestone are closed:

```bash
# Close milestone
gh api repos/maxymurm/project-name/milestones/<MILESTONE_NUMBER> -X PATCH -f state=closed

# Create release/tag
git tag -a v1.0-phase2 -m "Phase 2: Laravel Foundation - Complete"
git push origin v1.0-phase2

# Update PROJECT_DOCUMENTATION.md
# Agent: Mark Phase 2 as ✅ COMPLETE
# Agent: Move to Phase 3
# Agent: Create Phase 3 issues
```

---

## 🐙 GitHub Automation

### 🎯 Issue Scoping & Creation Workflow

**PRIMARY WORKFLOW:** This is the recommended way to create ALL issues.

#### Trigger Phrase

Whenever you need to create issues (for features, bugs, epics, or any work), say:

```
"Scope it out and create issues"
```

This triggers the **comprehensive automated workflow**:

1. **Agent asks clarifying questions** (comprehensive scoping)
2. **Agent presents detailed breakdown** (tasks, estimates, dependencies)
3. **You review and approve** (make changes if needed)
4. **Agent automatically:**
   - Creates planning documents (`docs/planning/[feature]-scope.md`)
   - Generates JSON templates (`docs/planning/[feature]-issues.json`)
   - Checks if project board exists
   - **Creates board if missing** (fully automated via GraphQL)
   - **Enables auto-add workflow** (all issues auto-sync)
   - Creates all GitHub issues with proper labels
   - Links dependencies
   - Commits planning docs
   - Reports completion

**Time:** < 10 minutes for comprehensive project scoping (vs 2-3 hours manually)

#### Label Enforcement (CRITICAL)

**EVERY issue MUST have at least 2 labels:**

1. **Phase Label** (required - pick ONE):
   - `phase-1` - Database Schema & Models
   - `phase-2` - Business Logic & Services
   - `phase-3` - API Development
   - `phase-4` - Admin Panel (Filament)
   - `phase-5` - Testing & QA
   - `phase-6` - Deployment & Documentation

2. **Type Label** (required - pick ONE or MORE):
   - `backend` - Server-side code
   - `frontend` - Client-side code
   - `database` - Database migrations, models
   - `api` - API endpoints, integration
   - `testing` - Tests, QA
   - `documentation` - Docs, guides, comments
   - `bug` - Bug fixes
   - `feature` - New features

3. **Additional Labels** (optional but recommended):
   - `enhancement` - Improvements
   - `epic` - Parent issue
   - `dependency` - Blocked/blocking
   - `security` - Security-related
   - `performance` - Performance improvements

**Agent validation:** If labels are missing or ambiguous, agent will ASK before creating issues.

#### Auto-Add to Project Board

**Automatic syncing:** ALL issues with proper labels automatically added to project board via GitHub's built-in workflow.

**Setup** (automated by agent):
- When board is created, agent configures auto-add workflow
- Filter: "Label is any of: *" (all labeled issues)
- Result: Zero manual board management

**If automation fails:**
Agent will STOP and ask you to:
1. Enable auto-add workflow manually (takes 30 seconds)
2. Say "Continue" to proceed with issue creation

#### Complete Documentation

See [ISSUE_SCOPING_WORKFLOW.md](./ISSUE_SCOPING_WORKFLOW.md) for:
- Comprehensive scoping question templates
- Example workflows (features, bugs, technical debt)
- Error handling procedures
- Integration with git workflow
- Best practices

#### Example Usage

**New Feature:**
```
You: "We need user authentication with 2FA. Scope it out and create issues."

Agent: [Asks 10 clarifying questions about auth methods, 2FA, sessions, etc.]

You: [Answers questions]

Agent: [Presents 18-task breakdown with estimates]

You: "Looks good, create them"

Agent: [Creates planning docs, board (if needed), 18 issues, commits, reports]

Result: 18 issues created in < 2 minutes, all synced to board
```

**Bug Report:**
```
You: "Login fails on Safari mobile. Scope it out and create issues."

Agent: [Asks quick questions about browser versions, error messages, impact]

You: [Provides details]

Agent: [Creates 4 issues: investigation, fix, testing, documentation]

Result: 4 issues created in < 1 minute
```

---

### Creating Issues Programmatically

**Batch Create from List:**

```powershell
# PowerShell script to create multiple issues
$issues = @(
    @{title="1.1: Design users table schema"; body="Design schema..."; estimate="2h"; labels="database,phase-1"},
    @{title="1.2: Create users migration"; body="Create migration..."; estimate="1h"; labels="database,phase-1"},
    @{title="1.3: Seed test users"; body="Create seeder..."; estimate="1h"; labels="database,phase-1"}
)

foreach ($issue in $issues) {
    gh issue create `
        --title $issue.title `
        --body "$($issue.body)`n`n**Estimate:** $($issue.estimate)" `
        --label $issue.labels `
        --milestone "Phase 1" `
        --assignee maxymurm `
        --project "project-name"
}
```

### Project Board Automation

**Get Project Details:**
```bash
gh project list --owner maxymurm
gh project view <PROJECT_NUMBER> --owner maxymurm
```

**Add Issues to Project:**
```bash
gh project item-add <PROJECT_NUMBER> --owner maxymurm --url "https://github.com/maxymurm/project-name/issues/5"
```

**Update Issue Status:**
```bash
# List fields
gh project field-list <PROJECT_NUMBER> --owner maxymurm

# Update status
gh project item-edit --project-id <PROJECT_NUMBER> --id <ITEM_ID> --field-id <STATUS_FIELD_ID> --single-select-option-id <OPTION_ID>
```

### Milestone Management

**Create Milestone:**
```bash
gh api repos/maxymurm/project-name/milestones \
  -f title="Phase 3: API Development" \
  -f description="Build RESTful API endpoints for all resources" \
  -f due_on="2026-02-28T23:59:59Z"
```

**List Milestones:**
```bash
gh api repos/maxymurm/project-name/milestones --jq '.[] | {number, title, open_issues, closed_issues}'
```

**Close Milestone:**
```bash
gh api repos/maxymurm/project-name/milestones/<NUMBER> -X PATCH -f state=closed
```

### Label Management

**Create Standard Labels:**
```bash
# Create labels for all projects
gh label create "phase-1" --color "0052CC" --description "Phase 1 tasks"
gh label create "phase-2" --color "0052CC" --description "Phase 2 tasks"
gh label create "phase-3" --color "0052CC" --description "Phase 3 tasks"
gh label create "enhancement" --color "84b6eb" --description "New feature"
gh label create "bug" --color "d73a4a" --description "Bug fix"
gh label create "documentation" --color "0075ca" --description "Documentation"
gh label create "in-progress" --color "fbca04" --description "Currently being worked on"
gh label create "blocked" --color "d93f0b" --description "Blocked by dependency"
gh label create "database" --color "5319e7" --description "Database related"
gh label create "api" --color "1d76db" --description "API related"
gh label create "frontend" --color "0e8a16" --description "Frontend related"
```

---

## 📚 Documentation Automation

### PROJECT_DOCUMENTATION.md Template

**Location:** `docs/PROJECT_DOCUMENTATION.md`

**Agent Instructions:** Update this file AFTER EVERY significant change

**Template Structure:**

```markdown
# Project Name - Development Documentation

**Last Updated:** 2026-01-26 15:45  
**Current Phase:** Phase 2 - Laravel Foundation  
**Status:** 🟢 Active Development

---

## 📊 Project Overview

### Description
[Brief project description - what it does, who it's for]

### Technology Stack
- **Backend:** Laravel 11 + PHP 8.3
- **Database:** PostgreSQL 15
- **Frontend:** Vue.js 3 + Tailwind CSS
- **Mobile:** Kotlin (Android), Swift (iOS)
- **Deployment:** Laravel Cloud
- **CI/CD:** GitHub Actions

### Repository
- **GitHub:** https://github.com/maxymurm/project-name
- **Project Board:** https://github.com/users/maxymurm/projects/5

---

## 🎯 Phase Breakdown

### Phase 1: Database Design & Migration ✅ COMPLETE
**Timeline:** Jan 3-7, 2026  
**Status:** Closed

**Tasks Completed:**
- ✅ 1.1: Design database schema (2h)
- ✅ 1.2: Create migrations for all tables (4h)
- ✅ 1.3: Seed test data (1h)
- ✅ 1.4: Validate relationships (2h)

**Deliverables:**
- Database schema documentation in `docs/database/`
- 15 migration files in `database/migrations/`
- Seeder files for test data

---

### Phase 2: Laravel Foundation Setup 🔄 IN PROGRESS
**Timeline:** Jan 8-14, 2026  
**Status:** 60% Complete (6/10 tasks done)

**Tasks Completed:**
- ✅ 2.1: Laravel 11 initialization (1h)
- ✅ 2.2: Configure database connection (0.5h)
- ✅ 2.3: Create Eloquent models (15 models) (3h)
- ✅ 2.4: Define model relationships (2h)
- ✅ 2.5: Set up authentication (1h)
- ✅ 2.6: Implement User model (1h) - Issue #5

**Tasks Pending:**
- [ ] 2.7: Create base controller (1h) - Issue #6
- [ ] 2.8: Set up API routing structure (1h) - Issue #7
- [ ] 2.9: Configure CORS middleware (0.5h) - Issue #8
- [ ] 2.10: Write unit tests for models (2h) - Issue #9

**Deliverables:**
- Fully configured Laravel application
- 15 Eloquent models with relationships
- Authentication scaffolding
- Base API structure

---

### Phase 3: API Development ⏳ UPCOMING
**Timeline:** Jan 15-28, 2026  
**Status:** Not started

**Planned Tasks:**
- [ ] 3.1: Create RESTful endpoints for Users
- [ ] 3.2: Create RESTful endpoints for Posts
- [ ] 3.3: Create RESTful endpoints for Comments
- [ ] 3.4: Implement pagination
- [ ] 3.5: Add API authentication (JWT)
- [ ] 3.6: Write API tests

---

## 📝 Change Log

### 2026-01-26

#### 15:45 - User Model Implementation (Issue #5)
**Type:** Feature  
**Branch:** feature/issue-5-user-authentication  
**Commit:** `feat: implement User model with relationships`

**Changes:**
- Created `app/Models/User.php` with Eloquent model
- Added `hasMany` relationship with Posts
- Added `hasMany` relationship with Comments
- Implemented password hashing in `setPasswordAttribute`
- Added email verification and remember token fields

**Files Modified:**
- `app/Models/User.php` (new file, 85 lines)
- `docs/PROJECT_DOCUMENTATION.md` (updated)

**Testing:**
- Manual testing: ✅ Pass
- Unit tests: Pending (Issue #9)

---

#### 14:20 - Authentication Setup (Issue #4)
**Type:** Feature  
**Branch:** feature/issue-4-auth-setup  
**Commit:** `feat: set up Laravel authentication scaffolding`

**Changes:**
- Installed Laravel Sanctum for API authentication
- Configured auth.php with guards and providers
- Created authentication routes in `routes/api.php`

**Files Modified:**
- `config/auth.php`
- `routes/api.php`
- `app/Http/Controllers/Auth/` (5 new controllers)

---

### 2026-01-25

#### 17:00 - Eloquent Models Created (Issue #3)
**Type:** Feature  
**Commit:** `feat: create Eloquent models for all database tables`

**Changes:**
- Created 15 Eloquent models in `app/Models/`
- Defined fillable fields for mass assignment
- Added casts for date fields and JSON columns

**Models Created:**
- User, Post, Comment, Category, Tag
- Product, Order, OrderItem, Cart, CartItem
- Payment, Transaction, Notification, Settings, Log

---

## 🏗️ Architecture Decisions

### Database Design
- **ORM:** Eloquent (Laravel's built-in ORM)
- **Relationships:** Properly normalized with foreign keys
- **Timestamps:** All tables include created_at and updated_at
- **Soft Deletes:** Enabled on User, Post, Product tables

### API Design
- **Style:** RESTful
- **Authentication:** JWT via Laravel Sanctum
- **Versioning:** URL-based (e.g., /api/v1/)
- **Response Format:** JSON with consistent structure
  ```json
  {
    "success": true,
    "data": {},
    "message": "Success message",
    "errors": []
  }
  ```

### Code Organization
- **Controllers:** Resource controllers for RESTful routes
- **Repositories:** Repository pattern for data access (optional)
- **Services:** Business logic in service classes
- **Requests:** Form Request validation classes
- **Resources:** API Resource transformers

---

## 🔧 Setup Instructions

### Prerequisites
- PHP 8.3+
- Composer 2.x
- PostgreSQL 15+
- Node.js 18+ (for frontend)

### Local Development Setup

1. **Clone repository:**
   ```bash
   git clone https://github.com/maxymurm/project-name.git
   cd project-name
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database in .env:**
   ```
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=project_name
   DB_USERNAME=postgres
   DB_PASSWORD=password
   ```

5. **Run migrations and seeders:**
   ```bash
   php artisan migrate --seed
   ```

6. **Start development server:**
   ```bash
   php artisan serve
   npm run dev
   ```

7. **Access application:**
   - Backend: http://localhost:8000
   - API: http://localhost:8000/api
   - Frontend: http://localhost:3000

---

## 📱 API Documentation

### Base URL
```
http://localhost:8000/api/v1
```

### Authentication
All protected endpoints require Bearer token:
```
Authorization: Bearer {token}
```

### Endpoints

#### Users
- `GET /users` - List all users (paginated)
- `GET /users/{id}` - Get user by ID
- `POST /users` - Create new user
- `PUT /users/{id}` - Update user
- `DELETE /users/{id}` - Delete user (soft delete)

#### Posts
- `GET /posts` - List all posts (paginated)
- `GET /posts/{id}` - Get post by ID
- `POST /posts` - Create new post
- `PUT /posts/{id}` - Update post
- `DELETE /posts/{id}` - Delete post

[See full API documentation in `docs/api/README.md`]

---

## 🧪 Testing

### Run Tests
```bash
# All tests
php artisan test

# Specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# With coverage
php artisan test --coverage
```

### Test Coverage
- **Current Coverage:** 45% (Target: 80%)
- **Unit Tests:** 12 tests, 12 passed
- **Feature Tests:** 8 tests, 8 passed

---

## 🚀 Deployment

### Production Deployment (Laravel Cloud)
```bash
# Deploy to production
php artisan cloud:deploy production

# Run migrations
php artisan cloud:migrate production

# Check deployment status
php artisan cloud:status
```

### Environment Variables (Production)
Ensure these are set in Laravel Cloud dashboard:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://project-name.com`
- Database credentials
- API keys

---

## 📋 Next Steps

### Immediate (This Week)
1. Complete User model testing (Issue #9)
2. Create base controller structure (Issue #6)
3. Set up API routing (Issue #7)
4. Configure CORS middleware (Issue #8)

### Short Term (Next 2 Weeks)
1. Complete Phase 2 milestone
2. Begin Phase 3: API Development
3. Create comprehensive API tests
4. Document all API endpoints

### Long Term (Next Month)
1. Complete API development (Phase 3)
2. Begin frontend implementation (Phase 4)
3. Mobile app development (Phase 5)
4. QA and testing (Phase 6)
5. Production deployment (Phase 7)

---

## 🐛 Known Issues

### Current Bugs
- None reported

### Technical Debt
- [ ] Add repository pattern for data access
- [ ] Implement caching layer (Redis)
- [ ] Add request rate limiting
- [ ] Set up queue workers for async tasks

---

## 👥 Team & Contacts

**Developer:** Maxwell Murunga  
**Email:** maxmm@adventit.digital  
**GitHub:** @maxymurm  
**Company:** Advent Digital

---

**End of Documentation**  
*Auto-generated and maintained by AI agents*  
*Template version: 2.0*
```

### Updating PROJECT_DOCUMENTATION.md

**Agent Instructions:**

1. **Read current file** before making changes
2. **Update sections** that changed:
   - Current Phase status
   - Tasks Completed list
   - Change Log (add new entry at top)
   - Next Steps
3. **Add timestamp** to "Last Updated" field
4. **Commit with docs:** commit message

**Update Frequency:**
- After EVERY file creation or significant edit
- After closing an issue
- After completing a task
- At end of each work session

---

## 📦 Phase Management

### Understanding Phases

**Definition:** A phase is a logical grouping of related tasks that accomplish a specific project milestone.

**Characteristics:**
- **Timeboxed:** Each phase has estimated duration (days/weeks)
- **Milestone-linked:** Each phase = 1 GitHub milestone
- **Sequential or Parallel:** Phases can depend on each other or run concurrently
- **Deliverable-focused:** Each phase produces tangible deliverables

### Phase Lifecycle

```
1. PLANNING → 2. IN PROGRESS → 3. REVIEW → 4. COMPLETE
```

### Creating Phases for New Projects

**Agent Checklist:**

1. **Analyze project scope**
   - What needs to be built?
   - What are major components?
   - What are logical groupings?

2. **Define 4-8 phases** (typical)
   - Phase 1: Usually database/backend foundation
   - Phase 2: Core business logic
   - Phase 3: API/Integration
   - Phase 4: UI/Frontend
   - Phase 5: Testing/QA
   - Phase 6: Deployment/Go-Live
   - Phase 7+: Additional features, maintenance

3. **Create GitHub milestone** for each phase
   ```bash
   gh api repos/maxymurm/project-name/milestones \
     -f title="Phase 1: Database Design" \
     -f description="Design and implement database schema" \
     -f due_on="2026-02-07T23:59:59Z"
   ```

4. **Break down Phase 1** into detailed issues (10-20 issues typical)

5. **Wait to break down Phase 2+** until Phase 1 nears completion

### Retrospective Phases (Existing Projects)

**When:** Adopting this workflow mid-project

**Agent Instructions:**

1. **Analyze git history** to understand what's been done
   ```bash
   git log --oneline --all --graph
   ```

2. **Identify completed work** (features, components, modules)

3. **Group into logical phases**
   - Example: "Phase 1: Initial Setup" for commits 1-50
   - Example: "Phase 2: User Authentication" for commits 51-100

4. **Create closed milestones** for completed phases
   ```bash
   gh api repos/maxymurm/project-name/milestones \
     -f title="Phase 1: Initial Setup (COMPLETED)" \
     -f state=closed \
     -f description="Retrospectively created for completed work"
   ```

5. **Create closed issues** for completed features
   ```bash
   gh issue create \
     --title "1.1: Project initialization (COMPLETED)" \
     --body "Retrospective issue for completed work" \
     --milestone "Phase 1" \
     --label "completed,retrospective" \
     --assignee maxymurm
   
   # Close immediately
   gh issue close 1 --comment "Completed before workflow adoption"
   ```

6. **Document in PROJECT_DOCUMENTATION.md**
   - Mark phases as ✅ COMPLETE
   - Add change log entries (approximate dates)
   - Note: "Retrospectively documented"

7. **Create current phase** for ongoing work

8. **Create future phases** for planned work

---

## 💼 Client Project Workflows

### Client Project Structure

```
project-name/
├── .github/
├── app/
├── database/
├── docs/
│   ├── PROJECT_DOCUMENTATION.md
│   ├── architecture/
│   ├── api/
│   ├── guides/
│   └── client/
│       ├── MEETING_NOTES_2026-01-15.md
│       ├── MEETING_NOTES_2026-01-22.md
│       ├── QUOTE_ADV-PS-001.html
│       ├── INVOICE_ADV-PS-002.html
│       ├── PROJECT_PROPOSAL.md
│       └── STATUS_REPORT_WEEKLY.md
```

### Client Meeting Notes Template

**Location:** `docs/client/MEETING_NOTES_YYYY-MM-DD.md`

```markdown
# Client Meeting Notes

**Date:** 2026-01-26  
**Time:** 14:00 - 15:30 EAT  
**Client:** Pinsoft Solutions Limited  
**Attendees:**
- Matthews C Rutto (Client - CEO)
- Maxwell Murunga (Advent Digital - Developer)

**Meeting Type:** Progress Review

---

## Agenda

1. Phase 3 completion review
2. Phase 4 scope discussion
3. Timeline and budget review
4. Next steps and action items

---

## Discussion Summary

### Phase 3 Review
- Demonstrated 59 CRUD resources
- Client satisfied with dashboard and UI
- Production deployment successful on Laravel Cloud

### Phase 4 Scope
- Extract PowerBuilder business logic
- Implement premium calculation engine
- Build claims adjudication workflows
- Estimated: 6 weeks, $16,000

### Budget Discussion
- Phase 1-3 completed: $12,800 (payment pending)
- Phase 4-6 quoted: $30,000
- Payment terms: Milestone-based (30/40/20/10)

---

## Decisions Made

1. ✅ Proceed with Phase 4-6 as outlined in quote
2. ✅ Payment schedule: 30% upfront, 40% on Phase 4 completion
3. ✅ Mobile apps to be scoped separately after Phase 6
4. ⏸️ Additional features (SMS notifications) deferred to Phase 7

---

## Action Items

### Client (Matthews)
- [ ] Review and approve Quote ADV-PS-003 by Jan 28
- [ ] Make 30% deposit ($9,000) to kickoff Phase 4
- [ ] Provide PowerBuilder SRD export by Feb 1

### Advent Digital (Maxwell)
- [ ] Send signed quote to client by Jan 27
- [ ] Prepare Phase 4 detailed project plan
- [ ] Set up weekly status report schedule

---

## Next Meeting

**Date:** February 5, 2026  
**Time:** 14:00 EAT  
**Agenda:**
- Phase 4 kickoff
- Requirements review
- Technical deep-dive on business logic

---

## Notes

- Client very pleased with progress so far
- Emphasized importance of timeline (insurance companies waiting)
- Interested in mobile apps but wants to see Phase 4-6 first
- Suggested we present to insurance partners in March

---

**Meeting recorded:** Yes (with permission)  
**Recording location:** Google Drive /Clients/Pinsoft/Meetings/
```

### Creating Meeting Notes

**Agent Instructions:**

```bash
# After user says "I just had a meeting with client"

# 1. Create meeting notes file
DATE=$(date +%Y-%m-%d)
FILE="docs/client/MEETING_NOTES_$DATE.md"
cp ~/.config/agents/CLIENT_MEETING_TEMPLATE.md $FILE

# 2. Agent fills in template with details provided by user

# 3. Commit
git add $FILE
git commit -m "docs: add client meeting notes for $DATE

- Discussion: Phase 4 scope and budget
- Decisions: Proceed with Phase 4-6
- Action items documented"

git push
```

### Quote Generation Template

**Location:** `docs/client/QUOTE_ADV-PS-XXX.html`

(Use the template from the Pinsoft project - same blue color scheme, professional layout)

**Agent Instructions:**
1. Copy template from `~/.config/agents/QUOTE_TEMPLATE.html`
2. Fill in project-specific details
3. Save as `QUOTE_ADV-PS-{number}.html`
4. Generate PDF for client
5. Link in PROJECT_DOCUMENTATION.md under "Client Documentation"

---

## 🔄 Existing Project Adoption

### Scenario: You're joining a project mid-development

**Goal:** Retroactively document everything and establish workflow

### Step 1: Assess Project State

```bash
# Clone repo
git clone https://github.com/maxymurm/existing-project.git
cd existing-project

# Check git history
git log --oneline --all --graph --decorate

# Count commits
git rev-list --count HEAD

# Check branches
git branch -a

# Check existing issues/PRs
gh issue list --state all
gh pr list --state all
```

### Step 2: Analyze Commit History

**Agent Instructions:**

Parse commit history to identify:
1. **Major features** (groups of related commits)
2. **Time periods** (clusters of commits)
3. **Contributors** (who did what)
4. **File changes** (what was modified)

```bash
# Get detailed commit log
git log --all --pretty=format:"%h|%ai|%an|%s" > commits.txt

# Analyze file changes
git log --all --name-only --pretty=format: | sort -u > all-files-changed.txt
```

### Step 3: Create Retrospective Phases

**Example Analysis:**

Commits 1-45 (Jan 1-7, 2026):
- Database migrations
- Initial Laravel setup
- Models created
→ **Phase 1: Foundation Setup** ✅ COMPLETE

Commits 46-120 (Jan 8-20, 2026):
- Controllers created
- API endpoints
- Authentication
→ **Phase 2: API Development** ✅ COMPLETE

Commits 121-present (Jan 21-26, 2026):
- Frontend components
- Dashboard
- UI polish
→ **Phase 3: Frontend** 🔄 IN PROGRESS

**Create Milestones:**

```bash
# Phase 1 (closed)
gh api repos/maxymurm/existing-project/milestones \
  -f title="Phase 1: Foundation (RETRO)" \
  -f description="Retrospectively created. Completed Jan 1-7, 2026." \
  -f state=closed

# Phase 2 (closed)
gh api repos/maxymurm/existing-project/milestones \
  -f title="Phase 2: API Development (RETRO)" \
  -f description="Retrospectively created. Completed Jan 8-20, 2026." \
  -f state=closed

# Phase 3 (open - current)
gh api repos/maxymurm/existing-project/milestones \
  -f title="Phase 3: Frontend" \
  -f description="Currently in progress" \
  -f due_on="2026-02-15T23:59:59Z"
```

### Step 4: Create Retrospective Issues

**Agent Instructions:**

For each closed phase, create closed issues representing completed work:

```bash
# Phase 1 issues (closed)
gh issue create \
  --title "1.1: Database schema design (COMPLETED)" \
  --body "**Retrospective Issue**
  
Completed during initial project setup (Jan 1-3, 2026).

**Work Done:**
- Designed database schema
- Created migration files
- Documented in /docs/database/

**Related Commits:** #abc123, #def456" \
  --milestone "Phase 1: Foundation (RETRO)" \
  --label "completed,retrospective,database" \
  --assignee maxymurm

# Close immediately
gh issue close 1 --comment "Completed before workflow adoption on 2026-01-26"

# Repeat for all major features...
```

### Step 5: Create PROJECT_DOCUMENTATION.md

**Agent Instructions:**

1. Create `docs/PROJECT_DOCUMENTATION.md` from template
2. Fill in retrospective phases (mark as ✅ COMPLETE)
3. Fill in current phase (mark as 🔄 IN PROGRESS)
4. Add change log entries (approximate dates from git history)
5. Document architecture decisions (infer from code)
6. Add setup instructions (from README or infer)

### Step 6: Create Project Board

```bash
# Create project
gh project create --title "existing-project" --owner maxymurm

# Add all issues (including retrospective)
PROJECT_ID=$(gh project list --owner maxymurm --format json | jq -r '.projects[] | select(.title=="existing-project") | .number')

gh issue list --state all --json number --jq '.[].number' | while read issue_num; do
  gh project item-add $PROJECT_ID --owner maxymurm --url "https://github.com/maxymurm/existing-project/issues/$issue_num"
done
```

### Step 7: Set Up Automation Going Forward

From this point, follow the Daily Development Workflow for all new work.

**Commit to establish workflow:**

```bash
git add docs/PROJECT_DOCUMENTATION.md .github/
git commit -m "docs: establish AI agent workflow and retrospective documentation

- Create PROJECT_DOCUMENTATION.md with full project history
- Document Phases 1-2 (completed retrospectively)
- Set up Phase 3 as current work
- Add GitHub issue templates
- Configure automation for future work

This commit establishes the AI agent automation workflow for this project."

git push
```

---

## 📚 Templates & Examples

### Issue Templates

#### Bug Report Template

**File:** `.github/ISSUE_TEMPLATE/bug_report.md`

```markdown
---
name: Bug Report
about: Report a bug or unexpected behavior
title: '[BUG] '
labels: bug
assignees: maxymurm
---

## 🐛 Bug Description
A clear and concise description of what the bug is.

## 📋 Steps to Reproduce
1. Go to '...'
2. Click on '...'
3. Scroll down to '...'
4. See error

## ✅ Expected Behavior
A clear description of what you expected to happen.

## ❌ Actual Behavior
A clear description of what actually happened.

## 📸 Screenshots
If applicable, add screenshots to help explain the problem.

## 💻 Environment
- **OS:** [e.g., Windows 11, macOS 14]
- **Browser:** [e.g., Chrome 120, Safari 17]
- **PHP Version:** [e.g., 8.3]
- **Laravel Version:** [e.g., 11.0]

## 📝 Additional Context
Add any other context about the problem here.

## 🔍 Possible Solution
(Optional) Suggest a fix or reason for the bug.
```

#### Feature Request Template

**File:** `.github/ISSUE_TEMPLATE/feature_request.md`

```markdown
---
name: Feature Request
about: Suggest a new feature or enhancement
title: '[FEATURE] '
labels: enhancement
assignees: maxymurm
---

## 💡 Feature Description
A clear and concise description of the feature you'd like to see.

## 🎯 Problem Statement
Describe the problem this feature would solve. Why is this feature needed?

## ✨ Proposed Solution
Describe how you envision this feature working.

## 🔄 Alternatives Considered
Describe any alternative solutions or features you've considered.

## 📊 Acceptance Criteria
- [ ] Criterion 1
- [ ] Criterion 2
- [ ] Criterion 3

## ⏱️ Estimate
Estimated time to implement: [e.g., 4 hours, 1 day, 1 week]

## 🔗 Dependencies
List any issues or features this depends on.

## 📝 Additional Context
Add any other context, mockups, or screenshots about the feature.
```

### Pull Request Template

**File:** `.github/pull_request_template.md`

```markdown
## 📝 Description
Brief description of what this PR does.

## 🔗 Related Issue
Closes #(issue number)

## 🎯 Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update
- [ ] Refactoring (no functional changes)

## 🧪 Testing
- [ ] Unit tests pass
- [ ] Feature tests pass
- [ ] Manual testing completed
- [ ] No new warnings or errors

## ✅ Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Comments added for complex code
- [ ] Documentation updated
- [ ] No merge conflicts
- [ ] Commit messages follow conventional commits

## 📸 Screenshots (if applicable)
Add screenshots to demonstrate changes.

## 📝 Additional Notes
Any additional information reviewers should know.
```

---

## 🔧 Troubleshooting

### Common Issues

#### GitHub CLI Not Authenticated

**Symptom:** `gh auth status` shows not authenticated

**Solution:**
```bash
gh auth login
# Follow prompts, use classic token with all scopes
```

#### Can't Create Project Board

**Symptom:** `gh project create` fails

**Solution:**
```bash
# Check if projects feature is enabled
gh api user --jq '.has_projects'

# Use web UI to create project, then manage via CLI
```

#### Issues Not Closing Automatically

**Symptom:** Commit with `Closes #5` doesn't close issue

**Solution:**
- Ensure commit is merged to default branch (main)
- Use full issue reference: `Closes #5` or `Fixes #5`
- Keywords: `close`, `closes`, `closed`, `fix`, `fixes`, `fixed`, `resolve`, `resolves`, `resolved`

#### PROJECT_DOCUMENTATION.md Getting Out of Sync

**Symptom:** Documentation doesn't reflect current state

**Solution:**
- **Agent:** Read file before every update
- **Agent:** Always update after ANY code change
- **Agent:** Include docs in every commit

#### Merge Conflicts in PROJECT_DOCUMENTATION.md

**Symptom:** Conflicts when merging feature branches

**Solution:**
```bash
# Always update docs on develop branch first
git checkout develop
git pull origin develop

# Then merge feature branch
git merge feature/issue-5

# Resolve conflicts manually (keep both changes)
# Re-commit
git commit -m "chore: merge feature/issue-5 and resolve docs conflicts"
```

#### Git Push Fails (Large Files)

**Symptom:** Push rejected due to large files

**Solution:**
```bash
# Add to .gitignore
echo "*.log" >> .gitignore
echo "node_modules/" >> .gitignore
echo "vendor/" >> .gitignore

# Remove from git cache
git rm --cached -r node_modules/
git commit -m "chore: remove node_modules from git"
```

---

## ✅ Quick Reference Checklists

### New Project Checklist

```
PROJECT INITIALIZATION
□ Create project directory
□ Initialize git repository
□ Create GitHub repository (gh repo create)
□ Add .gitignore, README, LICENSE
□ Create docs/ directory structure
□ Copy PROJECT_DOCUMENTATION.md template
□ Create .github/ISSUE_TEMPLATE/ files
□ Define project phases (4-8 phases)
□ Create GitHub milestones for each phase
□ Create GitHub project board
□ Add standard labels
□ Create Phase 1 issues (10-20 issues)
□ Initial commit and push
□ Create develop branch
□ Create first feature branch
```

### Daily Development Checklist

```
STARTING WORK
□ Pull latest from develop
□ Pick issue from Todo column
□ Move issue to "In Progress"
□ Create/checkout feature branch (feature/issue-N-name)
□ Start implementation

DURING WORK
□ Make code changes
□ Update PROJECT_DOCUMENTATION.md
□ Commit with conventional commit message
□ Include "Closes #N" in commit
□ Push to feature branch
□ Repeat for logical checkpoints

COMPLETING TASK
□ Final commit and push
□ Move issue to "Done"
□ Merge feature branch to develop
□ Delete feature branch
□ Verify issue closed automatically
□ Check project board updated
```

### Phase Completion Checklist

```
PHASE COMPLETION
□ All phase issues closed
□ All tests passing
□ Documentation updated (PROJECT_DOCUMENTATION.md)
□ Close milestone
□ Create git tag (v1.0-phaseN)
□ Push tag to GitHub
□ Update PROJECT_DOCUMENTATION.md (mark phase complete)
□ Create issues for next phase
□ Update project board
□ (Optional) Create release notes
□ (Optional) Deploy to staging
```

### Existing Project Adoption Checklist

```
ADOPTING WORKFLOW MID-PROJECT
□ Clone repository
□ Analyze git history (commits, branches, contributors)
□ Identify completed work (group into phases)
□ Create retrospective milestones (closed)
□ Create retrospective issues (closed)
□ Create PROJECT_DOCUMENTATION.md with history
□ Create GitHub project board
□ Add all issues to board (including retrospective)
□ Set up current phase (in progress)
□ Create issues for current work
□ Plan future phases
□ Commit documentation setup
□ Begin daily development workflow
```

### Client Meeting Checklist

```
BEFORE MEETING
□ Review PROJECT_DOCUMENTATION.md
□ Check completed vs pending issues
□ Prepare demo (if applicable)
□ Review previous meeting notes
□ Prepare agenda

DURING MEETING
□ Take detailed notes
□ Record decisions
□ Capture action items (client and your team)
□ Discuss timeline and budget
□ Schedule next meeting

AFTER MEETING
□ Create MEETING_NOTES_YYYY-MM-DD.md
□ Update PROJECT_DOCUMENTATION.md if scope changed
□ Create new issues for action items
□ Send meeting summary to client
□ Update project timeline if needed
□ (If needed) Create/update quote
```

---

## 🎓 Agent Best Practices

### Communication

1. **Be Transparent:**
   - Explain what you're doing before running commands
   - Show command output if relevant
   - Report any errors immediately

2. **Confirm Actions:**
   - "I'll create 15 issues for Phase 1. Proceed?"
   - "Ready to commit and push. Confirm?"

3. **Provide Context:**
   - "Creating feature branch feature/issue-5-user-auth..."
   - "Updating PROJECT_DOCUMENTATION.md with User model changes..."

### Workflow Efficiency

1. **Batch Operations:**
   - Create multiple issues at once (script)
   - Don't commit after EVERY single line change
   - Commit at logical checkpoints (file complete, function working, feature done)

2. **Read Before Write:**
   - Always read PROJECT_DOCUMENTATION.md before updating
   - Check if issue already exists before creating
   - Verify milestone exists before assigning

3. **Error Recovery:**
   - If command fails, read error message
   - Try alternative approach
   - Ask user if unsure

### Code Quality

1. **Test Before Commit:**
   - Run tests if available
   - Manually verify changes work
   - Check for syntax errors

2. **Clean Commits:**
   - One logical change per commit
   - Descriptive commit messages
   - Include issue reference

3. **Documentation Sync:**
   - Update docs in same commit as code
   - Keep PROJECT_DOCUMENTATION.md current
   - Add code comments for complex logic

---

## 📖 Additional Resources

### Documentation Standards
- [Conventional Commits](https://www.conventionalcommits.org/)
- [Semantic Versioning](https://semver.org/)
- [Keep a Changelog](https://keepachangelog.com/)

### GitHub CLI Documentation
- [GitHub CLI Manual](https://cli.github.com/manual/)
- [GitHub API Reference](https://docs.github.com/en/rest)
- [GitHub Projects](https://docs.github.com/en/issues/planning-and-tracking-with-projects)

### Laravel Resources
- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)
- [Laracasts](https://laracasts.com/)

---

## 🔄 Version History

### Version 2.0 (2026-01-26)
- Initial comprehensive documentation
- Added existing project adoption workflow
- Added client project templates
- Added retrospective phase creation
- Added PowerShell commands for Windows

### Version 1.0 (2026-01-20)
- Basic workflow documentation
- GitHub CLI integration
- PROJECT_DOCUMENTATION.md template

---

## 📞 Support

**Issues with this workflow?**
- Open issue: https://github.com/maxymurm/agent-automation/issues
- Email: maxmm@adventit.digital

**Want to contribute improvements?**
- Fork the template repository
- Submit pull request
- Follow CONTRIBUTING.md

---

**End of AGENTS.md**  
*This document should be deployed to:*
- **Global:** `~/.config/agents/AGENTS.md`
- **Project:** `docs/AGENTS.md` (copy per project, customize as needed)

*Created by: Maxwell Murunga*  
*For use with: GitHub Copilot, Claude, ChatGPT, and other AI coding assistants*
