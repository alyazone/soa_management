# SOA Management System

A custom-built business management system developed for **KYROL Security Labs** to handle Statements of Account, client/supplier relationships, staff claims and mileage reimbursement, document management, inventory tracking, and outstation leave applications.

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Installation & Setup](#installation--setup)
- [Modules](#modules)
  - [Authentication](#authentication)
  - [Dashboard](#dashboard)
  - [Client Management](#client-management)
  - [Supplier Management](#supplier-management)
  - [Staff Management](#staff-management)
  - [Client SOA](#client-statement-of-account)
  - [Supplier SOA](#supplier-statement-of-account)
  - [Claims & Mileage Reimbursement](#claims--mileage-reimbursement)
  - [Document Management](#document-management)
  - [Inventory Management](#inventory-management)
  - [Outstation Leave](#outstation-leave)
- [Role-Based Access Control](#role-based-access-control)
- [Security](#security)

---

## Features

- **Dual SOA System** -- Separate workflows for tracking client invoices (revenue) and supplier invoices (expenses), each with PDF generation.
- **Client & Supplier Management** -- Full CRUD operations for managing business contacts with Person In Charge (PIC) details.
- **Staff Claims & Mileage Reimbursement** -- Multi-entry travel claims with position-based and vehicle-based tiered mileage rates, meal tracking, parking/toll fees, and manager approval workflow.
- **Inventory Tracking** -- Asset management with category organization, status lifecycle (Available, Assigned, Maintenance, Disposed), transaction history, and maintenance records.
- **Document Management** -- File upload system supporting multiple formats, linked to claims, SOAs, and other records.
- **Outstation Leave** -- Leave application system with automatic claim eligibility calculation, approval workflow, and analytics dashboard.
- **Executive Dashboard** -- Financial overview with charts showing revenue, expenses, claims, net balance, and monthly trends.
- **Role-Based Access Control** -- Three-tier permission system (Admin, Manager, Staff) restricting access to features based on user role.
- **PDF Generation** -- Downloadable PDF invoices and documents for client SOAs, supplier SOAs, and outstation applications.

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP (procedural) |
| Database | MySQL with PDO prepared statements |
| Frontend | HTML5, Bootstrap 4.5.2, Tailwind CSS |
| JavaScript | jQuery 3.5.1, DataTables, Chart.js 3.9.1 |
| Icons | Font Awesome 6.4.0 |
| Fonts | Google Fonts (Poppins) |
| PDF | FPDF library |
| Animations | AOS (Animate On Scroll) |

---

## Project Structure

```
soa_management/
├── assets/
│   ├── css/          # Custom stylesheets (style.css, dashboard.css, modern-dashboard.css)
│   ├── js/           # DataTables init, AJAX handlers (script.js)
│   └── images/       # Logos and graphics
├── config/
│   └── database.php  # PDO database connection
├── includes/
│   ├── header.php    # Session check, HTML head, navbar
│   ├── sidebar.php   # Role-based navigation menu
│   ├── footer.php    # Script loading, footer
│   └── permissions.php  # Permission checking functions
├── modules/
│   ├── auth/         # Login, logout, registration, password recovery
│   ├── clients/      # Client CRUD
│   ├── suppliers/    # Supplier CRUD
│   ├── staff/        # Staff account management
│   ├── soa/
│   │   ├── client/   # Client SOA with PDF generation
│   │   └── supplier/ # Supplier SOA with PDF generation
│   ├── claims/       # Mileage reimbursement and meal claims
│   ├── documents/    # Document upload and management
│   ├── inventory/    # Asset tracking, categories, maintenance
│   └── outstation/   # Leave applications and claims
├── vendor/
│   └── fpdf/         # PDF generation library
├── uploads/          # Uploaded document storage
├── dashboard.php     # Executive dashboard
├── index.php         # Entry point (redirects to login)
└── setup.php         # Initial admin account creation
```

---

## Installation & Setup

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache or Nginx web server

### Steps

1. **Clone the repository** into your web server's document root:
   ```bash
   git clone <repository-url> soa_management
   ```

2. **Create the database** and import the schema:
   ```sql
   CREATE DATABASE soa_management;
   ```
   Import the provided SQL file to create all tables.

3. **Configure the database connection** in `config/database.php`:
   ```php
   $host = '127.0.0.1';
   $port = '3306';
   $db   = 'soa_management';
   $user = 'root';
   $pass = '';
   ```

4. **Run the initial setup** by visiting:
   ```
   http://localhost/soa_management/setup.php
   ```
   This creates the default admin account:
   - **Username:** `admin`
   - **Password:** `password`

   This script can only be run once. Change the default password after first login.

5. **Set up the Outstation module** (if needed) by visiting:
   ```
   http://localhost/soa_management/modules/outstation/setup_database.php
   ```

6. **Access the application** at:
   ```
   http://localhost/soa_management/
   ```

---

## Modules

### Authentication

Handles user login, logout, registration, and password recovery.

- **Login** -- Credential verification with password hashing (`password_verify`). Admins and Managers are directed to the dashboard; Staff are directed to the Outstation module.
- **Registration** -- Admin-only. Creates new staff accounts with role assignment (Admin, Manager, or Staff), email validation, and duplicate username detection.
- **Logout** -- Destroys the session and redirects to the login page.
- **Password Recovery** -- Forgot password and reset password functionality.

### Dashboard

An executive overview available to Admin and Manager roles. Displays:

- **Financial Summary Cards** -- Total client SOA revenue, supplier expenses, approved staff claims, and net balance.
- **Status Breakdowns** -- Counts and amounts for Paid, Pending, Overdue, and Closed SOAs.
- **Interactive Charts** (Chart.js) -- SOA status distribution (doughnut), claims status distribution (doughnut), and monthly trends (line chart) covering the last 6 months.
- **Recent Activity** -- Tables showing the 5 most recent client SOAs and staff claims.
- **Business Metrics** -- Client count, supplier count, and expense efficiency percentage.

### Client Management

Full CRUD for client records. Each client has a name, address, and Person In Charge (PIC) contact details (name, phone, email). The detail view shows all SOAs associated with the client. Clients cannot be deleted if they have existing SOA records. Accessible by Admin and Manager roles.

### Supplier Management

Full CRUD for supplier records with the same structure as client management (name, address, PIC details). Suppliers link to both Supplier SOAs and inventory items. Delete protection prevents removal of suppliers with existing transactions. Accessible by Admin only.

### Staff Management

Admin-only module for managing user accounts. View staff profiles with statistics on their claims and documents. Edit staff details including department and role assignment. Delete protection prevents removing accounts that have associated claims or documents, and prevents self-deletion.

### Client Statement of Account

Tracks invoices issued to clients. Each SOA record includes:

- **Account number** (unique identifier)
- **Purchase Order and invoice numbers**
- **Service description, dates, and payment terms**
- **Total amount and status** (Pending, Paid, Overdue, Closed)
- **Due date tracking**

SOAs can be viewed filtered by client or as a complete list. PDF invoices can be generated with the KYROL Security Labs company header, bill-to details, itemized amounts, and footer. Accessible by Admin and Manager roles.

### Supplier Statement of Account

Tracks invoices received from suppliers. Each record includes the supplier link, invoice number, issue date, payment due date, amount, and payment status (Pending, Paid, Overdue). Supports PDF generation for supplier invoices. Accessible by Admin and Manager roles.

### Claims & Mileage Reimbursement

The most feature-rich module. Staff submit mileage reimbursement claims that go through a manager approval workflow.

**Claim structure:**
- Each claim is for a specific month and vehicle type (Car or Motorcycle).
- A claim contains multiple **travel entries**, each with a date, origin, destination, purpose, distance (km), parking fee, and toll fee.
- Claims can also include **meal entries** with date, meal type (Breakfast/Lunch/Dinner/Other), and description.

**Position-based mileage rates:**

| Vehicle | Position | First 500 km | Beyond 500 km |
|---------|----------|--------------|---------------|
| Car | Manager | RM 1.00/km | RM 0.80/km |
| Car | Staff | RM 0.80/km | RM 0.50/km |
| Motorcycle | Manager | RM 0.80/km | RM 1.00/km |
| Motorcycle | Staff | RM 0.50/km (flat) | RM 0.50/km (flat) |

**Total claim amount** = mileage amount + parking fees + toll fees + meal allowance.

**Workflow:**
1. Staff submits a claim with travel entries and an employee signature.
2. Admin or Manager reviews and approves or rejects the claim.
3. Rejected claims include a rejection reason.
4. Approved claims can be marked as paid with payment details.

All staff can submit and view their own claims. Admin and Manager can view all claims and process approvals.

### Document Management

A file management system for uploading and organizing documents linked to other records in the system.

- **Supported formats:** PDF, DOC, DOCX, JPG, JPEG, PNG, GIF
- **Max file size:** 5 MB
- **Reference linking:** Each document links to a record type (claim, SOA, client, etc.) and a specific record ID.
- **Security:** File type whitelist validation, size limits, and unique filename generation to prevent overwrites.
- **Tracking:** Records the uploader and upload date for each document.

All users can upload and view documents.

### Inventory Management

Tracks physical assets and equipment. Accessible by Admin and Manager roles.

- **Items** -- Each inventory item has a name, category, supplier link, serial number, model number, purchase date, and status.
- **Categories** -- Items are organized into user-defined categories with descriptions.
- **Status lifecycle:** Available → Assigned → Maintenance → Disposed. Every status change is logged as a transaction with timestamps, notes, and the staff member who performed it.
- **Maintenance records** -- Track maintenance date, type, description, cost, and who performed it.
- **Delete protection** -- Items with transaction history cannot be deleted.

### Outstation Leave

Manages staff outstation (travel/leave) applications with automatic claim eligibility calculation.

- **Applications** use a numbered format (OSL-YYYY-XXXX) and include destination, departure/return dates, purpose, transportation mode, estimated cost, and accommodation details.
- **Automatic calculation** -- The system computes total nights and determines claim eligibility (stays of 2+ nights qualify).
- **Approval workflow** -- Manager or Admin reviews and approves or rejects applications. Rejected applications require a reason.
- **Dashboard** -- Analytics view with application statistics, monthly trends, status distribution, and top destinations (Manager/Admin only).
- **PDF generation** -- Export application details as PDF documents.
- **AJAX APIs** -- Asynchronous endpoints for creating, updating, and approving applications.

All staff can submit and track their own applications. Managers and Admins can view all applications and process approvals.

---

## Role-Based Access Control

The system enforces three permission levels:

| Feature | Admin | Manager | Staff |
|---------|:-----:|:-------:|:-----:|
| Dashboard | Yes | Yes | No |
| Client Management | Yes | Yes | No |
| Supplier Management | Yes | No | No |
| Staff Management | Yes | No | No |
| Client SOA | Yes | Yes | No |
| Supplier SOA | Yes | Yes | No |
| Claims (own) | Yes | Yes | Yes |
| Claims (approve/reject) | Yes | Yes | No |
| Document Management | Yes | Yes | Yes |
| Inventory | Yes | Yes | No |
| Outstation Leave (own) | Yes | Yes | Yes |
| Outstation Leave (approve) | Yes | Yes | No |
| User Registration | Yes | No | No |

Staff users are redirected to the Outstation module upon login instead of the executive dashboard.

---

## Security

- **SQL Injection Prevention** -- All database queries use PDO prepared statements with parameter binding.
- **XSS Prevention** -- All user input rendered in HTML is escaped with `htmlspecialchars()`.
- **Password Security** -- Passwords are hashed using PHP's `PASSWORD_DEFAULT` algorithm and verified with `password_verify()`.
- **Session-Based Authentication** -- Protected pages validate session variables before granting access.
- **Input Validation** -- Server-side validation on all form submissions including email format, required fields, and numeric checks.
- **File Upload Validation** -- Whitelist of allowed file types, file size limits, and unique filename generation.
- **Foreign Key Protection** -- Delete operations check for dependent records before allowing removal.
- **Role Enforcement** -- Permission checks at the module and page level prevent unauthorized access.
