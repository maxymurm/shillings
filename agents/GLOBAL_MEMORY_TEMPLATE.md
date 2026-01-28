---
applyTo: '**'
lastUpdated: '2026-01-26 15:50'
owner: 'Maxwell Murunga (@maxymurm)'
company: 'Advent Digital'
---

# Global Memory - Cross-Project Preferences

> **AGENT INSTRUCTIONS:** Read this file BEFORE reading project-specific memory. This contains universal preferences that apply to ALL projects. Update when user establishes new preferences or patterns.

---

## 👤 Developer Profile

**Name:** Maxwell Murunga  
**GitHub:** @maxymurm  
**Email:** maxmm@adventit.digital  
**Company:** Advent Digital  
**Location:** [Your location]  
**Timezone:** [Your timezone]

---

## 💻 Technology Preferences

### Backend
- **Primary:** Laravel 11+ with PHP 8.3+
- **Database:** PostgreSQL (preferred), MySQL (acceptable)
- **API Style:** RESTful JSON APIs
- **Authentication:** JWT preferred, Laravel Sanctum acceptable
- **Validation:** Form Request classes, never in controllers

### Mobile Development
- **Android:** Kotlin (preferred), Java (legacy only)
- **iOS:** Swift (preferred), Objective-C (legacy only)
- **Architecture:** MVVM for both platforms
- **IDE:** Android Studio for Android, Xcode for iOS

### Frontend (Web)
- **Framework:** [Your preference - Vue.js, React, etc.]
- **Styling:** Tailwind CSS preferred
- **Build Tool:** Vite preferred

### Tools & Environment
- **Editor:** VS Code
- **Terminal:** PowerShell (Windows)
- **Version Control:** Git with GitHub
- **Package Managers:** Composer (PHP), npm (Node), Gradle (Android), CocoaPods/SPM (iOS)

---

## 🎨 Coding Style & Conventions

### General Principles
- **Clarity over cleverness:** Readable code > clever code
- **DRY principle:** Don't Repeat Yourself
- **SOLID principles:** Follow SOLID design principles
- **Comments:** Write why, not what
- **Naming:** Descriptive names, avoid abbreviations

### PHP/Laravel Specific
- **PSR-12:** Follow PSR-12 coding standard
- **Type Hints:** Always use type hints (PHP 8.3 features)
- **Eloquent:** Use Eloquent relationships, avoid raw queries
- **Controllers:** Thin controllers, fat services
- **Validation:** Form Request classes for validation
- **Routes:** Resourceful routes when possible

### Kotlin/Android Specific
- **Style:** Follow Android Kotlin style guide
- **Coroutines:** Use coroutines for async operations
- **Architecture:** MVVM with Repository pattern
- **Dependency Injection:** Hilt/Dagger preferred
- **Naming:** camelCase for variables, PascalCase for classes

### Swift/iOS Specific
- **Style:** Follow Swift style guide
- **Async:** async/await for modern Swift
- **Architecture:** MVVM with Combine/SwiftUI
- **Naming:** camelCase for variables/methods, PascalCase for types
- **Optionals:** Safe unwrapping, avoid force unwrapping

---

## 📝 Git & Version Control

### Branching Strategy
```
main (production)
  └── develop (staging)
        └── feature/issue-N-description
        └── bugfix/issue-N-description
        └── hotfix/issue-N-description
```

### Commit Message Format
```
<type>: <short summary>

<detailed description>
- Bullet points for changes
- More details as needed

Closes #<issue-number>
```

**Types:** feat, fix, docs, style, refactor, test, chore, perf

### Commit Preferences
- **Frequency:** Commit at logical checkpoints (not every line, not once per feature)
- **Auto-push:** Push after EVERY commit
- **Atomic Commits:** One logical change per commit
- **Message Quality:** Descriptive messages, explain WHY not WHAT
- **Issue References:** Always include "Closes #N" when completing issues

---

## 📋 Project Management Preferences

### Always Phase-Based Development
Every project must be broken into **4-8 logical phases**:
1. **Phase 1:** Foundation (database, models, basic structure)
2. **Phase 2:** Core business logic
3. **Phase 3:** API/Integration layer
4. **Phase 4:** UI/Frontend
5. **Phase 5:** Testing & QA
6. **Phase 6:** Deployment & Go-Live
7. **Phase 7+:** Additional features/enhancements

### GitHub Workflow
- **Projects:** Always use GitHub Projects (Kanban board)
- **Issues:** Detailed issues with:
  - Clear title (e.g., "1.1: Design users table")
  - Description with acceptance criteria
  - Estimate (hours)
  - Labels (phase-N, enhancement/bug, category)
  - Milestone (phase)
  - Assignee (@maxymurm)
