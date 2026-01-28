# Issue Scoping & Creation Workflow

## 🎯 Overview

This document defines the **automated issue creation workflow** that triggers whenever you say the magic phrase:

> **"Scope it out and create issues"**

This workflow ensures:
- ✅ Comprehensive scoping of features, bugs, and epics
- ✅ Logical breakdown into actionable tasks
- ✅ Automatic GitHub issue creation with proper metadata
- ✅ Automatic project board creation and syncing
- ✅ Zero manual work after initial scoping conversation

---

## 🚀 Trigger Phrase

**Primary trigger:**
```
"Scope it out and create issues"
```

**Variations (also work):**
```
"Scope this out and create issues"
"Scope it and create issues"
"Scope out and create issues"
"Break this down and create issues"
```

**Context:** Use this phrase for ANY of the following:
- New features or epics
- Bug reports from users
- Technical debt or refactoring
- New project phases
- Architecture changes
- Testing requirements
- Documentation needs

---

## 📋 Comprehensive Scoping Process

### Step 1: Initial Context Gathering

**Agent asks clarifying questions based on request type:**

#### For New Features/Epics:
```
1. What's the user story? (Who needs this and why?)
2. What are the core requirements? (Must-have vs nice-to-have)
3. Are there technical constraints? (APIs, databases, integrations)
4. What's the expected timeline? (Urgent, normal, long-term)
5. Who are the stakeholders? (Users, admins, developers)
6. Are there dependencies? (Other features, external services)
7. What's the success criteria? (How do we know it's done?)
8. Any security/compliance considerations?
9. What's the rollout strategy? (Phased, all-at-once, beta)
10. What's the rollback plan if issues arise?
```

#### For Bug Reports:
```
1. What's the expected behavior?
2. What's the actual behavior?
3. Steps to reproduce?
4. Which environments affected? (Production, staging, dev)
5. Which users affected? (All, specific roles, specific browsers)
6. Error messages or logs?
7. When did this start? (Recent deploy, always been there)
8. What's the user impact? (Critical, high, medium, low)
9. Any workarounds available?
10. Related issues or recent changes?
```

#### For Technical Debt/Refactoring:
```
1. What's the current pain point?
2. What's the proposed solution?
3. What's the risk level? (High, medium, low)
4. Impact on existing features?
5. Testing strategy?
6. Can it be done incrementally?
7. What's the ROI? (Performance, maintainability, scalability)
```

### Step 2: Scope Breakdown

**Agent creates structured breakdown:**

```markdown
# [Feature/Bug/Epic Name]

## Overview
[1-2 sentence summary]

## User Story
As a [user type], I want [goal] so that [benefit].

## Requirements
### Must Have
- [ ] Requirement 1
- [ ] Requirement 2

### Nice to Have
- [ ] Enhancement 1
- [ ] Enhancement 2

## Technical Approach
[High-level architecture/solution]

## Task Breakdown

### Epic Issue (Parent)
- Title: [Epic name]
- Description: [Full context]
- Labels: epic, phase-X
- Milestone: [Phase name]

### Development Tasks
1. **[Task 1 name]** (4-6 hours)
   - Subtasks: [specific steps]
   - Dependencies: None
   - Labels: phase-X, backend/frontend, enhancement

2. **[Task 2 name]** (3-4 hours)
   - Subtasks: [specific steps]
   - Dependencies: Task 1
   - Labels: phase-X, database, enhancement

[... continues ...]

### Testing Tasks
1. **Unit tests for [feature]** (2-3 hours)
2. **Integration tests** (3-4 hours)
3. **Manual QA checklist** (1-2 hours)

### Documentation Tasks
1. **Update architecture docs** (1-2 hours)
2. **Update API documentation** (1-2 hours)
3. **Update README** (30 min)

## Dependencies
- External: [Third-party APIs, services]
- Internal: [Other features, database migrations]

## Risks
- Risk 1: [Description + mitigation]
- Risk 2: [Description + mitigation]

## Timeline Estimate
- Development: X hours
- Testing: Y hours
- Documentation: Z hours
- **Total: N hours (~W days)**

## Success Criteria
- [ ] Criterion 1
- [ ] Criterion 2
- [ ] All tests passing
- [ ] Documentation updated
- [ ] Code reviewed and merged
```

