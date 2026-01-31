# Contributing to Shillings

Thank you for your interest in contributing to Shillings! This document provides guidelines and information for contributors.

## Code of Conduct

By participating in this project, you agree to abide by our code of conduct: be respectful, inclusive, and professional.

## Getting Started

### Prerequisites

- PHP 8.3+
- Composer 2.x
- Node.js 20+
- Git
- SQLite (for local development)

### Development Setup

1. **Fork and Clone**
   ```bash
   git clone https://github.com/YOUR_USERNAME/shillings.git
   cd shillings
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Setup Database**
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   ```

5. **Create Test User**
   ```bash
   php artisan make:filament-user
   ```

6. **Start Development**
   ```bash
   php artisan serve
   npm run dev
   ```

## Development Workflow

### Branch Strategy

- `main` - Production-ready code
- `develop` - Integration branch for features
- `feature/*` - Feature development
- `bugfix/*` - Bug fixes
- `hotfix/*` - Production hotfixes

### Creating a Feature

1. **Create Branch**
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feature/your-feature-name
   ```

2. **Make Changes**
   - Write clean, readable code
   - Follow existing code patterns
   - Add tests for new functionality

3. **Test Your Changes**
   ```bash
   php artisan test
   vendor/bin/pint
   vendor/bin/phpstan analyse
   ```

4. **Commit Your Changes**
   ```bash
   git add .
   git commit -m "feat: add your feature description"
   ```

5. **Push and Create PR**
   ```bash
   git push origin feature/your-feature-name
   ```

### Commit Message Convention

We follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

**Types:**
- `feat` - New feature
- `fix` - Bug fix
- `docs` - Documentation changes
- `style` - Code style changes (formatting, etc.)
- `refactor` - Code refactoring
- `test` - Adding or updating tests
- `chore` - Maintenance tasks

**Examples:**
```
feat(accounts): add account balance calculation
fix(transactions): resolve split rounding error
docs: update API documentation
test(models): add company model tests
```

## Code Standards

### PHP Style Guide

We use Laravel Pint with the default Laravel preset:

```bash
# Check code style
vendor/bin/pint --test

# Fix code style
vendor/bin/pint
```

### Key Conventions

1. **Use Type Hints**
   ```php
   public function calculateBalance(Account $account, ?Carbon $asOf = null): Money
   ```

2. **Use Early Returns**
   ```php
   if (! $account->exists) {
       return null;
   }
   ```

3. **Use Named Arguments for Clarity**
   ```php
   Transaction::create(
       date: now(),
       description: 'Payment received',
   );
   ```

4. **Document Complex Logic**
   ```php
   /**
    * Calculate account balance using GnuCash fraction arithmetic.
    *
    * @param Account $account The account to calculate
    * @param Carbon|null $asOf Optional date for historical balance
    * @return Money The calculated balance
    */
   ```

### Database Conventions

1. **Use Migrations for All Schema Changes**
2. **Foreign Keys Should Use `constrained()`**
   ```php
   $table->foreignId('company_id')->constrained()->cascadeOnDelete();
   ```
3. **Use UUIDs for GnuCash Compatibility** (future consideration)

### Testing Standards

1. **Test File Location**
   - Unit tests: `tests/Unit/`
   - Feature tests: `tests/Feature/`
   - API tests: `tests/Feature/Api/`

2. **Test Naming**
   ```php
   public function test_it_calculates_account_balance(): void
   // or
   /** @test */
   public function it_calculates_account_balance(): void
   ```

3. **Use Factories**
   ```php
   $account = Account::factory()
       ->for(Company::factory())
       ->create();
   ```

## Architecture Guidelines

### Service Layer

Business logic should be in service classes:

```php
// app/Services/AccountService.php
class AccountService
{
    public function getBalance(Account $account): Money
    {
        // Complex balance calculation
    }
}
```

### Controllers Should Be Thin

```php
// Good
public function show(Account $account)
{
    return new AccountResource($account);
}

// Avoid - move logic to service
public function show(Account $account)
{
    $balance = $account->splits->sum(...); // Move to service
    // ...
}
```

### Use Form Requests

```php
// app/Http/Requests/StoreTransactionRequest.php
class StoreTransactionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'splits' => ['required', 'array', 'min:2'],
            // ...
        ];
    }
}
```

### Use Resources for API Responses

```php
// app/Http/Resources/AccountResource.php
class AccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'balance' => $this->balance,
        ];
    }
}
```

## Filament Resources

When creating Filament resources:

1. **Use Resource Classes**
   ```bash
   php artisan make:filament-resource Account --generate
   ```

2. **Follow Naming Conventions**
   - Resource: `AccountResource`
   - Pages: `ListAccounts`, `CreateAccount`, `EditAccount`

3. **Add Proper Relationships**
   ```php
   public static function getRelations(): array
   {
       return [
           SplitsRelationManager::class,
       ];
   }
   ```

## Pull Request Guidelines

### Before Submitting

- [ ] Tests pass: `php artisan test`
- [ ] Code style passes: `vendor/bin/pint --test`
- [ ] Static analysis passes: `vendor/bin/phpstan analyse`
- [ ] Documentation updated if needed
- [ ] Commit messages follow convention

### PR Description Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- How was this tested?
- Any specific testing instructions?

## Screenshots (if applicable)
Add screenshots for UI changes

## Checklist
- [ ] Tests added/updated
- [ ] Documentation updated
- [ ] Code follows style guidelines
```

### Review Process

1. At least one approval required
2. All CI checks must pass
3. No merge conflicts
4. Squash commits when merging

## Reporting Issues

### Bug Reports

Include:
- Clear description of the bug
- Steps to reproduce
- Expected vs actual behavior
- Environment details (PHP version, OS, etc.)
- Screenshots if applicable

### Feature Requests

Include:
- Clear description of the feature
- Use case / why it's needed
- Proposed implementation (optional)
- Mockups/wireframes (optional)

## Questions?

- Open an issue with the `question` label
- Check existing issues and documentation first

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
