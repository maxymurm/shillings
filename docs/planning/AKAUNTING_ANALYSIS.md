# Akaunting Application Analysis

**Generated:** January 30, 2026  
**Purpose:** Comprehensive analysis of Akaunting for porting to Shillings application  
**Version:** Akaunting 3.x (Laravel 10-based)

---

## 📊 Overview

Akaunting is an online accounting software designed for small businesses and freelancers. It's built with:
- **Laravel 10** (PHP 8.1+)
- **Livewire 3** for reactive components
- **VueJS** for frontend interactivity
- **Tailwind CSS** for styling
- **RESTful API** architecture
- **Modular structure** with App Store for extensions

---

## 🏗️ Core Architecture

### Database Models Structure

Akaunting uses a well-organized model structure:

#### Banking Models
- **Account** - Bank accounts with multi-currency support
  - Fields: name, number, currency_code, opening_balance, bank details
  - Relationships: transactions, transfers, reconciliations
  - Computed: balance, title, initials
  
- **Transaction** - Income and expense transactions
  - Types: income, expense, transfer, split, recurring
  - Fields: type, number, account_id, amount, currency, contact, category
  - Features: Media attachments, recurring support, split transactions
  
- **Transfer** - Money transfers between accounts
  - Handles multi-currency transfers with rates
  
- **Reconciliation** - Account reconciliation tracking
  - Tracks reconciled vs unreconciled transactions

#### Document Models
- **Document** - Invoices and bills (unified model)
  - Types: invoice, invoice-recurring, bill, bill-recurring
  - Fields: document_number, status, issued_at, due_at, amount, currency
  - Features: Tax calculation, discounts, partial payments
  
- **DocumentItem** - Line items for documents
  - Fields: item_id, name, quantity, price, tax
  
- **DocumentTotal** - Total calculations (subtotal, tax, discount, total)
  
- **DocumentHistory** - Status change tracking
  - Tracks: sent, viewed, approved, received, paid, cancelled

#### Common Models
- **Contact** - Customers and vendors (unified)
  - Types: customer, vendor
  - Fields: name, email, phone, address, tax_number, currency
  - Features: Multiple contact persons, custom fields
  
- **Item** - Products and services
  - Fields: name, description, sale_price, purchase_price, category
  - Features: Inventory tracking, tax rates
  
- **Company** - Multi-company support
  - Each company has separate books
  
- **Dashboard** - Customizable dashboards with widgets
  
- **Report** - Financial reports storage and generation

#### Setting Models
- **Currency** - Multi-currency with exchange rates
  - Auto-update rates via API
  
- **Tax** - Tax rates and types
  - Support for compound taxes
  
- **Category** - Hierarchical categories for transactions and items
  - Types: income, expense, item, other
  
- **EmailTemplate** - Customizable email templates
  
- **Setting** - Key-value configuration storage

### Module System

Akaunting has a powerful module/app system:
- **Module Model** - Tracks installed apps/modules
- **ModuleHistory** - Installation/update history
- Module architecture allows extending core functionality
- App Store integration for discovering modules

---

## 💼 Key Features

### 1. Multi-Company Support
- Complete data isolation per company
- Switch between companies seamlessly
- Separate settings, currencies, and users per company

### 2. Multi-Currency
- Unlimited currencies with live exchange rates
- Currency conversion on transactions
- Display amounts in base and transaction currencies

### 3. Double-Entry Accounting
- All transactions follow double-entry principles
- Automatic journal entries
- Complete audit trail

### 4. Banking
- Multiple bank accounts
- Bank reconciliation
- Import bank statements
- Transfer between accounts
- Split transactions

### 5. Sales Management
- Create invoices
- Recurring invoices
- Partial payments tracking
- Multiple payment methods
- Invoice templates

### 6. Purchase Management
- Vendor bills
- Recurring bills
- Expense tracking
- Purchase orders

### 7. Reporting
- Income statement (P&L)
- Balance sheet
- Cash flow statement
- Tax summary
- Custom reports

### 8. Inventory
- Item/product management
- Track stock levels
- Cost of goods sold

### 9. Tax Management
- Multiple tax rates
- Compound taxes
- Tax reports
- Automatic tax calculations

### 10. Automation
- Recurring transactions
- Recurring invoices/bills
- Scheduled reports
- Email notifications

---

## 🔌 API Structure

