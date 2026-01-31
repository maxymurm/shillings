# Shillings API Documentation

## Overview

The Shillings API provides RESTful access to accounting data including companies, accounts, transactions, and financial reports. Authentication is handled via Laravel Sanctum tokens.

**Base URL:** `https://your-domain.com/api`

**Content-Type:** `application/json`

---

## Authentication

### Register

Create a new user account.

```http
POST /auth/register
```

**Request Body:**

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "securepassword",
  "password_confirmation": "securepassword"
}
```

**Response (201):**

```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2026-01-31T12:00:00Z"
  },
  "token": "1|abc123..."
}
```

### Login

Authenticate and receive an API token.

```http
POST /auth/login
```

**Request Body:**

```json
{
  "email": "john@example.com",
  "password": "securepassword"
}
```

**Response (200):**

```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "token": "2|xyz789..."
}
```

### Logout

Revoke the current API token.

```http
POST /auth/logout
Authorization: Bearer {token}
```

**Response (200):**

```json
{
  "message": "Logged out successfully"
}
```

### Get Current User

```http
GET /auth/user
Authorization: Bearer {token}
```

**Response (200):**

```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "created_at": "2026-01-31T12:00:00Z"
}
```

---

## Companies

### List Companies

Get all companies for the authenticated user.

```http
GET /companies
Authorization: Bearer {token}
```

**Response (200):**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Acme Inc",
      "code": "ACME",
      "tax_id": "12-3456789",
      "default_currency": {
        "id": 1,
        "code": "USD",
        "name": "US Dollar"
      },
      "created_at": "2026-01-31T12:00:00Z"
    }
  ]
}
```

### Create Company

```http
POST /companies
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "name": "Acme Inc",
  "code": "ACME",
  "tax_id": "12-3456789",
  "default_currency_id": 1
}
```

### Get Company

```http
GET /companies/{id}
Authorization: Bearer {token}
```

### Update Company

```http
PUT /companies/{id}
Authorization: Bearer {token}
```

### Delete Company

```http
DELETE /companies/{id}
Authorization: Bearer {token}
```

---

## Accounts

### List Accounts

Get all accounts for a company.

```http
GET /companies/{companyId}/accounts
Authorization: Bearer {token}
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `type` | string | Filter by account type (ASSET, LIABILITY, EQUITY, INCOME, EXPENSE) |
| `parent_id` | int | Filter by parent account |
| `include_children` | bool | Include child accounts in tree structure |

**Response (200):**

```json
{
  "data": [
    {
      "id": 1,
      "code": "1000",
      "name": "Assets",
      "description": "All company assets",
      "account_type": {
        "id": 1,
        "name": "ASSET",
        "normal_balance": "DEBIT"
      },
      "currency": {
        "id": 1,
        "code": "USD"
      },
      "parent_id": null,
      "is_placeholder": true,
      "is_hidden": false,
      "balance": "15000.00",
      "children_count": 3
    }
  ]
}
```

### Create Account

```http
POST /companies/{companyId}/accounts
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "code": "1100",
  "name": "Cash",
  "description": "Cash on hand",
  "account_type_id": 1,
  "currency_id": 1,
  "parent_id": 1,
  "is_placeholder": false,
  "is_hidden": false
}
```

### Get Account

```http
GET /companies/{companyId}/accounts/{id}
Authorization: Bearer {token}
```

**Response (200):**

```json
{
  "data": {
    "id": 2,
    "code": "1100",
    "name": "Cash",
    "description": "Cash on hand",
    "account_type": {
      "id": 1,
      "name": "ASSET",
      "normal_balance": "DEBIT"
    },
    "currency": {
      "id": 1,
      "code": "USD",
      "name": "US Dollar"
    },
    "parent": {
      "id": 1,
      "code": "1000",
      "name": "Assets"
    },
    "balance": "5000.00",
    "balance_with_children": "5000.00",
    "splits_count": 25
  }
}
```

### Get Account Balance

```http
GET /companies/{companyId}/accounts/{id}/balance
Authorization: Bearer {token}
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `as_of` | date | Balance as of date (YYYY-MM-DD) |
| `include_children` | bool | Include child account balances |

**Response (200):**

```json
{
  "account_id": 2,
  "balance": "5000.00",
  "balance_with_children": "5000.00",
  "as_of": "2026-01-31",
  "currency": "USD"
}
```

### Update Account

```http
PUT /companies/{companyId}/accounts/{id}
Authorization: Bearer {token}
```

### Delete Account

```http
DELETE /companies/{companyId}/accounts/{id}
Authorization: Bearer {token}
```

> **Note:** Accounts with transactions cannot be deleted.

---

## Transactions

### List Transactions

Get all transactions for a company.

