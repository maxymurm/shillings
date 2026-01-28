# 📦 Project Starter Kit - README

**Version:** 1.0  
**Last Updated:** January 27, 2026  
**Purpose:** Complete automation for new project setup with AI agent integration

---

## 🎯 What Is This?

This is your **Project Starter Kit** - a collection of files that completely automates new project setup. Copy these files to a new project directory, run ONE command, and you have:

✅ Git repository (local + GitHub)  
✅ Documentation structure  
✅ Memory system (project + global)  
✅ GitHub issues, milestones, project board  
✅ All automation configured  
✅ Ready to code!

---

## 📂 Starter Kit Contents

### Required Files (Copy These)

```
PROJECT_STARTER_KIT/
├── docs/
│   ├── AGENTS.md                          ⭐ Agent automation guide
│   ├── GLOBAL_MEMORY_TEMPLATE.md          ⭐ Global preferences template
│   ├── PROJECT_DOCUMENTATION_TEMPLATE.md  ⭐ Project docs template
│   ├── MEMORY_TEMPLATE.md                 ⭐ Project memory template
│   ├── PROJECT_INITIALIZATION.md          ⭐ Setup guide (becomes issues!)
│   └── PROJECT_STARTER_KIT_README.md      📖 This file
├── SETUP_PROJECT.ps1                      🚀 ONE-COMMAND setup script
└── .github/
    └── ISSUE_TEMPLATE/
        ├── bug_report.md                  🐛 Bug report template
        ├── feature_request.md             ✨ Feature request template
        └── pull_request_template.md       🔄 PR template
```

---

## 🚀 Three Ways to Use This Kit

### Option 1: Fully Automated (Recommended) ⚡

**One command, everything ready!**

```powershell
# 1. Copy starter kit to new project directory
Copy-Item -Recurse "path\to\starter-kit\*" "C:\Users\maxmm\projects\my-new-project\"
cd C:\Users\maxmm\projects\my-new-project

# 2. Run setup script
.\SETUP_PROJECT.ps1 `
  -ProjectName "my-new-project" `
  -TechStack "laravel" `
  -Database "postgresql" `
  -IsPrivate `
  -ClientProject `
  -ClientName "Acme Corp"

# 3. Done! Project is fully configured with issues on project board
```

**Script does EVERYTHING:**
- ✅ Initializes git repo
- ✅ Creates GitHub repo
- ✅ Sets up documentation
- ✅ Creates memory files
- ✅ Deploys templates
- ✅ Creates milestones
- ✅ Creates project board
- ✅ Generates setup issues (showing what was done)
- ✅ Prompts for Phase 1 breakdown
- ✅ Commits and pushes everything

**Time:** 5-10 minutes

---

### Option 2: Agent-Assisted 🤖

**Let the AI agent do the work:**

```powershell
# 1. Copy starter kit
Copy-Item -Recurse "path\to\starter-kit\*" "C:\Users\maxmm\projects\my-new-project\"
cd C:\Users\maxmm\projects\my-new-project

# 2. Say to agent:
"Initialize a new Laravel project called 'my-new-project' following PROJECT_INITIALIZATION.md. 
This is a client project for Acme Corp, using PostgreSQL database."

# Agent will:
# - Read PROJECT_INITIALIZATION.md
# - Execute all 23 setup steps
# - Create issues on GitHub
# - Set up project board
# - Ask you to define phases
# - Create Phase 1 issues
```

**Time:** 20-30 minutes (with agent interaction)

---

### Option 3: Manual Setup 📝

**Follow PROJECT_INITIALIZATION.md step-by-step**

Not recommended unless you want to learn the process. Takes 2-3 hours.

---

## 🔧 One-Time Global Setup (First Time Only)

**Before using the starter kit for ANY project, run once:**