### Step 3: User Review & Approval

**Agent presents breakdown and asks:**
```
"I've scoped out [N] tasks totaling [X] hours of work. Here's the breakdown:

[Summary of tasks]

Does this look complete? Any additions or changes?"
```

**User can:**
- Approve: "Looks good" / "Yes, create them"
- Modify: "Add task for X" / "Change priority of Y"
- Ask questions: "What about Z?"

### Step 4: Automated Execution

**Once approved, agent automatically:**

#### 4.1 Create Planning Document
```
Location: docs/planning/[feature-name]-scope.md
Content: Full scoping breakdown (from Step 2)
Purpose: Version-controlled record of decisions
```

#### 4.2 Generate JSON Template
```
Location: docs/planning/[feature-name]-issues.json
Content: All issues in JSON format
Purpose: Reproducible, version-controlled issue definitions
```

#### 4.3 Check GitHub Project Board

**If board exists:**
- Use existing board
- Issues will auto-add via existing workflow

**If board does NOT exist:**
```powershell
# Create project board
gh project create \
  --owner [owner] \
  --title "[Project Name] Development Board" \
  --format json

# Get project ID (for GraphQL)
$projectId = [Get project ID via GraphQL]

# Create auto-add workflow via GraphQL
gh api graphql -f query='
  mutation {
    createProjectV2Workflow(input: {
      projectId: "'$projectId'"
      name: "Auto-add issues"
      patterns: [{field: "labels", values: ["*"]}]
      enabled: true
    }) {
      workflow { id name enabled }
    }
  }'

# Save board URL to memory
echo "Board URL: [url]" >> .github/instructions/memory.instruction.md
```

**If GraphQL fails:**
```
⚠️ STOP: Board creation succeeded but auto-add workflow setup failed.

ACTION REQUIRED:
1. Go to: [board URL]
2. Click "..." → Settings → Workflows
3. Enable "Auto-add to project"
4. Repository: Select [repo-name]
5. Filter: "is:issue is:pr" (or just "is:issue")
6. Save

⚠️ IMPORTANT: Auto-add workflow only affects FUTURE issues.
For existing issues, manually trigger:

```powershell
# Add existing issues to board
for ($i=1; $i -le [last-issue-number]; $i++) {
    gh project item-add [project-number] --owner [owner] --url "https://github.com/[owner]/[repo]/issues/$i"
}
```

Once complete, say "Continue" and I'll proceed with issue creation.
```

#### 4.4 Create GitHub Issues

**For each task in breakdown:**
```powershell
gh issue create \
  --title "[Task title]" \
  --body "[Full description with tasks, dependencies, acceptance criteria]" \
  --label "phase-X,[type],enhancement" \
  --milestone "[Milestone name]" \
  --assignee "@me"
```

**Label enforcement:**
- ✅ **EVERY issue MUST have at least 2 labels:**
  - Phase label: `phase-1` through `phase-6`
  - Type label: `backend`, `frontend`, `database`, `api`, `testing`, `documentation`, `bug`, `feature`
- ✅ Additional labels: `enhancement`, `epic`, `dependency`
- ✅ Issues without proper labels are **rejected** (agent asks to clarify)

**Issue linking:**
- Parent epic issue created first
- Child issues reference epic in description: "Part of #[epic-number]"
- Dependencies noted in descriptions

#### 4.5 Commit Planning Documents

```powershell
git add docs/planning/[feature-name]-*
git commit -m "docs: add planning for [feature name]

- Scoping document with requirements and breakdown
- Issue definitions in JSON format
- [N] issues created: #X - #Y

Total estimated effort: [N] hours"
git push
```

#### 4.6 Report Completion

```
✅ Issue scoping complete!

📊 Summary:
- Planning docs: docs/planning/[feature-name]-scope.md
- Issues created: [N] issues (#X - #Y)
- Milestone: [Milestone name]
- Project board: [Board URL]
- Estimated effort: [N] hours

📋 Issues created:
1. #X - [Epic title] (EPIC)
2. #Y - [Task 1 title] (4-6h)
3. #Z - [Task 2 title] (3-4h)
[... list all ...]

🎯 Next steps:
1. Review issues on board: [Board URL]
2. Start with issue #Y (first task)
3. Say "Start working on #Y" to begin development

All issues have been automatically added to the project board via auto-add workflow.
```

