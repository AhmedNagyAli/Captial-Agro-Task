# Modular Configuration System

A modular configuration engine built with Laravel (API) and Vue 3 + Inertia.

---

## Overview

This system provides a generic, extensible architecture for building configurable products or services.

While the current implementation demonstrates a PC builder, the system is designed to support:

* Product configurators
* Food customization workflows
* Service bundles
* Any structured multi-option selection system

The focus is on deterministic behavior, strict data integrity, and predictable API design.

---

## Core Concept

A configuration represents a session where a user selects options across multiple groups.

### Entities

* **Configuration** → user session / build
* **OptionGroup** → category (e.g. CPU, RAM)
* **Option** → selectable item
* **ConfigurationItem** → immutable snapshot of selection

---

## Architecture

### Backend (Laravel)

* Service layer encapsulates business logic
* Controllers remain thin and deterministic
* Strict API response contract
* MySQL with enforced foreign keys

### Frontend (Vue 3 + Inertia)

* Reactive state with controlled hydration
* LocalStorage persistence
* Stateless API communication
* Sidebar-based interaction model

---

## Database Design

Based on full system structure and relationships 

---

### Core Tables

#### configurations

* `id` (BIGINT UNSIGNED)
* `session_id`
* `token`
* `total_price`
* `order_id`
* timestamps

---

#### configuration_items

* `id`
* `configuration_id` (FK)
* `option_group_id` (FK)
* `option_id` (FK)

Snapshot fields:

* `option_group_name`
* `option_name`
* `price`

Other:

* `quantity`
* timestamps

Constraint:

* unique (`configuration_id`, `option_group_id`, `option_id`)

---

#### option_groups

* `id`
* `name`
* `type`
* `min_selections`
* `max_selections`
* `is_required`
* `sort_order`
* `validation_rules`
* timestamps

---

#### options

* `id`
* `option_group_id`
* `name`
* `price_type`
* `render_type`
* `price_value`
* `sku`
* `stock`
* `metadata`
* `is_active`
* `sort_order`
* timestamps

---

## Configuration Rules

### option_dependencies

Defines required relationships between options.

### option_exclusions

Defines mutually exclusive options.

---

## Product Layer

#### products

* `id`
* `name`

#### product_option_group

* `product_id`
* `option_group_id`
* pricing and metadata fields

---

## Order System

#### orders

* `order_number`
* `subtotal`
* `discount`
* `total`
* `coupon_id`
* `status`

#### order_items

Stores snapshot of purchased configuration.

---

## Coupon System

#### coupons

* `code`
* `type`
* `value`
* limits and expiration

#### coupon_usages

Tracks usage per order.

---

## System Tables

* users
* sessions
* cache / cache_locks
* jobs / failed_jobs / job_batches
* migrations
* password_reset_tokens

---

## Relationships

* Configuration → hasMany → ConfigurationItem
* OptionGroup → hasMany → Option
* ConfigurationItem → belongsTo → Option / OptionGroup
* Product → belongsToMany → OptionGroup
* Configuration → transforms into → Order

---

## Constraints

* All IDs must be `BIGINT UNSIGNED`
* Foreign keys must match types exactly
* Storage engine: InnoDB
* Migration order must respect dependencies

---

## API

### Create Configuration

```
POST /api/configurations
```

```json
{
  "data": { "id": 1 }
}
```

---

### Show Configuration

```
GET /api/configurations/{id}
```

---

### Select Option

```
POST /api/configurations/{id}/select
```

```json
{
  "option_id": 5,
  "quantity": 1
}
```

---

### Clear Configuration

```
POST /api/configurations/{id}/clear
```

---

### Summary

```
GET /api/configurations/{id}/summary
```

---

## API Response Contract

### Success

```json
{ "data": { ... } }
```

### Validation Error

```json
{ "error": "Validation failed", "details": { ... } }
```

### Not Found

```json
{ "error": "Resource not found" }
```

### Server Error

```json
{ "error": "Server error" }
```

---

## Testing

The system includes both feature and unit tests.

### Feature Tests (Catalog API)

* Retrieve all option groups
* Retrieve all options
* Retrieve options by group
* Handle non-existent group

### Feature Tests (Configuration API)

* Create configuration
* Retrieve configuration
* Handle missing configuration
* Select option
* Validate inputs
* Remove option
* Clear configuration
* Retrieve summary

### Unit Tests (Configuration Service)

* Create configuration
* Select option
* Replace option in same group
* Clear configuration
* Calculate total price
* Verify snapshot integrity

---

## Caching

### Backend

```php
Cache::remember('builder.catalog', 3600, function () {
    return OptionGroup::with('options')
        ->ordered()
        ->get()
        ->toArray();
});
```

Rules:

* Cache arrays only
* Never cache Eloquent models

---

### Frontend

LocalStorage keys:

* `pc_builder_selections`
* `pc_builder_visited`

---

## Frontend Lifecycle

### First Visit

* Create configuration
* Load catalog
* Initialize empty state

### Returning Visit

* Restore selections
* Rebuild configuration via API
* Sync totals

---

## Known Issues & Fixes

### Foreign Key Error (MySQL)

Cause:

* Migration order
* Type mismatch

Fix:

* Enforce BIGINT
* Ensure proper migration sequence

---

### Cache Returning Empty Data

Cause:

* Caching Eloquent models

Fix:

* Cache arrays using `toArray()`

---

### Empty UI on First Load

Cause:

* Inertia hydration timing

Fix:

* Sync props into reactive state

---

## Performance Decisions

* Eliminated redundant queries
* Removed unnecessary abstraction
* Used route model binding
* Cached read-heavy data only

---

## Controller Rules

* No business logic
* No raw exceptions in responses
* Consistent response structure

---

## Setup

```bash
git clone https://github.com/AhmedNagyAli/Captial-Agro-Task.git
Cd Captial-Agro-Task

composer install
npm install

cp .env.example .env
php artisan key:generate

php artisan migrate:fresh --seed

php artisan serve
npm run dev
```

---

## Cache Reset

```bash
php artisan optimize:clear
```

---



## Principles

* Stateless backend
* Predictable API
* Explicit data flow
* No hidden behavior
* Data integrity over convenience

---

## Conclusion

This is not a PC builder.

It is a reusable configuration engine capable of supporting any structured selection system.

The UI is replaceable.
The architecture is the core value.
