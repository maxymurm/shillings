---
applyTo: '**'
lastUpdated: '2026-01-30 18:00:00'
chatSession: 'session-002'
projectName: 'Shillings - Offline-First Accounting'
---

# Project Memory - Shillings

> **AGENT INSTRUCTIONS:** Always read this file FIRST before starting any new conversation. Update after completing tasks, making decisions, or when user says "remember this".

---

## 🔗 Important Links (REMEMBER THESE)

- **GitHub Repo:** https://github.com/maxymurm/shillings
- **Project Board:** https://github.com/users/maxymurm/projects/4
- **Milestones:** https://github.com/maxymurm/shillings/milestones
- **Issues:** https://github.com/maxymurm/shillings/issues
- **Developer:** Maxwell Murunga (@maxymurm)
- **Company:** Advent Digital

---

## 🎯 Current Focus

**Active Phase:** Phase 1 - Foundation & Core Accounting  
**Active Milestone:** Phase 1: Database Schema & Core Models  
**Current Branch:** main  
**Last Activity:** 2026-01-30 - Created 15 Phase 1 issues

**GitHub Milestones (exact names):**
1. Phase 1: Database Schema & Core Models
2. Phase 2: Double-Entry Logic
3. Phase 3: Financial Reports & Queries
4. Phase 4: Admin Panel - Accounts
5. Phase 5: Transactions & Reconciliation
6. Phase 6: Reports & Charts
7. Phase 7: Testing & QA
8. Phase 8: Deployment & Documentation

---

## ✅ Completed Tasks (This Session)

1. ✅ Analyzed Akaunting codebase for feature porting
2. ✅ Analyzed GnuCash codebase for accounting model
3. ✅ Created feature comparison matrix
4. ✅ Designed offline-first sync architecture
5. ✅ Defined 8 phases with detailed milestones
6. ✅ Created 80+ issues backlog for Phase 1-2
7. ✅ Created issue automation scripts
8. ✅ Updated PROJECT_DOCUMENTATION.md
9. ✅ Created 15 Phase 1 GitHub issues
10. ✅ Created 44 Phase 2-8 GitHub issues (59 total new issues)
11. ✅ All issues synced to Project Board #4

**Issue Summary by Milestone:**
| Milestone | Issues |
|-----------|--------|
| Phase 1: Database Schema & Core Models | 15 |
| Phase 2: Double-Entry Logic | 7 |
| Phase 3: Financial Reports & Queries | 7 |
| Phase 4: Admin Panel - Accounts | 6 |
| Phase 5: Transactions & Reconciliation | 6 |
| Phase 6: Reports & Charts | 6 |
| Phase 7: Testing & QA | 6 |
| Phase 8: Deployment & Documentation | 6 |
| **Total** | **59** |

---

## 📁 Key Documentation Files

| File | Purpose |
|------|---------|
| docs/PROJECT_DOCUMENTATION.md | Main project documentation |
| docs/planning/AKAUNTING_ANALYSIS.md | Akaunting feature analysis |
| docs/planning/GNUCASH_ANALYSIS.md | GnuCash architecture analysis |
| docs/planning/FEATURE_COMPARISON.md | Feature comparison matrix |
| docs/planning/PHASES_AND_MILESTONES.md | Phase breakdown |
| docs/planning/ISSUES_BACKLOG.md | Full issues backlog |
| docs/architecture/OFFLINE_FIRST_ARCHITECTURE.md | Sync architecture |
| agents/create_phase1_issues.ps1 | Phase 1 issue creation |
| agents/create_remaining_issues.ps1 | Phase 2-8 issue creation |

---

## 🛠️ Technology Stack

- **Backend:** Laravel 12 + PHP 8.3
- **Admin Panel:** Filament 4.3
- **Database:** PostgreSQL 16
- **Mobile:** Compose Multiplatform (iOS/Android)
- **Offline Web:** IndexedDB + Service Workers (PWA)
- **API:** REST with Laravel Sanctum
- **Deployment:** Cloud-hosted (Laravel Forge/Vapor)

---

## 🏗️ Architecture Decisions

### Accounting Model
- **Source:** GnuCash-style split-based double-entry
- **Precision:** numerator/denominator fractions (not float)
- **Primary Keys:** UUIDs for offline-first
- **Multi-tenancy:** company_id scoping
- **Soft Deletes:** All core models

### Sync Strategy
- **Pattern:** Bidirectional with version vectors
- **Conflict:** Last-Writer-Wins with smart merge
- **Offline Auth:** Encrypted cached credentials (30-day)

---

## 📋 Next Steps

1. Begin Phase 1 development
2. Start with Issue #6: Initialize Laravel 12 Project
3. Work through issues in order (1.1 → 1.2 → 1.3 → etc.)

---

## 👤 User Preferences

- Solo developer workflow
- Cloud-only deployment (no Docker/K8s)
- Offline can wait until Phase 2
- Multi-company support required in Phase 1
- Mobile as subset of web features initially

---

## 🔧 Scripts & Tools

**Issue Creation:**
```powershell
cd c:\Users\maxmm\Herd\shillings\agents
.\create_phase1_issues.ps1
```

**GitHub CLI Status:**
```powershell
gh auth status  # Logged in as maxymurm
```

---

**Memory Updated:** 2026-01-30 18:00  
*This file is the source of truth for continuing work*