```http
GET /companies/{companyId}/transactions
Authorization: Bearer {token}
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `from_date` | date | Start date filter |
| `to_date` | date | End date filter |
| `account_id` | int | Filter by account |
| `description` | string | Search in description |
| `per_page` | int | Results per page (default: 15) |

**Response (200):**

```json
{
  "data": [
    {
      "id": 1,
      "date": "2026-01-15",
      "num": "INV-001",
      "description": "Client payment received",
      "notes": "Payment for January services",
      "is_reconciled": false,
      "splits": [
        {
          "id": 1,
          "account_id": 2,
          "account_name": "Cash",
          "action": "DEBIT",
          "amount": "1000.00",
          "memo": null
        },
        {
          "id": 2,
          "account_id": 5,
          "account_name": "Accounts Receivable",
          "action": "CREDIT",
          "amount": "1000.00",
          "memo": null
        }
      ],
      "created_at": "2026-01-15T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 72
  }
}
```

### Create Transaction

Create a new double-entry transaction.

```http
POST /companies/{companyId}/transactions
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "date": "2026-01-15",
  "num": "INV-001",
  "description": "Client payment received",
  "notes": "Payment for January services",
  "splits": [
    {
      "account_id": 2,
      "action": "DEBIT",
      "amount": "1000.00",
      "memo": null
    },
    {
      "account_id": 5,
      "action": "CREDIT",
      "amount": "1000.00",
      "memo": null
    }
  ]
}
```

**Response (201):**

```json
{
  "data": {
    "id": 1,
    "date": "2026-01-15",
    "num": "INV-001",
    "description": "Client payment received",
    "splits": [...]
  },
  "message": "Transaction created successfully"
}
```

**Validation Rules:**

- Transaction must have at least 2 splits
- Total debits must equal total credits
- All accounts must belong to the same company
- Date is required

### Get Transaction

```http
GET /companies/{companyId}/transactions/{id}
Authorization: Bearer {token}
```

### Update Transaction

```http
PUT /companies/{companyId}/transactions/{id}
Authorization: Bearer {token}
```

### Delete Transaction

```http
DELETE /companies/{companyId}/transactions/{id}
Authorization: Bearer {token}
```

### Void Transaction

Void a transaction instead of deleting (creates reversing entry).

```http
POST /companies/{companyId}/transactions/{id}/void
Authorization: Bearer {token}
```

**Response (200):**

```json
{
  "original_transaction_id": 1,
  "reversing_transaction_id": 2,
  "message": "Transaction voided successfully"
}
```

---

## Reports

### Trial Balance

```http
GET /companies/{companyId}/reports/trial-balance
Authorization: Bearer {token}
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `as_of` | date | Report date (default: today) |

**Response (200):**

```json
{
  "report": "Trial Balance",
  "as_of": "2026-01-31",
  "accounts": [
    {
      "id": 1,
      "code": "1100",
      "name": "Cash",
      "debit": "15000.00",
      "credit": "0.00"
    },
    {
      "id": 5,
      "code": "2100",
      "name": "Accounts Payable",
      "debit": "0.00",
      "credit": "5000.00"
    }
  ],
  "totals": {
    "debit": "25000.00",
    "credit": "25000.00"
  }
}
```

### Balance Sheet

```http
GET /companies/{companyId}/reports/balance-sheet
Authorization: Bearer {token}
```

### Income Statement

```http
GET /companies/{companyId}/reports/income-statement
Authorization: Bearer {token}
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `from_date` | date | Period start |
| `to_date` | date | Period end |

### Cash Flow Statement

```http
GET /companies/{companyId}/reports/cash-flow
Authorization: Bearer {token}
```

---

## Currencies

### List Currencies

```http
GET /currencies
Authorization: Bearer {token}
```

**Response (200):**

```json
{
  "data": [
    {
      "id": 1,
      "code": "USD",
      "name": "US Dollar",
      "symbol": "$",
      "decimal_places": 2,
      "is_active": true
    },
    {
      "id": 2,
      "code": "EUR",
      "name": "Euro",
      "symbol": "€",
      "decimal_places": 2,
      "is_active": true
    }
  ]
}
```

---

## Error Responses

### 400 Bad Request

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

### 401 Unauthorized

```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden

```json
{
  "message": "This action is unauthorized."
}
```

### 404 Not Found

```json
{
  "message": "Resource not found."
}
```

### 422 Unprocessable Entity

```json
{
  "message": "Transaction validation failed.",
  "errors": {
    "splits": ["Total debits must equal total credits."]
  }
}
```

### 500 Server Error

```json
{
  "message": "Server error. Please try again later."
}
```

---

## Rate Limiting

API requests are rate limited to 60 requests per minute per user.

**Rate Limit Headers:**

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1706745600
```

---

## Pagination

All list endpoints support pagination:

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Page number |
| `per_page` | int | 15 | Results per page (max: 100) |

**Response Metadata:**

```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  },
  "links": {
    "first": "https://api.example.com/resource?page=1",
    "last": "https://api.example.com/resource?page=10",
    "prev": null,
    "next": "https://api.example.com/resource?page=2"
  }
}
```

---

## SDKs & Examples

### cURL

```bash
# Get all accounts
curl -X GET "https://your-domain.com/api/companies/1/accounts" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### JavaScript (Fetch)

```javascript
const response = await fetch('https://your-domain.com/api/companies/1/accounts', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
  },
});
const data = await response.json();
```

### PHP (Guzzle)

```php
$client = new \GuzzleHttp\Client();
$response = $client->request('GET', 'https://your-domain.com/api/companies/1/accounts', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
    ],
]);
$data = json_decode($response->getBody(), true);
```