### Controllers Organization

Akaunting has well-organized controllers:

#### Auth Controllers
- Login, Register, Reset Password
- Two-factor authentication
- Role-based access control (RBAC)

#### Banking Controllers
- `Accounts` - Account CRUD
- `Transactions` - Transaction management
- `Transfers` - Transfer operations
- `Reconciliations` - Reconciliation workflow

#### Sales Controllers
- `Invoices` - Invoice CRUD and operations
- `Revenues` - Revenue tracking (old name for income transactions)
- `Customers` - Customer management
- `RecurringInvoices` - Recurring invoice management

#### Purchases Controllers
- `Bills` - Bill CRUD and operations
- `Payments` - Payment tracking (old name for expense transactions)
- `Vendors` - Vendor management
- `RecurringBills` - Recurring bill management

#### Common Controllers
- `Items` - Product/service management
- `Reports` - Report generation
- `Dashboards` - Dashboard management
- `Import` - CSV/Excel import
- `Export` - Export functionality

#### Settings Controllers
- `Company` - Company settings
- `Currencies` - Currency management
- `Taxes` - Tax configuration
- `Categories` - Category management
- `EmailTemplates` - Template customization
- `Modules` - App/module management
- `Defaults` - Default settings
- `Localisation` - Language and region
- `Schedule` - Scheduled tasks

### RESTful API

Akaunting provides a complete REST API:
- API authentication via Laravel Sanctum
- Rate limiting
- Versioned API (v1)
- Complete CRUD for all resources
- Webhook support
- API documentation available

---

## 🎨 Frontend Architecture

### Technologies
- **Livewire 3** - For reactive server-rendered components
- **VueJS** - For complex interactive components
- **Tailwind CSS** - Utility-first styling
- **AlpineJS** - Lightweight JS framework (via Livewire)
- **ApexCharts** - Interactive charts and graphs

### UI Components
- Responsive tables with sorting/filtering
- Modal dialogs
- Dropdown menus
- Date pickers
- File uploads with drag-drop
- WYSIWYG editors
- Autocomplete inputs

### Dashboard
- Customizable widget-based dashboard
- Real-time updates
- Charts: income/expense, profit/loss, cash flow
- Latest transactions
- Unpaid invoices
- Overdue bills

---

## 📦 Database Schema Highlights

### Key Tables
- `companies` - Multi-tenancy
- `users` - User accounts with RBAC
- `accounts` - Bank accounts
- `transactions` - All income/expense transactions
- `transfers` - Inter-account transfers
- `documents` - Invoices and bills
- `document_items` - Document line items
- `document_totals` - Document totals breakdown
- `document_histories` - Document status changes
- `contacts` - Customers and vendors
- `items` - Products and services
- `categories` - Hierarchical categories
- `currencies` - Multi-currency support
- `taxes` - Tax rates and rules
- `settings` - Configuration key-value pairs
- `media` - File attachments
- `recurring` - Recurring transaction patterns
- `reports` - Saved reports
- `dashboards` - Dashboard configurations
- `widgets` - Dashboard widgets
- `modules` - Installed apps/modules
- `permissions` - RBAC permissions
- `roles` - User roles

### Relationships
- Polymorphic relationships for attachments, categories
- Soft deletes on most models
- Audit trail with created_by, updated_by
- Company_id on all models for multi-tenancy

---

## 🔐 Security Features

1. **Authentication**
   - Laravel's built-in authentication
   - Two-factor authentication support
   - Session management
   - Remember me functionality

2. **Authorization**
   - Role-based access control (RBAC)
   - Permission-based authorization
   - Company-level isolation
   - API token authentication

3. **Data Protection**
   - CSRF protection
   - XSS prevention
   - SQL injection prevention (via Eloquent)
   - Input validation
   - Output sanitization

4. **Audit Trail**
   - Track who created/updated records
   - Document history tracking
   - Activity logs
   - Change logs

---

## 📱 Mobile Responsiveness

- Fully responsive design
- Touch-friendly interfaces
- Mobile-optimized tables
- Responsive navigation
- Works well on tablets and phones

**Note:** Currently web-based only, no native mobile app

---

## 🔄 Import/Export Capabilities

### Import
- CSV import for:
  - Customers
  - Vendors
  - Items
  - Accounts
  - Transactions
