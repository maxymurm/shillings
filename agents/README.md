# 🤖 AI Agents Control Center

**Purpose:** This folder contains all AI agent automation templates and guides.  
**Version:** 1.0  
**Last Updated:** January 27, 2026

---

## 📋 What This Folder Contains

This is the **control center** for AI agent automation. All files here are **templates** designed to be copied to new projects.

### 🎯 Core Files

| File | Purpose | Size |
|------|---------|------|
| **AGENT_START_HERE.md** | **START HERE** - Main entry point for agents | 14 KB |
| **AGENTS.md** | Complete automation guide & reference | 60 KB |
| **PROJECT_INITIALIZATION.md** | 23-step setup guide for new projects | 27 KB |
| **PROJECT_STARTER_KIT_README.md** | How to use the starter kit | 15 KB |

### 📝 Templates

| Template | Purpose | Deploy To |
|----------|---------|-----------|
| **GLOBAL_MEMORY_TEMPLATE.md** | Cross-project preferences | `~/.config/agents/GLOBAL_MEMORY.md` |
| **MEMORY_TEMPLATE.md** | Per-project memory | `.github/instructions/memory.instruction.md` |
| **PROJECT_DOCUMENTATION_TEMPLATE.md** | Project documentation | `docs/PROJECT_DOCUMENTATION.md` |

---

## 🚀 How to Use This Folder

### For New Projects

1. **Copy this entire `agents/` folder** to your new project root
2. **Say to agent:** "Set up this project"
3. **Agent automatically:**
   - Finds `AGENT_START_HERE.md`
   - Asks intelligent questions
   - Executes 23-step setup
   - Creates GitHub repo, milestones, issues, project board
   - Initializes memory system
   - You're ready to code!

### For Existing Projects (Retroactive Setup)

1. **Copy `agents/` folder** to existing project root
2. **Say to agent:** "Set up automation for this existing project"
3. **Agent automatically:**
   - Analyzes git history (2+ years capability)
   - Creates retrospective milestones (closed)
   - Creates retrospective issues (closed)
   - Documents all past work
   - Sets up current automation
   - Complete history + future automation active!

---

## 📂 Folder Structure After Setup

```
your-project/
├── agents/                           ← This folder (templates)
│   ├── AGENT_START_HERE.md          ← Agent entry point
│   ├── AGENTS.md                     ← Complete guide
│   ├── PROJECT_INITIALIZATION.md    ← Setup steps
│   └── templates...
├── docs/                             ← Project documentation (generated)
│   ├── PROJECT_DOCUMENTATION.md     ← Customized for project
│   ├── architecture/
│   ├── api/
│   └── guides/
├── .github/
│   ├── instructions/
│   │   └── memory.instruction.md    ← Live project memory
│   ├── ISSUE_TEMPLATE/
│   │   ├── bug_report.md
│   │   └── feature_request.md
│   └── pull_request_template.md
└── [your source code]
```

---

## 🧠 Memory System (Industry Standard)

**Three-Tier Memory:**

1. **Global Memory:** `~/.config/agents/GLOBAL_MEMORY.md`
   - Your personal preferences (coding style, tech stack, workflow)
   - **One-time setup** - used across ALL projects
   - **NOT committed to git** (personal)

2. **Project Memory:** `.github/instructions/memory.instruction.md`
   - Project-specific context (current focus, recent decisions)
   - **Per-project** - unique to each project
   - **Committed to git** (shared with team/future agents)

3. **Agent Templates:** `agents/` folder
   - Setup guides and templates
   - **Portable** - copy to any new project
   - **Committed to git** (project setup instructions)

**Token Savings:** 90% (2,000 tokens vs 20,000 tokens without memory)

---

## ✅ What Gets Set Up Automatically

When you copy `agents/` folder to a new project and say "Set up this project":

1. ✅ **Git & GitHub:** Repository, branches (main/develop), .gitignore
2. ✅ **Documentation:** docs/ folder with architecture, API, guides
3. ✅ **Memory System:** Project memory at `.github/instructions/`
4. ✅ **GitHub Templates:** Issue templates, PR template
5. ✅ **Project Phases:** 4-8 phases with milestones
6. ✅ **Project Board:** Kanban board with complete setup history
7. ✅ **Labels:** phase-1, enhancement, bug, documentation, etc.
8. ✅ **Phase 1 Issues:** 10-20 ready-to-go development tasks

**Time:** 5-10 minutes automated vs 2-3 hours manual

---

## 🎯 Quick Start Commands

### NEW Project
```powershell
# Copy agents/ folder to new project
Copy-Item -Recurse "C:\path\to\Pinsoft\agents" "C:\path\to\new-project\"
cd C:\path\to\new-project

# Then say to agent:
"Set up this project"
```