```powershell
# 1. Install GitHub CLI
winget install --id GitHub.cli

# 2. Authenticate GitHub CLI
gh auth login
# Choose: GitHub.com → HTTPS → Token
# Create classic token: https://github.com/settings/tokens (enable all permissions)

# 3. Deploy global templates
mkdir "$env:USERPROFILE\.config\agents" -Force

# From your first project that has the starter kit:
Copy-Item "docs\GLOBAL_MEMORY_TEMPLATE.md" "$env:USERPROFILE\.config\agents\GLOBAL_MEMORY.md"
Copy-Item "docs\AGENTS.md" "$env:USERPROFILE\.config\agents\AGENTS.md"
Copy-Item "docs\PROJECT_DOCUMENTATION_TEMPLATE.md" "$env:USERPROFILE\.config\agents\PROJECT_DOCUMENTATION_TEMPLATE.md"
Copy-Item "docs\MEMORY_TEMPLATE.md" "$env:USERPROFILE\.config\agents\MEMORY_TEMPLATE.md"

# 4. Edit GLOBAL_MEMORY.md with YOUR preferences
code "$env:USERPROFILE\.config\agents\GLOBAL_MEMORY.md"
# Update: location, timezone, tech preferences, coding style
```

**Verify Global Setup:**
```powershell
gh --version
gh auth status
Test-Path "$env:USERPROFILE\.config\agents\GLOBAL_MEMORY.md"
```

---

## 📋 Script Parameters

### SETUP_PROJECT.ps1 Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `-ProjectName` | String | ✅ Yes | Project name (will be repo name) |
| `-TechStack` | String | ✅ Yes | `laravel`, `kotlin`, `swift`, `nextjs`, `other` |
| `-Database` | String | ✅ Yes | `postgresql`, `mysql`, `sqlite`, `none` |
| `-IsPrivate` | Switch | ❌ No | Create private repo (default: public) |
| `-ClientProject` | Switch | ❌ No | Is this a client project? |
| `-ClientName` | String | ❌ No | Client name (if -ClientProject) |
| `-ProjectPath` | String | ❌ No | Custom path (default: current directory) |
| `-SkipPhaseBreakdown` | Switch | ❌ No | Skip Phase 1 breakdown (do manually later) |

### Examples

**Laravel Client Project:**
```powershell
.\SETUP_PROJECT.ps1 `
  -ProjectName "acme-insurance-portal" `
  -TechStack "laravel" `
  -Database "postgresql" `
  -IsPrivate `
  -ClientProject `
  -ClientName "Acme Insurance"
```

**Kotlin Mobile App:**
```powershell
.\SETUP_PROJECT.ps1 `
  -ProjectName "health-tracker-android" `
  -TechStack "kotlin" `
  -Database "sqlite" `
  -IsPrivate
```

**Swift Mobile App:**
```powershell
.\SETUP_PROJECT.ps1 `
  -ProjectName "health-tracker-ios" `
  -TechStack "swift" `
  -Database "none" `
  -IsPrivate
```

**Quick Setup (Public, No Client):**
```powershell
.\SETUP_PROJECT.ps1 -ProjectName "my-app" -TechStack "other" -Database "none"
```

---

## 🎓 What Gets Created?

### Project Structure After Setup

```
my-new-project/
├── .git/                                   ✅ Git repository
├── .github/
│   ├── instructions/
│   │   └── memory.instruction.md          ✅ Project memory (customized)
│   ├── ISSUE_TEMPLATE/
│   │   ├── bug_report.md                  ✅ Bug template
│   │   ├── feature_request.md             ✅ Feature template
│   │   └── pull_request_template.md       ✅ PR template
├── docs/
│   ├── PROJECT_DOCUMENTATION.md           ✅ Customized docs
│   ├── AGENTS.md                          ✅ Reference guide
│   ├── MEMORY_TEMPLATE.md                 ✅ For reference
│   ├── PROJECT_INITIALIZATION.md          ✅ Setup history
│   ├── architecture/
│   │   └── README.md                      ✅ Placeholder
│   ├── api/
│   │   └── README.md                      ✅ Placeholder
│   ├── guides/
│   │   └── setup.md                       ✅ Setup guide
│   └── client/                            ✅ If client project
├── .gitignore                             ✅ Comprehensive
├── README.md                              ✅ Customized
├── LICENSE                                ✅ MIT license
└── [Your source code goes here]
```

