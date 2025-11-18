# CLAUDE.md - SOA Management System Guide for AI Assistants

This document provides comprehensive guidance for AI assistants working with the KYROL Security Labs SOA (Statement of Account) Management System.

## Table of Contents
1. [Project Overview](#project-overview)
2. [Tech Stack](#tech-stack)
3. [Project Structure](#project-structure)
4. [Database Schema](#database-schema)
5. [Architecture Patterns](#architecture-patterns)
6. [Naming Conventions](#naming-conventions)
7. [Code Organization](#code-organization)
8. [Development Workflows](#development-workflows)
9. [Security Guidelines](#security-guidelines)
10. [Module Documentation](#module-documentation)
11. [Common Patterns](#common-patterns)
12. [Testing and Debugging](#testing-and-debugging)

---

## Project Overview

**Project Name:** SOA Management System
**Organization:** KYROL Security Labs
**Purpose:** Custom-built business management system for handling Statements of Account, client/supplier relationships, staff claims, and inventory tracking.

**Key Features:**
- Client and Supplier Management
- Dual SOA System (Client SOA & Supplier SOA)
- Staff Claims & Mileage Reimbursement with position-based rates
- Document Management
- Inventory Tracking with Maintenance
- Role-Based Access Control (Admin, Manager, Staff)
- PDF Generation for SOA documents

**Statistics:**
- Total PHP files: 92
- Database tables: 14
- Modules: 9
- Project size: ~2.0MB

---

## Tech Stack

### Backend
- **PHP**: Procedural programming style (no OOP framework)
- **MySQL**: Relational database
- **PDO (PHP Data Objects)**: Database abstraction with prepared statements
- **FPDF**: PDF generation library

### Frontend
- **HTML5**: Markup
- **Bootstrap 4.5.2**: Responsive CSS framework
- **jQuery 3.5.1**: JavaScript library
- **Font Awesome 5.15.3/6.4.0**: Icon library
- **DataTables**: Table enhancement (sorting, pagination, filtering)
- **Google Fonts (Poppins)**: Typography

### Server
- **Apache/Nginx**: Web server
- **PHP Sessions**: Authentication management

---

## Project Structure

```
/home/user/soa_management/
├── assets/                    # Static resources
│   ├── css/
│   │   └── style.css         # Custom styles (474 lines)
│   ├── js/
│   │   └── script.js         # DataTables, AJAX handlers
│   └── images/               # Logos and graphics
│
├── config/
│   └── database.php          # PDO database connection
│
├── includes/                 # Shared components
│   ├── header.php           # Session check, HTML head, navbar
│   ├── sidebar.php          # Role-based navigation
│   ├── footer.php           # Scripts and footer
│   └── permissions.php      # Permission checking functions
│
├── modules/                  # Feature modules
│   ├── auth/                # Authentication (login, logout, register)
│   ├── clients/             # Client CRUD operations
│   ├── suppliers/           # Supplier CRUD operations
│   ├── staff/               # Staff management (Admin only)
│   ├── soa/                 # Statement of Account
│   │   ├── client/         # Client SOA sub-module
│   │   └── supplier/       # Supplier SOA sub-module
│   ├── claims/              # Mileage reimbursement system
│   ├── documents/           # Document upload/management
│   └── inventory/           # Inventory tracking
│
├── vendor/                   # Third-party libraries
│   └── fpdf/                # PDF generation library
│
├── dashboard.php            # Main dashboard (post-login)
├── index.php               # Entry point (redirects to login)
└── setup.php               # Initial admin account setup
```

### Directory Purposes

- **assets/**: Frontend resources (CSS, JS, images)
- **config/**: System and database configuration
- **includes/**: Reusable PHP components (headers, footers, shared functions)
- **modules/**: Business logic organized by feature/entity
- **vendor/**: External dependencies

---

## Database Schema

**Database Name:** `soa_management`
**Connection:** localhost, user: root, password: empty (development)

### Core Tables

#### 1. staff
```sql
staff_id (PK, AUTO_INCREMENT)
username (UNIQUE)
password (HASHED)
full_name
email (UNIQUE)
department
position (Admin/Manager/Staff)
created_at
```

#### 2. clients
```sql
client_id (PK, AUTO_INCREMENT)
client_name
address
pic_name (Person In Charge)
pic_contact
pic_email
created_at
```

#### 3. suppliers
```sql
supplier_id (PK, AUTO_INCREMENT)
supplier_name
address
pic_name
pic_contact
pic_email
created_at
```

#### 4. client_soa
```sql
soa_id (PK, AUTO_INCREMENT)
account_number (UNIQUE)
client_id (FK → clients.client_id)
terms
purchase_date
issue_date
po_number (Purchase Order)
invoice_number
service_description
total_amount
status (Paid/Pending/Overdue)
due_date
created_by (FK → staff.staff_id)
created_at
```

#### 5. supplier_soa
```sql
soa_id (PK, AUTO_INCREMENT)
account_number (UNIQUE)
supplier_id (FK → suppliers.supplier_id)
invoice_number
issue_date
payment_due_date
total_amount
status
created_by (FK → staff.staff_id)
created_at
```

#### 6. claims
```sql
claim_id (PK, AUTO_INCREMENT)
staff_id (FK → staff.staff_id)
claim_month
vehicle_type (Car/Motorcycle)
description
amount (total calculated amount)
status (Pending/Approved/Rejected)
km_rate
total_km_amount
employee_signature
signature_date
submitted_date
created_at
```

#### 7. claim_travel_entries
```sql
entry_id (PK, AUTO_INCREMENT)
claim_id (FK → claims.claim_id)
travel_date
travel_from
travel_to
purpose
parking_fee
toll_fee
miles_traveled
created_at
```

#### 8. mileage_rates
```sql
rate_id (PK, AUTO_INCREMENT)
vehicle_type
km_threshold
rate_per_km
position_type (Manager/Staff)
```

**Mileage Rate Logic:**
- **Car - Manager**: RM 1.00/km (first 450km), RM 0.80/km (beyond)
- **Car - Regular Staff**: RM 0.80/km (first 450km), RM 0.50/km (beyond)
- **Motorcycle - Manager**: RM 0.80/km (first 450km), RM 1.00/km (beyond)
- **Motorcycle - Regular Staff**: RM 0.50/km (flat rate)

#### 9. documents
```sql
document_id (PK, AUTO_INCREMENT)
document_type
reference_id (links to claim_id, soa_id, etc.)
reference_type (claims, soa, etc.)
file_name
file_path
description
uploaded_by (FK → staff.staff_id)
upload_date
```

#### 10. inventory_items
```sql
item_id (PK, AUTO_INCREMENT)
item_name
category_id (FK → inventory_categories.category_id)
supplier_id (FK → suppliers.supplier_id)
serial_number
model_number
purchase_date
status (Available/Assigned/Maintenance/Disposed)
created_at
```

#### 11. inventory_categories
```sql
category_id (PK, AUTO_INCREMENT)
category_name
description
created_at
```

#### 12. inventory_transactions
```sql
transaction_id (PK, AUTO_INCREMENT)
item_id (FK → inventory_items.item_id)
transaction_type
from_status
to_status
transaction_date
notes
performed_by (FK → staff.staff_id)
```

#### 13. inventory_maintenance
```sql
maintenance_id (PK, AUTO_INCREMENT)
item_id (FK → inventory_items.item_id)
maintenance_date
maintenance_type
description
cost
performed_by (FK → staff.staff_id)
```

---

## Architecture Patterns

### Primary Pattern: Modular MVC-like (Simplified)

This is **NOT a strict MVC framework**, but follows a simplified separation of concerns:

- **Model**: Database interactions via PDO (inline in each file)
- **View**: HTML templates with embedded PHP
- **Controller**: PHP files handling business logic and HTTP requests

### Architecture Style

1. **Modular Monolith**: Features organized in separate module directories
2. **Procedural PHP**: No OOP classes except PDF generation (`extends FPDF`)
3. **Include-based**: Uses `include_once` for code reuse

### Key Design Patterns

#### 1. Template Pattern
Every page follows this structure:
```php
<?php
ob_start(); // Output buffering
$basePath = '../../'; // Relative path management

include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";
require_once $basePath . "config/database.php";

// Business logic here

?>
<div class="col-md-10 ml-sm-auto px-4">
    <!-- Page content -->
</div>
<?php
include_once $basePath . "includes/footer.php";
ob_end_flush();
?>
```

#### 2. CRUD Pattern
Standard file naming across modules:
- `index.php` - List all records (READ all)
- `add.php` - Create new record (CREATE)
- `edit.php` - Update existing record (UPDATE)
- `view.php` - View single record details (READ one)
- `delete.php` - Delete record (DELETE)
- `generate_pdf.php` - Generate PDF (specific to SOA modules)

#### 3. Security Patterns
- **Prepared Statements**: All database queries use PDO placeholders
- **Password Hashing**: `password_hash(PASSWORD_DEFAULT)` for storage
- **Session-based Auth**: Session checks in `header.php`
- **Input Validation**: Server-side validation for all inputs
- **XSS Prevention**: `htmlspecialchars()` on all user input output
- **Role-based Access Control**: Position-based permissions

---

## Naming Conventions

### Files
- **lowercase with underscores**: `update_status.php`, `generate_pdf.php`, `claim_travel_entries`
- **CRUD standard**: `index.php`, `add.php`, `edit.php`, `view.php`, `delete.php`

### Variables (PHP)
- **snake_case**: `$client_name`, `$total_amount`, `$staff_id`
- **Error variables**: Suffix with `_err` → `$client_name_err`, `$password_err`
- **PDO parameters**: Prefix with `param_` → `$param_client_name`, `$param_id`
- **Descriptive names**: `$preselected_client`, `$mileage_rates`

### Database
- **Lowercase with underscores**: `client_soa`, `claim_travel_entries`
- **Primary Keys**: `{table}_id` → `client_id`, `staff_id`, `soa_id`
- **Foreign Keys**: Match referenced table's PK name

### Functions
- **camelCase**: `checkPermission()`, `calculateMileage()`

### Classes
- **PascalCase**: `PDF` (extends FPDF)

### CSS Classes
- **kebab-case**: `login-wrapper`, `sidebar-nav`, `card-header`
- **Bootstrap standard**: `btn-primary`, `table-responsive`

---

## Code Organization

### Separation of Concerns

1. **Configuration Layer** (`/config/`)
   - Database connection isolated in `database.php`
   - PDO configuration centralized

2. **Presentation Layer** (`/includes/`)
   - `header.php`: Authentication check, HTML head, navbar
   - `sidebar.php`: Role-based navigation menu
   - `footer.php`: Scripts loading, copyright

3. **Business Logic Layer** (`/modules/`)
   - Each feature in separate directory
   - Self-contained functionality
   - Minimal interdependencies

4. **Data Layer**
   - PDO prepared statements throughout
   - No ORM
   - Direct SQL with parameterized queries
   - Fetch mode: `PDO::FETCH_ASSOC`

### basePath Management

Each file sets `$basePath` based on directory depth:

```php
// In root level files
$basePath = './';

// In /modules/clients/
$basePath = '../../';

// In /modules/soa/client/
$basePath = '../../../';
```

This allows consistent inclusion of shared files:
```php
include_once $basePath . "includes/header.php";
require_once $basePath . "config/database.php";
```

### Error Handling

```php
try {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = :id");
    $stmt->bindParam(':id', $param_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: Could not execute query. " . $e->getMessage());
}
```

---

## Development Workflows

### Adding a New Module

1. **Create module directory** in `/modules/`
   ```bash
   mkdir modules/new_module
   ```

2. **Create CRUD files** following the standard pattern:
   - `index.php` - List view with DataTables
   - `add.php` - Create form with validation
   - `edit.php` - Update form (pre-populate from DB)
   - `view.php` - Read-only details view
   - `delete.php` - Delete with confirmation

3. **Set correct basePath** in each file:
   ```php
   $basePath = '../../'; // For files in /modules/new_module/
   ```

4. **Include required files**:
   ```php
   include_once $basePath . "includes/header.php";
   include_once $basePath . "includes/sidebar.php";
   require_once $basePath . "config/database.php";
   ```

5. **Add navigation** to `includes/sidebar.php`:
   ```php
   <li class="nav-item">
       <a class="nav-link" href="<?php echo $basePath; ?>modules/new_module/index.php">
           <i class="fas fa-icon"></i> Module Name
       </a>
   </li>
   ```

6. **Implement permissions** if role-restricted:
   ```php
   require_once $basePath . "includes/permissions.php";
   checkPermission('Admin'); // Restricts to Admin only
   ```

### Adding a Database Table

1. **Create migration/setup script** or execute SQL directly:
   ```sql
   CREATE TABLE new_table (
       id INT PRIMARY KEY AUTO_INCREMENT,
       name VARCHAR(255) NOT NULL,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
   ```

2. **Follow naming convention**: `table_name` (lowercase, underscores)

3. **Use consistent PK naming**: `{table}_id`

4. **Add foreign keys** where appropriate:
   ```sql
   FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE
   ```

### Form Processing Pattern

```php
<?php
// Initialize variables
$field_name = "";
$field_name_err = "";

// Process form on POST
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate field
    $input_field = trim($_POST["field_name"]);
    if(empty($input_field)){
        $field_name_err = "Please enter field name.";
    } else {
        $field_name = $input_field;
    }

    // Check for errors before inserting
    if(empty($field_name_err)){
        $sql = "INSERT INTO table_name (field_name) VALUES (:field_name)";

        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":field_name", $param_field_name);
            $param_field_name = $field_name;

            if($stmt->execute()){
                header("location: index.php");
                exit();
            } else {
                echo "Something went wrong. Please try again later.";
            }
        }
        unset($stmt);
    }
    unset($pdo);
}
?>
```

### PDF Generation Pattern

For modules requiring PDF generation (like SOA):

```php
<?php
require_once '../../../vendor/fpdf/fpdf.php';

class PDF extends FPDF {
    function Header() {
        // Logo, title, etc.
    }

    function Footer() {
        // Page number, etc.
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Document Title', 0, 1, 'C');

// Fetch data from database
// Add content to PDF

$pdf->Output('D', 'filename.pdf'); // D = Download
?>
```

### Adding JavaScript Functionality

Add to `/assets/js/script.js`:

```javascript
$(document).ready(function() {
    // Initialize DataTables
    $('#myTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 25
    });

    // AJAX example
    $('#myForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'process.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                // Handle response
            }
        });
    });
});
```

---

## Security Guidelines

### 1. Input Validation

**Always validate on server-side** (client-side is optional):

```php
// Required field
if(empty(trim($_POST["field_name"]))){
    $field_name_err = "Please enter field name.";
}

// Email validation
if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
    $email_err = "Please enter a valid email address.";
}

// Numeric validation
if(!is_numeric($amount)){
    $amount_err = "Please enter a valid number.";
}
```

### 2. SQL Injection Prevention

**ALWAYS use prepared statements**:

```php
// CORRECT
$sql = "SELECT * FROM users WHERE username = :username";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':username', $param_username);
$stmt->execute();

// WRONG - Never do this
$sql = "SELECT * FROM users WHERE username = '$username'";
$result = $pdo->query($sql);
```

### 3. XSS Prevention

**Escape output with `htmlspecialchars()`**:

```php
// Display user input
echo htmlspecialchars($user_input);

// In HTML attributes
<input type="text" value="<?php echo htmlspecialchars($value); ?>">
```

### 4. Password Security

```php
// Hashing on registration
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Verification on login
if(password_verify($password, $hashed_password)){
    // Password correct
}
```

### 5. Session Security

In `header.php`:
```php
session_start();

// Check if logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}
```

### 6. File Upload Security

```php
// Validate file type
$allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
$file_extension = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));

if(!in_array($file_extension, $allowed_types)){
    $error = "Invalid file type.";
}

// Validate file size (5MB max)
if($_FILES["file"]["size"] > 5242880){
    $error = "File too large. Max 5MB.";
}

// Generate unique filename
$new_filename = uniqid() . '.' . $file_extension;
```

### 7. Role-Based Access Control

```php
// In permissions.php
function checkPermission($required_position) {
    $positions = ['Admin' => 3, 'Manager' => 2, 'Staff' => 1];
    $user_level = $positions[$_SESSION['position']] ?? 0;
    $required_level = $positions[$required_position] ?? 0;

    if($user_level < $required_level) {
        header("location: /dashboard.php");
        exit;
    }
}

// Usage in restricted pages
checkPermission('Admin'); // Only Admin can access
checkPermission('Manager'); // Manager and Admin can access
```

---

## Module Documentation

### Auth Module (`/modules/auth/`)

**Purpose**: User authentication and registration

**Files**:
- `login.php` - Login form with credential validation
- `logout.php` - Session destruction and redirect
- `register.php` - New staff registration (requires Admin access)

**Login Flow**:
1. User submits username/password
2. Query `staff` table for username
3. Verify password with `password_verify()`
4. Create session variables: `loggedin`, `staff_id`, `username`, `full_name`, `position`
5. Redirect to `dashboard.php`

**Session Variables**:
```php
$_SESSION['loggedin'] = true;
$_SESSION['staff_id'] = $staff_id;
$_SESSION['username'] = $username;
$_SESSION['full_name'] = $full_name;
$_SESSION['position'] = $position; // Admin/Manager/Staff
```

### Clients Module (`/modules/clients/`)

**Purpose**: Manage client records

**CRUD Operations**:
- **index.php**: List all clients with DataTables
- **add.php**: Create new client
- **edit.php**: Update client details
- **view.php**: Display client details + associated SOAs
- **delete.php**: Delete client (checks for associated SOAs first)

**Fields**:
- client_name
- address
- pic_name (Person In Charge)
- pic_contact
- pic_email

### Suppliers Module (`/modules/suppliers/`)

**Purpose**: Manage supplier records

**Structure**: Identical to Clients module

**Fields**: Same as clients (supplier_name, address, PIC details)

### Staff Module (`/modules/staff/`)

**Purpose**: Manage staff accounts (Admin only)

**Access**: Admin only (enforced with `checkPermission('Admin')`)

**Files**:
- `index.php` - Staff listing
- `edit.php` - Update staff details
- `view.php` - View staff profile

**Note**: Staff registration is done via `/modules/auth/register.php`

### SOA Module (`/modules/soa/`)

**Purpose**: Statement of Account management (dual system)

#### Client SOA Sub-module (`/modules/soa/client/`)

**Purpose**: Track client invoices and payments

**Files**:
- `index.php` - All client SOAs
- `client_soas.php` - SOAs filtered by specific client
- `all_soas.php` - Comprehensive listing
- `add.php` - Create new client SOA
- `edit.php` - Update client SOA
- `view.php` - View SOA details
- `generate_pdf.php` - Generate PDF invoice

**Key Fields**:
- account_number (unique identifier)
- client_id (FK)
- po_number (Purchase Order)
- invoice_number
- service_description
- total_amount
- status (Paid/Pending/Overdue)
- due_date

#### Supplier SOA Sub-module (`/modules/soa/supplier/`)

**Purpose**: Track supplier invoices and payments

**Structure**: Similar to client SOA

**Files**: `index.php`, `add.php`, `edit.php`, `view.php`, `delete.php`, `generate_pdf.php`

### Claims Module (`/modules/claims/`)

**Purpose**: Staff mileage reimbursement system

**Complexity**: Highest complexity module (630+ lines in add.php)

**Files**:
- `index.php` - Claims listing
- `add.php` - Submit new claim with travel entries
- `edit.php` - Update claims
- `view.php` - View claim details
- `update_status.php` - Admin/Manager claim approval
- `process.php` - Workflow processing

**Key Features**:
1. **Multi-entry System**: Each claim can have multiple travel entries
2. **Position-based Rates**: Different mileage rates for Managers vs Staff
3. **Vehicle Types**: Car or Motorcycle
4. **Tiered Rates**: Different rates based on km threshold (450km)
5. **Additional Costs**: Parking and toll fees per entry
6. **Signature Capture**: Employee signature for submission

**Calculation Logic**:
```php
// Car - Manager
if(km <= 450): rate = RM 1.00/km
if(km > 450): rate = RM 0.80/km

// Car - Staff
if(km <= 450): rate = RM 0.80/km
if(km > 450): rate = RM 0.50/km

// Motorcycle - Manager
if(km <= 450): rate = RM 0.80/km
if(km > 450): rate = RM 1.00/km

// Motorcycle - Staff
rate = RM 0.50/km (flat)

// Total Calculation
total = (km_amount) + sum(parking_fees) + sum(toll_fees)
```

**Tables Used**:
- `claims` - Main claim record
- `claim_travel_entries` - Individual travel entries
- `mileage_rates` - Rate configuration

### Documents Module (`/modules/documents/`)

**Purpose**: Upload and manage documents linked to other records

**Files**:
- `index.php` - Document listing
- `upload.php` - File upload with validation
- `view.php` - View/download documents

**Allowed File Types**: PDF, DOC, DOCX, JPG, JPEG, PNG, GIF

**Max File Size**: 5MB

**Reference System**:
- `reference_type`: Type of linked record (claims, soa, client, etc.)
- `reference_id`: ID of linked record

### Inventory Module (`/modules/inventory/`)

**Purpose**: Track physical assets and equipment

**Files**:
- `index.php` - Inventory listing
- `add.php` - Add new item
- `edit.php` - Update item
- `view.php` - View item details
- `categories.php` - Manage categories
- `transactions.php` - Track status changes
- `maintenance.php` - Maintenance records

**Item Statuses**:
- Available
- Assigned
- Maintenance
- Disposed

**Features**:
- Category-based organization
- Supplier linkage
- Serial number tracking
- Transaction history
- Maintenance scheduling

---

## Common Patterns

### DataTables Implementation

In HTML:
```html
<table id="dataTable" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Column 1</th>
            <th>Column 2</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $stmt->fetch()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['column1']); ?></td>
                <td><?php echo htmlspecialchars($row['column2']); ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>
```

In JavaScript:
```javascript
$(document).ready(function() {
    $('#dataTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 25
    });
});
```

### Modal Confirmation Pattern

```html
<!-- Delete button triggers modal -->
<button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteModal">
    Delete
</button>

<!-- Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this record?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <a href="delete.php?id=<?php echo $id; ?>" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>
```

### Dynamic Form Fields (JavaScript)

For adding multiple travel entries in claims:
```javascript
$('#addEntry').click(function() {
    var entryHtml = `
        <div class="travel-entry">
            <input type="date" name="travel_date[]" required>
            <input type="text" name="travel_from[]" required>
            <input type="text" name="travel_to[]" required>
            <input type="number" name="parking_fee[]" step="0.01">
            <button type="button" class="remove-entry">Remove</button>
        </div>
    `;
    $('#entries-container').append(entryHtml);
});

$(document).on('click', '.remove-entry', function() {
    $(this).closest('.travel-entry').remove();
});
```

### Dropdown Population from Database

```php
<select name="client_id" class="form-control" required>
    <option value="">Select Client</option>
    <?php
    $sql = "SELECT client_id, client_name FROM clients ORDER BY client_name";
    $stmt = $pdo->query($sql);
    while($row = $stmt->fetch()):
    ?>
        <option value="<?php echo $row['client_id']; ?>">
            <?php echo htmlspecialchars($row['client_name']); ?>
        </option>
    <?php endwhile; ?>
</select>
```

### Pre-populating Edit Forms

```php
<?php
// Fetch existing data
$id = $_GET['id'];
$sql = "SELECT * FROM table_name WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id);
$stmt->execute();
$row = $stmt->fetch();

// Extract values
$field_name = $row['field_name'];
?>

<form method="POST">
    <input type="text" name="field_name" value="<?php echo htmlspecialchars($field_name); ?>" required>
    <button type="submit">Update</button>
</form>
```

---

## Testing and Debugging

### Database Connection Testing

```php
// In any file, after including database.php
try {
    $stmt = $pdo->query("SELECT DATABASE()");
    $db = $stmt->fetchColumn();
    echo "Connected to database: " . $db;
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
```

### Error Display (Development Only)

```php
// Add to top of file during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Remove in production!
```

### SQL Query Debugging

```php
try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':param', $value);

    // Debug: Print query
    echo $stmt->queryString;

    $stmt->execute();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
```

### Session Debugging

```php
// View all session variables
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
```

### Common Issues and Solutions

**Issue**: "Headers already sent" error
**Solution**: Ensure no output (including whitespace) before `header()` calls. Use `ob_start()` at file beginning.

**Issue**: Database connection fails
**Solution**: Check `config/database.php` credentials, ensure MySQL service is running.

**Issue**: Permissions error (Access denied)
**Solution**: Check session variables, verify `checkPermission()` call has correct position parameter.

**Issue**: DataTables not initializing
**Solution**: Ensure jQuery loads before DataTables script, verify table ID matches JavaScript selector.

**Issue**: File upload fails
**Solution**: Check `upload_max_filesize` and `post_max_size` in php.ini, verify folder permissions.

---

## Best Practices for AI Assistants

### When Adding Features
1. ✅ Follow existing CRUD patterns in similar modules
2. ✅ Use prepared statements for ALL database queries
3. ✅ Validate ALL user inputs on server-side
4. ✅ Set appropriate `$basePath` based on file location
5. ✅ Add navigation links to `includes/sidebar.php`
6. ✅ Use consistent naming conventions (snake_case variables, camelCase functions)
7. ✅ Include proper error handling with try-catch
8. ✅ Escape output with `htmlspecialchars()`
9. ✅ Add role-based access control if needed
10. ✅ Test with different user roles (Admin, Manager, Staff)

### When Modifying Existing Code
1. ✅ Read the entire file to understand context
2. ✅ Maintain existing code style and patterns
3. ✅ Don't break existing functionality
4. ✅ Test related features after changes
5. ✅ Keep security measures intact
6. ✅ Update both frontend and backend if changing data structure

### When Debugging
1. ✅ Check browser console for JavaScript errors
2. ✅ Verify database queries execute successfully
3. ✅ Confirm session variables are set correctly
4. ✅ Test with realistic data, not just edge cases
5. ✅ Review PHP error logs

### Security Checklist
- [ ] All database queries use prepared statements?
- [ ] All user input is validated?
- [ ] All output is escaped with `htmlspecialchars()`?
- [ ] Passwords are hashed, never stored in plain text?
- [ ] Session checks are in place for protected pages?
- [ ] File uploads are validated for type and size?
- [ ] Role-based access control is enforced where needed?

---

## Quick Reference

### Common File Paths
```php
// Database
require_once $basePath . "config/database.php";

// Includes
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";
include_once $basePath . "includes/footer.php";
include_once $basePath . "includes/permissions.php";

// FPDF
require_once $basePath . "vendor/fpdf/fpdf.php";
```

### Session Variables
```php
$_SESSION['loggedin']    // boolean
$_SESSION['staff_id']    // integer
$_SESSION['username']    // string
$_SESSION['full_name']   // string
$_SESSION['position']    // 'Admin', 'Manager', or 'Staff'
```

### Common SQL Patterns
```php
// SELECT
$sql = "SELECT * FROM table WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $param_id);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

// INSERT
$sql = "INSERT INTO table (field) VALUES (:field)";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':field', $param_field);
$stmt->execute();

// UPDATE
$sql = "UPDATE table SET field = :field WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':field', $param_field);
$stmt->bindParam(':id', $param_id);
$stmt->execute();

// DELETE
$sql = "DELETE FROM table WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $param_id);
$stmt->execute();
```

### Bootstrap Component Classes
```html
<!-- Buttons -->
<button class="btn btn-primary">Primary</button>
<button class="btn btn-success">Success</button>
<button class="btn btn-danger">Danger</button>

<!-- Cards -->
<div class="card">
    <div class="card-header">Title</div>
    <div class="card-body">Content</div>
</div>

<!-- Forms -->
<div class="form-group">
    <label>Label</label>
    <input type="text" class="form-control">
</div>

<!-- Tables -->
<table class="table table-striped table-bordered">
```

---

## Recent Development Focus

Based on git history analysis, recent development has focused on:

1. **Claims Module Enhancement** - Complex mileage calculation logic with position-based rates
2. **SOA Segregation** - Separating client and supplier SOA workflows into sub-modules
3. **UI/UX Improvements** - Layout refinements and design updates
4. **Role-based Features** - Different calculation rates for Managers vs Staff
5. **Feature Completeness** - Adding missing CRUD operations across modules

---

## Conclusion

This SOA Management System is a **procedural PHP application** with a **modular architecture** designed for business management. When working with this codebase:

- Follow the established patterns and conventions
- Prioritize security (prepared statements, input validation, XSS prevention)
- Maintain code consistency across modules
- Test thoroughly with different user roles
- Document significant changes

For questions or clarifications about specific modules, refer to the corresponding files in `/modules/` or review the database schema above.

**Last Updated**: 2025-11-18
**Version**: 1.0