- Mapping interface for CSV columns
- Validation and error reporting
- Batch processing

### Export
- Export to:
  - PDF (invoices, bills, reports)
  - Excel/CSV (all data)
  - Print-friendly formats
- Customizable templates
- Bulk export functionality

---

## 🧩 Module/App System

### Core Architecture
- Laravel package-based modules
- Auto-discovery of modules
- Module-specific routes, views, models
- Database migrations per module
- Event system for module integration

### Popular Modules
- Payment gateways (Stripe, PayPal, etc.)
- Inventory advanced
- Projects & time tracking
- CRM integration
- Double-entry accounting enhancements
- Bank feed integration
- Advanced reports
- Payroll

---

## 🚀 Performance Features

1. **Caching**
   - Model caching
   - Query result caching
   - View caching
   - Configuration caching

2. **Optimization**
   - Eager loading relationships
   - Database indexing
   - Query optimization
   - Asset minification

3. **Queue System**
   - Background job processing
   - Email sending via queue
   - Report generation in background
   - Large imports/exports queued

---

## 📊 Reporting Capabilities

### Standard Reports
1. **Profit & Loss** (Income Statement)
   - Revenue vs Expenses
   - Net profit/loss
   - Comparison periods

2. **Balance Sheet**
   - Assets, Liabilities, Equity
   - Current vs previous periods

3. **Cash Flow Statement**
   - Operating, investing, financing activities
   - Net cash flow

4. **Tax Summary**
   - Tax collected
   - Tax paid
   - Net tax liability

5. **Income by Category**
   - Revenue breakdown
   - Comparison and trends

6. **Expense by Category**
   - Expense breakdown
   - Budget comparison

7. **Income vs Expense**
   - Monthly comparison
   - Profit margin trends

### Report Features
- Date range selection
- Comparison periods
- Export to PDF/Excel
- Chart visualizations
- Drill-down capabilities
- Custom report builder (via modules)

---

## 💡 Best Practices Used

1. **Code Organization**
   - Service layer for business logic
   - Repository pattern for data access
   - Form requests for validation
   - Resource classes for API responses
   - Events and listeners for decoupling

2. **Testing**
   - Feature tests
   - Unit tests
   - Browser tests (Dusk)
   - API tests
   - Test database seeding

3. **Documentation**
   - Inline code documentation
   - API documentation
   - User guides
   - Developer documentation
   - Wiki for community

---

## 🎯 Key Learnings for Shillings

### Must-Have Features
1. Multi-company/multi-tenant architecture
2. Multi-currency with live rates
3. Double-entry accounting core
4. Unified document model (invoices/bills)
5. Flexible transaction system
6. Module/plugin architecture
7. Comprehensive reporting
8. Bank reconciliation
9. Recurring transactions
10. Media attachment support

### Architecture Patterns
1. Polymorphic relationships for flexibility
2. Soft deletes for audit trail
3. Observer pattern for automation
4. Event-driven architecture
5. Service layer for complex operations
6. API-first design
7. Repository pattern for data access

### Database Design
1. company_id on all tables for isolation
2. type fields for model variations
3. Separate totals table for documents
4. History tables for tracking
5. Settings as key-value pairs
6. Polymorphic media table

---

## 📋 Features NOT in Akaunting (Opportunities)

1. **Native mobile apps** (web-only currently)
2. **Offline functionality** (requires internet)
3. **Real-time collaboration** (limited multi-user)
4. **Advanced inventory** (basic only in core)
5. **Manufacturing features** (not available)
6. **Advanced CRM** (basic contacts only)
7. **Project management** (requires module)
8. **Time tracking** (requires module)
9. **Employee management** (requires module)
10. **Payroll** (requires module)

These are opportunities for Shillings to differentiate!

---

## 🔄 Migration Considerations

### Data to Migrate
1. Chart of accounts
2. Customers and vendors
3. Items/products
4. Historical transactions
5. Invoices and bills
6. Bank accounts
7. Tax rates and rules
8. Categories
9. User accounts and permissions
10. Company settings

### Migration Challenges
1. Data format differences
2. Custom field mapping
3. Relationship preservation
4. Balance verification
5. Transaction history integrity
6. Document numbering continuity
7. Multi-currency historical rates

---

**End of Akaunting Analysis**  
**Next:** GnuCash Analysis → Feature Comparison Matrix → Architecture Design