### GitHub Setup After Setup

**Repository:**
- ✅ Created on GitHub (public or private)
- ✅ main branch (protected)
- ✅ develop branch
- ✅ origin remote configured

**Project Board:**
- ✅ Kanban board created
- ✅ Columns: Backlog, Todo, In Progress, Done
- ✅ Setup issues in "Done" (showing what was automated)

**Milestones:**
- ✅ Phase 0: Setup (closed)
- ✅ Phase 1-N: Defined (open)

**Labels:**
- ✅ phase-1, phase-2, ... phase-N
- ✅ enhancement, bug, documentation, refactor, test
- ✅ blocked, needs-review, in-progress
- ✅ database, api, frontend, backend, mobile (as applicable)

**Issues:**
- ✅ 23 setup issues (closed, showing automation history)
- ✅ 10-20 Phase 1 issues (ready to work on)

---

## 🔄 Typical Workflow After Setup

### Day 1: Project Setup (5-10 minutes)
```powershell
# Run setup script
.\SETUP_PROJECT.ps1 -ProjectName "my-app" -TechStack "laravel" -Database "postgresql" -IsPrivate

# Review project board
gh browse  # Opens GitHub repo in browser

# Done! Everything ready.
```

### Day 2+: Development
```powershell
# 1. Start new chat with agent
"Read memory files for this project"

# 2. Pick issue from project board
# 3. Agent creates feature branch
# 4. Agent implements changes
# 5. Agent updates documentation
# 6. Agent commits with "Closes #N"
# 7. Agent auto-pushes
# 8. Issue auto-closes
# 9. Repeat!
```

---

## 🧠 How Memory System Works

### At Start of EVERY Chat Session

**Agent automatically does this:**

1. **Reads Global Memory First**
   ```
   Location: ~/.config/agents/GLOBAL_MEMORY.md
   Contains: Your universal preferences
   ```

2. **Then Reads Project Memory**
   ```
   Location: .github/instructions/memory.instruction.md
   Contains: This project's context
   ```

3. **Agent now knows:**
   - Your coding style and preferences
   - Current project focus
   - Recent decisions
   - File locations
   - What's being worked on
   - **No need to re-read entire codebase!**

### During Development

**Agent updates memory after:**
- ✅ Completing tasks
- ✅ Making decisions
- ✅ User says "remember this"
- ✅ Phase completions
- ✅ Client meetings

**Token Savings: 90%!**
- Without memory: ~20,000 tokens (reading all files)
- With memory: ~2,000 tokens (reading 2 memory files)

---

## 📚 Documentation Strategy

### Three Documentation Layers

**1. PROJECT_DOCUMENTATION.md (Comprehensive)**
- Complete project overview
- All phases and status
- Full change log (all time)
- Architecture decisions
- Setup and deployment
- Long-term reference