---

## 🏷️ Label Enforcement Rules

### Required Labels (EVERY issue)

**Phase Label (pick ONE):**
- `phase-1` - Database Schema & Models
- `phase-2` - Business Logic & Services
- `phase-3` - API Development
- `phase-4` - Admin Panel (Filament)
- `phase-5` - Testing & QA
- `phase-6` - Deployment & Documentation

**Type Label (pick ONE or MORE):**
- `backend` - Server-side code
- `frontend` - Client-side code
- `database` - Database migrations, models
- `api` - API endpoints, integration
- `testing` - Tests, QA
- `documentation` - Docs, guides, comments
- `bug` - Bug fixes
- `feature` - New features

**Additional Labels (optional but recommended):**
- `enhancement` - Improvements to existing features
- `epic` - Parent issue grouping multiple tasks
- `dependency` - Blocked by or blocking other issues
- `security` - Security-related changes
- `performance` - Performance improvements
- `refactor` - Code refactoring
- `breaking-change` - Breaking API/behavior changes

### Label Validation

**Agent checks BEFORE creating issue:**
```
IF issue has NO phase label:
  ASK: "Which phase does this belong to? (phase-1 to phase-6)"

IF issue has NO type label:
  ASK: "What type of work is this? (backend/frontend/database/api/testing/documentation)"

IF labels are ambiguous:
  SUGGEST: Based on description and context
```

### Auto-Labeling Suggestions

**Agent automatically suggests labels based on keywords:**

| Keywords in Title/Description | Suggested Labels |
|-------------------------------|------------------|
| "migration", "schema", "model" | `database`, `phase-1` |
| "test", "spec", "coverage" | `testing`, `phase-5` |
| "API", "endpoint", "route" | `api`, `backend`, `phase-3` |
| "admin", "panel", "dashboard" | `frontend`, `phase-4` |
| "deploy", "CI/CD", "production" | `deployment`, `phase-6` |
| "bug", "fix", "error" | `bug` |
| "document", "README", "guide" | `documentation` |

---

## 🎯 Example Workflows

### Example 1: New Feature (Comprehensive)

**User says:**
```
"We need user authentication with 2FA. Scope it out and create issues."
```

**Agent responds:**
```
I'll scope out the authentication feature. Let me ask some clarifying questions:

1. Authentication methods needed?
   - Email/password
   - OAuth (Google, GitHub, etc.)
   - SSO

2. 2FA options?
   - SMS codes
   - Authenticator app (TOTP)
   - Backup codes
   - Email codes

3. Password requirements?
   - Minimum length?
   - Complexity rules?
   - Expiration policy?
   - Password history?

4. Session management?
   - Session timeout duration?
   - Remember me feature?
   - Multiple sessions allowed?

5. User roles involved?
   - Just basic user authentication?
   - Admin/moderator roles?
   - Permissions system needed?

6. Audit logging?
   - Track login attempts?
   - Track password changes?
   - Security event logging?

Please answer what applies to your needs.
```

**User responds:**
```
1. Email/password only for now
2. Authenticator app (TOTP) + backup codes
3. Min 12 chars, must have uppercase/lowercase/number/symbol, no expiration
4. 30 min timeout, remember me for 30 days, single session only
5. Basic user + admin roles
6. Yes, log all login attempts and security events
```

**Agent creates scoping document and presents:**
```
Perfect! I've scoped this into 18 tasks across 3 weeks:

## Epic: User Authentication with 2FA

### Phase 2: Business Logic (8 tasks, 32 hours)
1. User registration with validation (4h)
2. Email/password authentication (6h)
3. Password hashing and security (2h)
4. Session management system (6h)
5. Remember me functionality (3h)
6. User roles system (Basic + Admin) (5h)
7. Password reset flow (4h)
8. Security audit logging (2h)

### Phase 3: API Development (5 tasks, 18 hours)
1. Registration API endpoint (3h)
2. Login API endpoint (4h)
3. Logout API endpoint (2h)
4. Password reset API endpoints (4h)
5. User profile API endpoints (5h)

### Phase 4: 2FA Implementation (3 tasks, 14 hours)
1. TOTP setup and verification (6h)
2. Backup codes generation (4h)
3. 2FA challenge flow (4h)

### Phase 5: Testing (2 tasks, 12 hours)
1. Authentication test suite (8h)
2. Security penetration testing (4h)

Total: 18 tasks, 76 hours (~10 days)

Does this look complete? Any changes needed?
```