- **Milestones:** One per phase with due date
- **Labels Standard Set:**
  - Phases: phase-1, phase-2, phase-3, etc.
  - Types: enhancement, bug, documentation, refactor
  - Status: in-progress, blocked, needs-review
  - Categories: database, api, frontend, mobile, etc.

### Issue Breakdown
- **Phase 1:** 10-20 issues (break down immediately)
- **Phase 2+:** Break down when Phase N-1 is 75% complete
- **Estimate:** Every issue must have time estimate
- **Granularity:** Issues should be 1-8 hours each

---

## 📖 Documentation Preferences

### Documentation Structure
```
docs/
├── PROJECT_DOCUMENTATION.md (main, always current)
├── AGENTS.md (copy from ~/.config/agents/)
├── architecture/
│   ├── database.md
│   ├── api.md
│   └── system-design.md
├── api/
│   ├── authentication.md
│   └── endpoints.md
├── guides/
│   ├── setup.md
│   ├── deployment.md
│   └── user-guide.md
└── client/ (if client project)
    ├── MEETING_NOTES_YYYY-MM-DD.md
    ├── quotes/
    └── proposals/
```

### Documentation Update Rules
1. **Update PROJECT_DOCUMENTATION.md after EVERY significant change**
2. **Add timestamped entry to Change Log**
3. **Include files modified and line counts**
4. **Include testing status**
5. **Commit docs in same commit as code**
6. **Never let docs fall out of sync**

### Change Log Entry Format
```markdown
### YYYY-MM-DD

#### HH:MM - Feature Name (Issue #N)
**Type:** Feature | Bug Fix | Documentation | Refactor  
**Branch:** feature/issue-N-name  
**Commit:** `commit message`

**Changes:**
- Bullet point 1
- Bullet point 2

**Files Modified:**
- path/to/file.ext (new | modified | deleted, X lines)

**Testing:**
- Manual: ✅ Pass | ❌ Fail | ⏳ Pending
- Unit: ✅ Pass | ❌ Fail | ⏳ Pending
```

---

## 💼 Client Work Preferences

### Meeting Documentation
**File:** `docs/client/MEETING_NOTES_YYYY-MM-DD.md`

**Required Sections:**
- Date, time, attendees, meeting type
- Agenda
- Discussion summary
- Decisions made
- Action items (client and developer)
- Next meeting scheduled

**Frequency:** Document EVERY client meeting