**2. memory.instruction.md (Working Memory)**
- Current focus (what's happening NOW)
- Recent decisions (last 30 days)
- Active blockers
- Recent conversations
- Quick reference for agents

**3. AGENTS.md (Automation Guide)**
- How everything works
- Commands and workflows
- Reference for agents
- Rarely changes

**Relationship:**
- Memory (short-term) → Eventually becomes PROJECT_DOCUMENTATION (long-term)
- Agents read memory for current context, docs for history

---

## 🎯 Phase Management

### How Phases Work

**Initial Setup (Automated):**
1. Script asks: "Define 4-8 phases for this project"
2. You provide phase names and estimates
3. Script creates GitHub milestones
4. Script breaks Phase 1 into 10-20 issues

**Phase Lifecycle:**
```
Planning → In Progress → Review → Complete
```

**Phase Completion:**
1. All issues closed
2. Milestone marked complete
3. Git tag created (v1.0-phase1)
4. Documentation updated
5. Next phase issues created

---

## 🚫 What NOT to Copy to New Projects

**DON'T copy project-specific files:**
- ❌ .git/ directory (will be created fresh)
- ❌ .github/instructions/memory.instruction.md (will be generated)
- ❌ docs/PROJECT_DOCUMENTATION.md (will be generated)
- ❌ README.md (will be generated)
- ❌ Any source code files
- ❌ .env files
- ❌ node_modules/, vendor/, build/

**ONLY copy the starter kit files listed at the top!**

---

## 🔍 Troubleshooting

### Script Fails: "gh not found"
```powershell
# Install GitHub CLI
winget install --id GitHub.cli

# Restart PowerShell
```

### Script Fails: "Not authenticated"
```powershell
gh auth login
# Use classic token with all permissions
```

### Script Fails: "Template not found"
```powershell
# Deploy global templates first
mkdir "$env:USERPROFILE\.config\agents" -Force
Copy-Item "docs\*.md" "$env:USERPROFILE\.config\agents\"
```

### Issues Not Creating
```powershell
# Verify repo exists
gh repo view

# Check authentication
gh auth status

# Try manual issue creation
gh issue create --title "Test" --body "Test issue"
```

### Memory File Not Customized
- The script replaces placeholders automatically
- If not, agent will do it on first read
- Manually edit .github/instructions/memory.instruction.md if needed

---

## 📦 Creating Your Own Starter Kit

**From an existing project (like Pinsoft):**

```powershell
# 1. Create starter kit directory
mkdir C:\StarterKit
mkdir C:\StarterKit\docs
mkdir C:\StarterKit\.github\ISSUE_TEMPLATE

# 2. Copy required files
Copy-Item "docs\AGENTS.md" "C:\StarterKit\docs\"
Copy-Item "docs\GLOBAL_MEMORY_TEMPLATE.md" "C:\StarterKit\docs\"
Copy-Item "docs\PROJECT_DOCUMENTATION_TEMPLATE.md" "C:\StarterKit\docs\"
Copy-Item "docs\MEMORY_TEMPLATE.md" "C:\StarterKit\docs\"
Copy-Item "docs\PROJECT_INITIALIZATION.md" "C:\StarterKit\docs\"
Copy-Item "docs\PROJECT_STARTER_KIT_README.md" "C:\StarterKit\docs\"
Copy-Item "SETUP_PROJECT.ps1" "C:\StarterKit\"
Copy-Item ".github\ISSUE_TEMPLATE\*" "C:\StarterKit\.github\ISSUE_TEMPLATE\"

# 3. Zip it up
Compress-Archive -Path "C:\StarterKit\*" -DestinationPath "C:\ProjectStarterKit.zip"

# 4. Store in Dropbox/OneDrive for reuse
Copy-Item "C:\ProjectStarterKit.zip" "$env:USERPROFILE\OneDrive\Templates\"
```

**For new projects:**
```powershell
# Extract and use
Expand-Archive "C:\ProjectStarterKit.zip" "C:\Users\maxmm\projects\new-project\"
cd C:\Users\maxmm\projects\new-project
.\SETUP_PROJECT.ps1 -ProjectName "new-project" -TechStack "laravel" -Database "postgresql"
```

---

## 🎉 Summary

**This starter kit gives you:**
- ✅ **One-command setup** (5-10 minutes)
- ✅ **Complete automation** (git, GitHub, docs, memory)
- ✅ **AI agent ready** (memory system active)
- ✅ **Project board populated** (issues ready to work)
- ✅ **Documentation complete** (customized templates)
- ✅ **Best practices built-in** (from day one)

**Copy → Run → Code!**

No more manual setup. No more forgetting steps. No more inconsistency.

**Every project starts perfect. Every time. Automatically.**

---

## 📞 Support

**Issues with starter kit?**
- Check troubleshooting section above
- Review docs/AGENTS.md for detailed guides
- Check docs/PROJECT_INITIALIZATION.md for step-by-step breakdown

**Agent not working as expected?**
- Verify global memory exists and is customized
- Check project memory was generated correctly
- Ensure agent is reading memory files first (should be in conversation summary)

---

**Starter Kit Version:** 1.0  
**Last Updated:** January 27, 2026  
**Maintained By:** Maxwell Murunga / Advent Digital

**Happy Coding! 🚀**