**User approves:**
```
"Looks good, create them"
```

**Agent executes:**
```
✅ Creating planning documents...
✅ Checking project board... (exists: Test Automation Development Board)
✅ Creating 18 GitHub issues...
✅ Linking dependencies...
✅ Committing planning docs...

✅ Issue scoping complete!

📊 Summary:
- Planning docs: docs/planning/authentication-2fa-scope.md
- Issues created: 18 issues (#13 - #30)
- Milestone: Phase 2: Business Logic, Phase 3: API Development, Phase 4: Admin Panel
- Project board: https://github.com/users/maxymurm/projects/1
- Estimated effort: 76 hours

📋 Issues created:
1. #13 - User Authentication with 2FA (EPIC)
2. #14 - User registration with validation (4h) - Phase 2
3. #15 - Email/password authentication (6h) - Phase 2
[... lists all 18 ...]

🎯 Ready to start! First task: #14 - User registration with validation
```

---

### Example 2: Bug Report (Quick)

**User says:**
```
"Login fails on Safari mobile. Scope it out and create issues."
```

**Agent responds:**
```
I'll scope this out quickly. A few questions:

1. Which iOS versions affected?
2. What error do users see?
3. Does it work on Safari desktop?
4. How many users reported this?

Priority assumption: HIGH (login is critical)
```

**User responds:**
```
1. iOS 16 and 17
2. "Session expired" error immediately after login
3. Yes, desktop Safari works fine
4. 5 users in last 2 days
```

**Agent creates:**
```
Creating 4 issues for Safari mobile login bug:

1. #31 - [BUG] Login fails on Safari mobile (iOS 16-17) (EPIC)
2. #32 - Investigate session cookie behavior on Safari mobile (2h)
3. #33 - Fix session cookie configuration for iOS Safari (3h)
4. #34 - Test login flow on Safari iOS 16 & 17 (2h)

Labels: bug, backend, api, testing, phase-2
Priority: HIGH
Estimated: 7 hours

Creating issues now...
```

---

### Example 3: Technical Debt

**User says:**
```
"The database queries are slow. Need to refactor. Scope it out and create issues."
```

**Agent responds:**
```
I'll scope out the database optimization. Questions:

1. Which specific queries are slow? (or all queries?)
2. What's the current response time vs target?
3. Database size? (rows in largest tables)
4. Indexes already in place?
5. Is this affecting users now or preventive?
6. Can we do incremental optimization or need big refactor?
```

[... continues with comprehensive scoping ...]

---

## 🔧 Integration with Git Workflow

### Automatic Commit Messages

When issues are created, agent commits with:
```
docs: add planning for [feature name]

- Scoping document with requirements and breakdown  
- Issue definitions in JSON format
- [N] issues created: #X - #Y

Total estimated effort: [N] hours

Related issues: #X, #Y, #Z
```

### Automatic Branch Naming

When starting work on an issue, agent creates:
```
feature/[issue-number]-[brief-description]
bug/[issue-number]-[brief-description]
refactor/[issue-number]-[brief-description]
```

### Closing Issues

Agent automatically closes issues when work is merged with commit message:
```
feat: implement [feature description]

[implementation details]

Closes #[issue-number]
```

---

## 🚨 Error Handling

### Board Creation Fails

**Symptom:** API error during `gh project create`

**Agent action:**
```
⚠️ STOP: Project board creation failed.

Error: [error message]

ACTION REQUIRED:
1. Create board manually: https://github.com/[owner]/[repo]/projects/new
2. Name it: "[Project Name] Development Board"
3. Once created, say "Board created, continue"

I'll then configure auto-add and create issues.
```

### Auto-Add Workflow Fails

**Symptom:** GraphQL mutation error

**Agent action:**
```
⚠️ STOP: Board created successfully, but auto-add workflow setup failed.

Board URL: [url]

ACTION REQUIRED:
1. Go to: [board URL]
2. Click "..." → Settings → Workflows
3. Enable "Auto-add to project"
4. Filter: "Label is any of: *" (add all issues)
5. Save

Once complete, say "Workflow enabled, continue"

I'll then create all issues.
```

