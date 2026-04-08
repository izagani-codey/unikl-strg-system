# UniKL STRG Request System

A Laravel-based workflow system for managing STRG-related requests across multiple organizational roles, with structured approvals, audit tracking, and PDF generation.

---

## 🧠 System Overview

This system implements a **multi-stage approval workflow** designed for internal university use.

### Workflow Lifecycle

```
Admission → Staff 1 → Staff 2 → Dean → Completed
```

* **Admission** submits and revises requests
* **Staff 1** verifies request details
* **Staff 2** recommends and prepares finalization
* **Dean** performs final approval (optional via feature toggle)

Each stage:

* updates request status
* records internal notes
* captures signatures
* contributes to final PDF output

---

## ⚙️ Core Features

### 📌 Workflow & Request Management

* Role-based dashboards (Admission, Staff, Dean)
* Structured request lifecycle with enforced transitions
* Revision flow for returned requests
* Status tracking with notes and rejection reasons

### 📄 Dynamic Request System

* Request types with configurable field schemas
* Dynamic form rendering based on selected request type
* Template-based document uploads (per request type)

### ✍️ Signature & PDF System

* Digital signature capture (multi-role)
* Auto-generated PDFs with:

  * applicant data
  * dynamic fields
  * VOT/budget breakdown
  * stage-based signatures

### 🧾 Audit & Tracking

* Internal comments per request
* Full audit trail of actions and transitions
* Timestamped activity logging

---

## 🧩 Template-Friendly Customization

This project is designed as a reusable internal system template.

### Environment Configuration

```env
SYSTEM_ORGANIZATION="Your Organization"
SYSTEM_PRODUCT_NAME="Request System"
SYSTEM_REQUEST_LABEL="Request"
```

### Feature Toggles

```env
FEATURE_DEAN_INTERFACE=true|false
```

* Enable/disable dean workflow without code changes

---

## 🛠 Tech Stack

* **Backend:** PHP 8.3+, Laravel 13
* **Frontend:** Blade + Vite
* **Database:** SQLite / MySQL (configurable)

---

## 🚀 Quick Start

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Run migrations and seeders

```bash
php artisan migrate --seed
```

### 4. Start development server

```bash
composer run dev
```

---

## 👤 Demo Accounts

All accounts use password: `password`

* [admissions@unikl.edu.my](mailto:admissions@unikl.edu.my)
* [staff1@unikl.edu.my](mailto:staff1@unikl.edu.my)
* [staff2@unikl.edu.my](mailto:staff2@unikl.edu.my)
* [dean@unikl.edu.my](mailto:dean@unikl.edu.my)

---

## 🧪 Testing & Quality

### Run tests

```bash
php artisan test
```

### Format code

```bash
./vendor/bin/pint
```

---

## 🧰 System Diagnostics (Recommended Before Deployment)

### Run QA checks

```bash
bash scripts/qa_check.sh
```

### Run production readiness checks

```bash
php scripts/production-readiness-check.php
```

---

## 🧯 Troubleshooting

### Missing dependencies

```bash
composer install
```

### Missing environment file

```bash
cp .env.example .env
php artisan key:generate
```

### Database issues

```bash
php artisan migrate --seed
```

---

## 📋 Request Form Health Checklist

If form features fail (signature pad, dynamic fields, templates):

* Ensure `@stack('scripts')` is present in layout
* Hard refresh browser (Ctrl + Shift + R)
* Check browser console for JS errors
* Ensure request type has configured schema/template
* Submit with:

  * valid VOT entries
  * completed required fields
  * signature provided

---

## 🏗 Architecture Notes

* Workflow transitions are centralized via a service layer
* Status changes follow a controlled lifecycle
* PDF generation is tied to request state and signatures
* Designed for **internal workflow efficiency over legal compliance**

---

## 🚧 Improvement Roadmap

### 🔴 High Priority

* Atomic reference number generation (prevent collisions)
* Remove duplicate migrations
* Queue notifications and PDF generation

### 🟡 Medium Priority

* Introduce workflow state machine abstraction
* Add database indexing for performance
* Improve dashboard query optimization

### 🟢 Long-Term

* Parallel approval support
* Versioned PDF artifacts
* Enhanced audit visualization (timeline UI)

---

## 🔐 Security Notes

* Developer quick-switch login route exists for local development
* MUST be disabled in production environments

---

## 📌 Notes

This system is designed for **internal institutional use**, prioritizing:

* workflow clarity
* maintainability
* operational efficiency

It is **not intended as a legally binding digital signature system**.

---