### EXISTING Project (Retroactive)
```powershell
# Copy agents/ folder to existing project
Copy-Item -Recurse "C:\path\to\Pinsoft\agents" "C:\path\to\existing-project\"
cd C:\path\to\existing-project

# Then say to agent:
"Set up automation for this existing project"
```

### Global Setup (One-Time)
```powershell
# Deploy global templates
mkdir "$env:USERPROFILE\.config\agents" -Force
Copy-Item "agents\GLOBAL_MEMORY_TEMPLATE.md" "$env:USERPROFILE\.config\agents\GLOBAL_MEMORY.md"
Copy-Item "agents\AGENTS.md" "$env:USERPROFILE\.config\agents\AGENTS.md"

# Edit GLOBAL_MEMORY.md with your preferences
code "$env:USERPROFILE\.config\agents\GLOBAL_MEMORY.md"
```

---

## 📖 File Descriptions

### AGENT_START_HERE.md
**The agent's entry point.** Contains:
- Instructions for determining project type (NEW vs EXISTING)
- NEW project workflow (6 questions → 23 steps → complete setup)
- EXISTING project workflow (analyze git history → retrospective phases → current automation)
- Git history analysis commands (2+ years capability)
- Codebase indexing for non-git projects
- Validation checklists
- Troubleshooting guide

### AGENTS.md
**Complete automation reference.** Contains:
- Memory system guide (setup, usage, best practices)
- Git workflow automation
- Conventional commits guide
- GitHub automation (issues, PRs, project boards)
- Phase management system
- Documentation strategy
- Client work guidelines
- Advanced automation patterns

### PROJECT_INITIALIZATION.md
**23-step setup guide.** Structured as:
- Phase 0: Prerequisites (3 issues)
- Phase 1: Git & GitHub Setup (4 issues)
- Phase 2: Documentation Structure (4 issues)
- Phase 3: Memory System Setup (2 issues)
- Phase 4: GitHub Issue Templates (3 issues)
- Phase 5: Project Phases Definition (2 issues)
- Phase 6: GitHub Project Board (3 issues)
- Phase 7: Phase 1 Issue Creation (2 issues)

Each step includes:
- Acceptance criteria
- PowerShell commands
- Agent instructions
- Time estimates

### PROJECT_STARTER_KIT_README.md
**How to use the starter kit.** Contains:
- Three usage options (automated, agent-assisted, manual)
- One-time global setup instructions
- Script parameters documentation
- Complete structure after setup
- Memory system explanation
- Documentation strategy
- Phase management lifecycle
- What NOT to copy to new projects
- Troubleshooting section

---

## 🔄 Workflow Overview

```
1. Copy agents/ folder to new project
        ↓
2. Say "Set up this project"
        ↓
3. Agent reads AGENT_START_HERE.md
        ↓
4. Agent asks 6 questions (or 5 for existing)
        ↓
5. Agent determines NEW or EXISTING
        ↓
6. Agent executes appropriate workflow
        ↓
7. Complete project ready in 5-10 minutes!
```

---

## 🎯 Agent Instructions

**If you're an AI agent reading this:**

1. **Start with:** `agents/AGENT_START_HERE.md` (main entry point)
2. **Read memory first:** 
   - Global: `~/.config/agents/GLOBAL_MEMORY.md`
   - Project: `.github/instructions/memory.instruction.md`
3. **Reference:** `agents/AGENTS.md` for complete guide
4. **Follow:** `agents/PROJECT_INITIALIZATION.md` for setup steps
5. **Update memory** after completing tasks

---

## 📊 Benefits

- ⚡ **Speed:** 5-10 min vs 2-3 hours manual setup
- 🎯 **Consistency:** Same structure every project
- 🤖 **Automation:** One command = complete setup
- 📚 **Documentation:** Always up-to-date
- 🧠 **Memory:** 90% token savings
- ♻️ **Reusability:** Copy to any new project
- 📈 **Scalability:** Works for any project size
- 🔄 **Retroactive:** Can adopt mid-project (2+ years history)

---

## 🆘 Support

**Questions?** Read:
1. `AGENT_START_HERE.md` - Quick start
2. `PROJECT_STARTER_KIT_README.md` - Detailed usage
3. `AGENTS.md` - Complete reference

**Issues?** Check troubleshooting sections in each file.

---

**Ready to automate your workflow?**  
Copy this folder to any project and say "Set up this project"! 🚀

---

*This folder is part of the Pinsoft project agent automation system.*  
*Version: 1.0 | Last Updated: January 27, 2026*