### Issue Creation Fails

**Symptom:** API rate limit or network error

**Agent action:**
```
⚠️ Issue creation interrupted at issue #X

Created successfully: #Y, #Z (first N issues)
Failed: Remaining M issues

ACTION REQUIRED:
Wait 60 seconds (API rate limit), then say "Retry issue creation"

I'll create the remaining issues.
```

### Missing Labels

**Symptom:** User-provided info doesn't clearly indicate phase or type

**Agent action:**
```
⚠️ Cannot determine appropriate labels for this task.

Task: "[task description]"

Questions:
1. Which phase does this belong to? (phase-1 to phase-6)
2. What type of work is this? (backend/frontend/database/api/testing/documentation)

Once you clarify, I'll continue with issue creation.
```

---

## 📁 File Structure

After scoping workflow, you'll have:

```
project/
├── docs/
│   └── planning/
│       ├── [feature-name]-scope.md          # Comprehensive scoping document
│       └── [feature-name]-issues.json       # JSON issue definitions
├── .github/
│   ├── instructions/
│   │   └── memory.instruction.md            # Updated with board URL
│   └── ISSUE_TEMPLATES_BULK.json            # Can be updated from planning JSON
└── [Project files...]
```

---

## 📊 Metrics & Reporting

### After Issue Creation

**Agent provides:**
```
📊 Scoping Metrics:
- Total issues created: [N]
- Estimated effort: [X] hours (~[Y] days)
- Breakdown:
  * Development: [A] hours ([B]%)
  * Testing: [C] hours ([D]%)
  * Documentation: [E] hours ([F]%)
- Average issue size: [G] hours
- Dependencies identified: [H]
- Risks documented: [I]
```

### Velocity Tracking

**Agent can report:**
```
"Based on past velocity (15 hours/week), estimated completion: [Date]"
"This adds [N] hours to Phase 2 milestone (currently at [X]/[Y] hours)"
```

---

## 🎓 Best Practices

### 1. Always Use Trigger Phrase
Don't say "create some issues" or "add tasks". Always use:
```
"Scope it out and create issues"
```

This ensures the full automated workflow runs.

### 2. Provide Context Upfront
Better:
```
"New feature: user authentication with 2FA, targeting Phase 2, 
high priority, must support TOTP and backup codes. Scope it out and create issues."
```

Than:
```
"Add auth. Scope it out and create issues."
```

### 3. Review Before Approving
Agent will present full breakdown. Take time to review:
- Are all requirements covered?
- Are estimates realistic?
- Are dependencies identified?
- Are there missing edge cases?

### 4. Keep Planning Docs Updated
If scope changes during implementation:
```
"Update planning doc for [feature]: [changes]"
```

Agent will update docs and create follow-up issues if needed.

### 5. Use for Everything
Don't create issues manually. Always scope first:
- ✅ New features → "Scope it out and create issues"
- ✅ Bugs → "Scope it out and create issues"
- ✅ Refactoring → "Scope it out and create issues"
- ✅ Documentation → "Scope it out and create issues"

---

## 🔗 Related Documentation

- [AGENTS.md](./AGENTS.md) - Complete automation guide
- [AGENT_START_HERE.md](./AGENT_START_HERE.md) - AI agent entry point
- [PROJECT_INITIALIZATION.md](./PROJECT_INITIALIZATION.md) - Initial project setup
- [ISSUE_AUTOMATION_GUIDE.md](../.github/ISSUE_AUTOMATION_GUIDE.md) - Alternative issue creation methods

---

## 🚀 Quick Reference

**Trigger:**
```
"Scope it out and create issues"
```

**Process:**
1. Agent asks clarifying questions
2. Agent presents comprehensive breakdown
3. You review and approve
4. Agent creates planning docs
5. Agent creates/configures board (if needed)
6. Agent creates all issues
7. Issues auto-add to board
8. Agent commits planning docs
9. Agent reports completion

**Time:**
- Scoping conversation: 2-5 minutes
- Issue creation: 30-60 seconds (for 20 issues)
- **Total: < 10 minutes for comprehensive project scoping**

**Zero manual work after initial conversation!**