### Quote/Invoice Styling
- **Color Scheme:** Blue (#2563eb primary, #1e40af dark)
- **Format:** Professional HTML (print to PDF)
- **Numbering:** ADV-PS-XXX format (PS = client initials)
- **File Naming:** `Quote ADV-PS-XXX - [Client Name] - [Description].html`
- **HTML Title:** Must match filename for PDF save
- **Structure:** Header, client info, project title, detailed breakdown, deliverables, payment terms, print button

### Client Preferences Tracking
Always remember and track:
- Client coding preferences/requirements
- Client timezone and working hours
- Client communication preferences
- Client approval processes
- Client technical constraints

---

## 🚀 Deployment Preferences

### Staging/Production
- **Staging:** Always test on staging first
- **Production:** Deploy only after staging approval
- **Rollback Plan:** Always have rollback plan
- **Database Migrations:** Test migrations on staging first
- **Environment Variables:** Never commit secrets

### CI/CD
- **Preferred:** GitHub Actions
- **Testing:** Run tests on every push
- **Deployment:** Auto-deploy to staging, manual to production

---

## 🧪 Testing Preferences

### Test Coverage
- **Target:** 80%+ coverage
- **Priority:** Business logic > Controllers > Views
- **TDD:** Preferred for complex business logic

### Test Types
- **Unit Tests:** Test individual methods/classes
- **Feature Tests:** Test API endpoints, user flows
- **Integration Tests:** Test component interactions
- **E2E Tests:** Test critical user journeys

### Testing Tools
- **PHP:** PHPUnit, Pest (Laravel)
- **Android:** JUnit, Mockito, Espresso
- **iOS:** XCTest, Quick/Nimble

---

## 🔐 Security Preferences

### Authentication
- **Password Hashing:** bcrypt or Argon2
- **API Authentication:** JWT or Laravel Sanctum
- **Session Management:** Secure, HTTP-only cookies
- **MFA:** Implement when appropriate

### Best Practices
- **Input Validation:** Validate everything from users
- **SQL Injection:** Use parameterized queries (Eloquent handles this)
- **XSS Protection:** Escape output
- **CSRF Protection:** Enable CSRF tokens
- **Rate Limiting:** Implement on all public APIs
- **Secrets Management:** Use environment variables, never hardcode

---

## 🎯 Performance Preferences

### Database
- **Indexing:** Index foreign keys and frequently queried columns
- **N+1 Queries:** Use eager loading (with/load)
- **Query Optimization:** Use query builder efficiently
- **Caching:** Cache expensive queries

### Application
- **Lazy Loading:** Load resources only when needed
- **Asset Optimization:** Minify CSS/JS
- **CDN:** Use CDN for static assets
- **Background Jobs:** Queue heavy operations

---

## 🤖 Agent Interaction Preferences

### Communication Style
- **Conciseness:** Be brief but complete
- **Transparency:** Explain actions before doing
- **Confirmation:** Ask before major changes
- **Context:** Provide context for decisions

### Workflow Efficiency
- **Batch Operations:** Create multiple issues at once
- **Parallel Reads:** Read multiple files in parallel
- **Logical Commits:** Commit at logical checkpoints
- **Read Before Write:** Always check existing content

### Code Generation
- **Complete Code:** Provide complete, working code
- **No Placeholders:** No "// existing code" comments
- **Tested:** Test code before committing
- **Documented:** Include inline comments for complex logic

---

## 📊 Patterns to Always Follow

### Repository Pattern (Laravel)
```php
interface UserRepositoryInterface {
    public function find(int $id): ?User;
    public function create(array $data): User;
    // etc.
}

class UserRepository implements UserRepositoryInterface {
    // Implementation
}
```

### Service Layer Pattern
```php
class UserService {
    public function __construct(
        private UserRepository $repository
    ) {}
    
    public function createUser(array $data): User {
        // Business logic here
        return $this->repository->create($data);
    }
}
```

### Controller Pattern (Thin)
```php
class UserController {
    public function store(
        StoreUserRequest $request,
        UserService $service
    ): JsonResponse {
        $user = $service->createUser($request->validated());
        return response()->json($user, 201);
    }
}
```

---

## 🔧 Common Code Snippets

### Laravel API Response Format
```php
return response()->json([
    'success' => true,
    'data' => $data,
    'message' => 'Operation successful',
    'errors' => []
], 200);
```

### Eloquent Relationship Pattern
```php
class User extends Model {
    public function posts(): HasMany {
        return $this->hasMany(Post::class);
    }
}
```

---

## 🚫 What NOT to Do

### Never
- ❌ Commit secrets/credentials
- ❌ Push to main without PR (except hotfixes)
- ❌ Skip tests
- ❌ Forget to update documentation
- ❌ Use raw SQL without parameterization
- ❌ Ignore errors/warnings
- ❌ Leave debug code in production
- ❌ Hardcode values that should be configurable

### Avoid
- ⚠️ Large commits (break into smaller ones)
- ⚠️ Vague commit messages
- ⚠️ Premature optimization
- ⚠️ Over-engineering simple solutions
- ⚠️ Too many abstraction layers

---

## 📅 Work Patterns

### Typical Development Day
1. Pull latest from develop
2. Check GitHub project board
3. Pick highest priority issue
4. Move to "In Progress"
5. Create feature branch
6. Implement with commits at checkpoints
7. Update documentation
8. Final commit with "Closes #N"
9. Push and verify auto-close
10. Move to next issue

### Code Review Checklist
- [ ] Code follows style guide
- [ ] Tests added/updated
- [ ] Documentation updated
- [ ] No secrets committed
- [ ] Performance considered
- [ ] Security considered
- [ ] Backwards compatible (if applicable)

---

## 🎓 Learning & Growth

### Continuous Learning
- Stay updated on Laravel releases
- Follow Android/iOS development trends
- Read about new PHP features
- Learn from code reviews

### Resources
- Laravel News
- Android Developers Blog
- Swift.org Blog
- PHP.net Documentation

---

## 📝 Notes to Self

### Common Mistakes to Avoid
1. Forgetting to update PROJECT_DOCUMENTATION.md
2. Not using "Closes #N" in commits
3. Creating issues without estimates
4. Not breaking down phases early enough
5. Forgetting to deploy templates globally

### Personal Reminders
- Always read memory.instruction.md first
- Update memory after completing tasks
- Keep documentation current
- Push after every commit
- Test before pushing

---

## 🔄 Global Memory Maintenance

**Update Frequency:** When establishing new preferences or patterns  
**Last Major Review:** 2026-01-26  
**Next Review:** As needed

---

**Global Memory Ends**  
*Applies to ALL projects*  
*Override with project-specific memory when needed*  
*Version: 1.0*
