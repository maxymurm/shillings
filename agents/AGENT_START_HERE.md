# 🤖 AGENT START HERE - Project Initialization Instructions

**🎯 PURPOSE:** This is the FIRST file AI agents should read when initializing a new project or adopting an existing one.

**📍 LOCATION:** `agents/AGENT_START_HERE.md` (root of every project)

---

## 🚨 AGENT INSTRUCTIONS - READ THIS FIRST

When a user says:
- "Set up this project"
- "Initialize this project"  
- "Set up the agents folder"
- "Follow the agents folder instructions"
- **"Scope it out and create issues"** ⭐ **PRIMARY WORKFLOW**
- Or similar variations...

**YOU MUST:**
1. ✅ Read this file FIRST (you're doing it now!)
2. ✅ Determine if this is a NEW or EXISTING project
3. ✅ Ask user for required information
4. ✅ Follow the appropriate workflow below

### 🎯 Issue Scoping Workflow (PRIMARY)

**When user says: "Scope it out and create issues"**

This is the **primary workflow** for creating ALL issues (features, bugs, epics, refactoring):

1. **Comprehensive scoping conversation** (agent asks clarifying questions)
2. **Detailed breakdown presentation** (tasks, estimates, dependencies)
3. **User approval** (review and modify if needed)
4. **Automatic execution:**
   - Create planning documents
   - Create/configure project board (if needed)
   - Create all GitHub issues with proper labels
   - Link dependencies
   - Auto-add issues to board
   - Commit planning docs
   - Report completion

**Complete documentation:** See [ISSUE_SCOPING_WORKFLOW.md](./ISSUE_SCOPING_WORKFLOW.md)

**Time:** < 10 minutes for comprehensive scoping vs 2-3 hours manual work

**Label enforcement:** EVERY issue must have phase + type labels (see AGENTS.md)

---

## 🔍 STEP 1: Determine Project Type

**Check for these indicators:**

### NEW Project (From Scratch)
- [ ] No git history (`git log` fails or shows nothing)
- [ ] No source code files yet
- [ ] Empty or nearly empty directory
- [ ] User says "new project" or "from scratch"

➡️ **If NEW:** Go to [NEW PROJECT WORKFLOW](#new-project-workflow)

### EXISTING Project (Retroactive Setup)
- [ ] Git history exists (commits going back weeks/months/years)
- [ ] Source code already exists
- [ ] User says "existing project" or "set up automation for this"
- [ ] OR: No git but lots of code files exist

➡️ **If EXISTING:** Go to [EXISTING PROJECT WORKFLOW](#existing-project-workflow)

---

## 🆕 NEW PROJECT WORKFLOW

### Required Information from User

**Ask the user these questions:**

```
To initialize your new project, I need some information:

1. **Project Name:** [What should the GitHub repo be called?]
2. **Tech Stack:** [Laravel | Kotlin/Android | Swift/iOS | Next.js | Other]
3. **Database:** [PostgreSQL | MySQL | SQLite | None]
4. **Privacy:** [Private or Public repository?]
5. **Client Project?** [Yes/No]
   - If Yes: **Client Name:** [Client company name]
6. **How many phases do you estimate?** [4-8 typical]
```

### Once You Have the Information

**Follow these files in order:**

1. **Read:** `agents/PROJECT_INITIALIZATION.md`
   - This contains 23 detailed setup steps
   - Execute each step (Issues #1-23)
   
2. **Execute all 23 steps** from PROJECT_INITIALIZATION.md:
   - ✅ Phase 0: Prerequisites (Issues #1-3)
   - ✅ Phase 1: Git & GitHub Setup (Issues #4-7)
   - ✅ Phase 2: Documentation Structure (Issues #8-11)
   - ✅ Phase 3: Memory System Setup (Issues #12-13)
   - ✅ Phase 4: GitHub Issue Templates (Issues #14-16)
   - ✅ Phase 5: Project Phases Definition (Issues #17-18)
   - ✅ Phase 6: GitHub Project Board (Issues #19-21)
   - ✅ Phase 7: Phase 1 Issue Creation (Issues #22-23)

3. **Create retrospective issues** showing what was automated
   - Create closed issues for each setup step
   - Add to "Phase 0: Setup" milestone (closed)
   - Move to "Done" column on project board
   - This documents what was automated

4. **Customize templates:**
   - Replace all `[PROJECT NAME]` placeholders
   - Replace all `PROJECT_NAME_WILL_BE_REPLACED` placeholders
   - Replace all `TIMESTAMP_WILL_BE_REPLACED` with actual timestamps
   - Fill in tech stack details

5. **Initialize memory file:**
   - Copy `agents/MEMORY_TEMPLATE.md` to `.github/instructions/memory.instruction.md`
   - Customize with project details
   - Set current focus to "Phase 1 planning"

6. **Initial commit:**
   - Commit all files with detailed message
   - Push to GitHub
   - Update develop branch

### Result

User will have:
- ✅ Git repository (local + GitHub)
- ✅ Documentation structure
- ✅ Memory system active
- ✅ GitHub project board with setup history
- ✅ Milestones for all phases
- ✅ Phase 1 issues ready to work on

---

## ♻️ EXISTING PROJECT WORKFLOW

**For projects with existing code/git history (retroactive setup)**

### Required Information from User

**Ask the user these questions:**

```
To set up automation for your existing project, I need:

1. **Project Name:** [What is this project called?]
2. **Tech Stack:** [What technologies are being used?]
3. **Database:** [What database if applicable?]
4. **GitHub Repository:** [Does a GitHub repo exist? If not, should I create one?]
5. **Client Project?** [Yes/No]
   - If Yes: **Client Name:** [Client name]
```

### Analysis Phase

**1. Analyze Git History (If Exists):**

```powershell
# Get git statistics
git log --oneline --all --graph --decorate
git rev-list --count HEAD
git log --all --pretty=format:"%h|%ai|%an|%s" --since="2 years ago"
git shortlog -sn --all
```

**Extract:**
- Total commits
- Contributors
- Date range (first commit to last)
- Major feature groupings
- Branch structure

**2. Index Codebase (If No Git or Supplement Git):**

```powershell
# Count files by type
Get-ChildItem -Recurse -File | Group-Object Extension | Sort-Object Count -Descending

# Find key files
Get-ChildItem -Recurse -Include *.php,*.kt,*.swift,*.java,*.js,*.ts,*.md

# Identify framework (Laravel, etc.)
Test-Path "artisan"  # Laravel
Test-Path "build.gradle"  # Android
Test-Path "Package.swift"  # Swift
Test-Path "package.json"  # Node/React/Vue
```

**Extract:**
- File count by type
- Framework/technology detection
- Key directories (app/, src/, database/, etc.)
- Configuration files
- Existing documentation

### Retrospective Phase Creation

**Work with user to create retrospective phases:**

```
Based on git history, I've identified these completed features:
[List features/major commits]

I recommend these retrospective phases:
1. Phase 1: Initial Setup (Jan-Feb 2024) - [X commits]
2. Phase 2: Core Features (Mar-May 2024) - [Y commits]
3. Phase 3: Advanced Features (Jun-Aug 2024) - [Z commits]
... and so on

Does this breakdown make sense? Should I adjust?
```

**Once user confirms phases:**

1. **Create retrospective milestones (CLOSED):**
   ```powershell
   gh api repos/maxymurm/PROJECT/milestones \
     -f title="Phase 1: Initial Setup (RETRO)" \
     -f state="closed" \
     -f description="Retrospectively created from git history"
   ```

2. **Create retrospective issues (CLOSED):**
   ```powershell
   gh issue create \
     --title "1.1: Project initialization (COMPLETED)" \
     --body "Retrospective issue for work completed before automation" \
     --milestone "Phase 1" \
     --label "completed,retrospective"
   
   gh issue close 1 --comment "Completed before automation system adopted"
   ```

3. **Document in PROJECT_DOCUMENTATION.md:**
   - Add all retrospective phases to "Phase Breakdown"
   - Mark as ✅ COMPLETE
   - Document deliverables for each phase
   - Add key commits to change log

### Current State Setup

**After documenting history:**

1. **Create current phase milestone (OPEN):**
   - Based on git history, determine what phase project is currently in
   - Create open milestone for current work
   - Example: "Phase 4: UI Enhancements (IN PROGRESS)"

2. **Create issues for current/future work:**
   - Break down current phase into issues
   - Create issues for known upcoming features
   - Add to project board in "Todo" column

3. **Set up memory file:**
   - Copy template to `.github/instructions/memory.instruction.md`
   - Populate "Recent Decisions & Context" from git history
   - Fill "Project File Map" with existing structure
   - Set "Current Focus" to current phase
   - Add retrospective summary to "Lessons Learned"

4. **Update all documentation:**
   - Fill in PROJECT_DOCUMENTATION.md with:
     - Complete phase history
     - Current architecture (analyze code)
     - Setup instructions (reverse-engineer from codebase)
     - Known issues (from git issues if they exist)

### Result

User will have:
- ✅ Complete project history documented
- ✅ Retrospective phases and issues (closed)
- ✅ Current phase defined with open issues
- ✅ Memory system capturing current state
- ✅ Project board showing history + future
- ✅ Ready to continue development with full automation

---

## 📂 Files in agents/ Folder

**Reference these files as needed:**

| File | Purpose | When to Use |
|------|---------|-------------|
| **AGENT_START_HERE.md** | This file! Start here | Always read first |
| **AGENTS.md** | Complete automation guide | Reference for commands/workflows |
| **PROJECT_INITIALIZATION.md** | 23-step setup guide | New projects (execute all steps) |
| **PROJECT_STARTER_KIT_README.md** | How starter kit works | Understanding the system |
| **GLOBAL_MEMORY_TEMPLATE.md** | User preferences template | Deploy to ~/.config/agents/ |
| **PROJECT_DOCUMENTATION_TEMPLATE.md** | Project docs template | Copy to docs/PROJECT_DOCUMENTATION.md |
| **MEMORY_TEMPLATE.md** | Project memory template | Copy to .github/instructions/memory.instruction.md |

---

## 🧠 Memory System - Critical!

### At Start of EVERY Conversation

**ALWAYS do this FIRST:**

1. **Read Global Memory:**
   ```
   Location: ~/.config/agents/GLOBAL_MEMORY.md
   Contains: User's universal preferences
   ```

2. **Read Project Memory:**
   ```
   Location: .github/instructions/memory.instruction.md
   Contains: This project's recent context
   ```

3. **Now you know:**
   - User's coding style preferences
   - This project's current focus
   - Recent decisions and why they were made
   - Where files are located
   - What's being worked on right now

**Token Savings: 90%!**
- Without memory: ~20,000 tokens (reading entire codebase)
- With memory: ~2,000 tokens (reading 2 memory files)

### Update Memory After

- ✅ Completing each task/issue
- ✅ Making significant decisions
- ✅ User says "remember this"
- ✅ Phase completions
- ✅ End of chat session

---

## 🎯 Quick Command Reference

### For New Projects

```powershell
# Create GitHub repo
gh repo create PROJECT_NAME --private --source=. --remote=origin

# Create milestone
gh api repos/maxymurm/PROJECT_NAME/milestones \
  -f title="Phase 1: Foundation" \
  -f due_on="2026-02-28T23:59:59Z"

# Create issue
gh issue create \
  --title "1.1: Task name" \
  --body "Description" \
  --milestone "Phase 1" \
  --assignee maxymurm \
  --label "enhancement,phase-1"

# Create project board
gh project create --title "PROJECT_NAME" --owner maxymurm
```

### For Existing Projects

```powershell
# Analyze git history
git log --oneline --all --graph --decorate
git log --all --pretty=format:"%h|%ai|%an|%s" > commits.txt

# Create retrospective milestone (closed)
gh api repos/maxymurm/PROJECT_NAME/milestones \
  -f title="Phase 1: Foundation (RETRO)" \
  -f state="closed"

# Create retrospective issue (closed)
gh issue create --title "1.1: Feature (COMPLETED)" --body "Retro" --milestone "Phase 1"
gh issue close 1 --comment "Completed before automation"
```

---

## ✅ Validation Checklist

**Before finishing initialization, verify:**

### For New Projects
- [ ] GitHub repository exists and is linked
- [ ] docs/PROJECT_DOCUMENTATION.md exists and is customized
- [ ] .github/instructions/memory.instruction.md exists and is customized
- [ ] GitHub milestones created for all phases
- [ ] GitHub project board exists with columns
- [ ] Labels created (phase-1, enhancement, bug, etc.)
- [ ] Issue templates exist (.github/ISSUE_TEMPLATE/)
- [ ] PR template exists (.github/pull_request_template.md)
- [ ] Phase 1 issues created (10-20 issues)
- [ ] Initial commit pushed to GitHub
- [ ] Develop branch created and pushed
- [ ] All placeholders replaced (no [BRACKETS] left)

### For Existing Projects
- [ ] Git history analyzed and documented
- [ ] Retrospective phases created (closed milestones)
- [ ] Retrospective issues created (closed)
- [ ] docs/PROJECT_DOCUMENTATION.md updated with history
- [ ] .github/instructions/memory.instruction.md populated with current state
- [ ] Current phase defined (open milestone)
- [ ] Current/future issues created
- [ ] Project board shows complete history
- [ ] File map documented in memory
- [ ] Architecture documented from code analysis

---

## 🚀 After Initialization

**Tell the user:**

```
✅ Project initialization complete!

**What's ready:**
- Git repository linked to GitHub
- Complete documentation structure
- Memory system active
- Project board with [X] issues ready
- [Y] phases defined

**Next steps:**
1. Review project board: gh browse
2. Pick first issue from Phase 1
3. I'll create feature branch and start coding
4. I'll update memory and docs as we go

**In future sessions:**
- I'll read memory files first (instant context!)
- No need to explain project again
- Just say "Continue with next issue"

Ready to start development! 🎉
```

---

## 🔍 Troubleshooting

### User says: "Set up this project" but no agents/ folder exists
**Response:** "I don't see an agents/ folder in this directory. Have you copied the starter kit? I need the agents/ folder with initialization templates to set up the project."

### GitHub CLI not authenticated
**Detect:** `gh auth status` fails  
**Response:** "GitHub CLI is not authenticated. Please run: `gh auth login` and create a classic token with all permissions."

### Global memory doesn't exist
**Detect:** `~/.config/agents/GLOBAL_MEMORY.md` missing  
**Response:** "Global memory template not found. I need to deploy it first. Should I copy from agents/GLOBAL_MEMORY_TEMPLATE.md to ~/.config/agents/?"

### Git history exists but no GitHub repo
**Response:** "I see git history but no GitHub remote. Should I create a GitHub repository for this project?"

---

## 📖 Summary

**This file tells agents:**
1. ✅ How to detect new vs existing projects
2. ✅ What questions to ask users
3. ✅ Which files to follow (PROJECT_INITIALIZATION.md)
4. ✅ How to handle retroactive initialization
5. ✅ How to use memory system
6. ✅ How to validate completion

**No more guessing. No more manual instructions. Everything automated.**

---

**AGENT_START_HERE.md - Version 1.0**  
**Always read this file first when initializing projects!**  
**Last Updated:** January 27, 2026
