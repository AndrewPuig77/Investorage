# 📦 Investorage
**Modern Full-Stack Inventory Management System**

A comprehensive web-based inventory management platform designed for small-to-medium enterprises, featuring real-time tracking, intelligent analytics, and seamless team collaboration.

[![PHP](https://img.shields.io/badge/PHP-8%2B-777BB4?style=flat-square&logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=flat-square&logo=mysql)](https://mysql.com)
[![HTML5](https://img.shields.io/badge/HTML5-Latest-E34F26?style=flat-square&logo=html5)](https://developer.mozilla.org/en-US/docs/Web/HTML)
[![CSS3](https://img.shields.io/badge/CSS3-Latest-1572B6?style=flat-square&logo=css3)](https://developer.mozilla.org/en-US/docs/Web/CSS)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap)](https://getbootstrap.com)
[![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?style=flat-square&logo=javascript)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![Git](https://img.shields.io/badge/Git-Version%20Control-F05032?style=flat-square&logo=git)](https://git-scm.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

---

##  Key Features

###  **Advanced Authentication & Security**
- **Role-Based Access Control (RBAC)** - Admin and Staff roles with granular permissions
- **Warehouse Group Management** - Multi-tenant architecture for team collaboration
- **Session Security** - Automatic timeouts and CSRF protection
- **Demo Account System** - Try the platform with pre-loaded sample data

###  **Smart Inventory Management**
- **Real-Time Dashboard** - Modern analytics with interactive charts and KPIs
- **Intelligent Low-Stock Alerts** - Customizable thresholds with visual indicators
- **Advanced Search & Filtering** - Multi-criteria search with pagination and sorting
- **Bulk Operations** - Select and manage multiple items simultaneously
- **Stock Level Indicators** - Visual progress bars and color-coded status badges

###  **Order Processing & Workflow**
- **Multi-Format Import** - Support for CSV, JSON, and Excel files
- **Smart Validation** - Real-time error detection with detailed feedback
- **Order Confirmation System** - Pending → Confirmed workflow with inventory sync
- **Export Management** - Track shipments to different locations
- **Duplicate Detection** - Prevent accidental duplicate entries

###  **Comprehensive Reporting**
- **Interactive Analytics Dashboard** - Real-time metrics and trends
- **Customizable Date Ranges** - Filter reports by specific time periods
- **PDF Export** - Professional reports for stakeholders
- **Activity Logging** - Complete audit trail of all inventory changes
- **Low Stock Reports** - Proactive inventory management alerts

###  **Modern User Experience**
- **Responsive Design** - Works perfectly on desktop, tablet, and mobile
- **Dark Theme UI** - Modern, professional interface with smooth animations
- **Progressive Enhancement** - JavaScript-enhanced UX with graceful degradation
- **Real-Time Updates** - Live notifications and status updates
- **Intuitive Navigation** - User-friendly interface with contextual help

---

##  Tech Stack

| Category | Technologies |
|----------|-------------|
| **Frontend** | HTML5, CSS3, Bootstrap 5, Vanilla JavaScript, Font Awesome |
| **Backend** | PHP 8+, Session Management, File Processing |
| **Database** | MySQL 8.0+ (InnoDB), Prepared Statements |
| **Security** | RBAC, Input Sanitization, SQL Injection Prevention |
| **Hosting** | Apache/Nginx, Cloud Linux Compatible |
| **Development** | Git, Modern PHP Standards, MVC Architecture |

---

##  Usage Guide

###  **Dashboard Overview**
- **Quick Stats** - Total items, low stock alerts, pending orders, inventory value
- **Recent Activity** - Latest inventory changes with user attribution
- **Quick Actions** - One-click access to common tasks
- **System Status** - Storage usage and health indicators

###  **Inventory Management**
1. **Add New Items**
   - Fill in product details with live preview
   - Set custom low-stock thresholds
   - Choose from predefined categories or create new ones

2. **Update Existing Items**
   - Search and select items to modify
   - Toggle individual fields for precise updates
   - Real-time validation and error handling

3. **Remove Items**
   - Bulk selection with search filtering
   - Confirmation dialogs with undo protection
   - Complete audit trail maintenance

###  **Order Processing**
1. **Import Orders**
   - Drag-and-drop file upload
   - Real-time validation with error highlighting
   - Preview before final import
   - Support for custom date ranges

2. **Export Inventory**
   - Select items and quantities
   - Specify destination locations
   - Automatic stock deduction
   - Generate shipping documents

###  **Reports & Analytics**
- **Inventory Reports** - Current stock levels, values, and trends
- **Low Stock Alerts** - Items requiring attention with reorder suggestions
- **Activity Logs** - Complete change history with user tracking
- **Custom Date Ranges** - Filter any report by specific time periods

---

##  Architecture

### Database Schema
```
├── RoleAccess (Users & Authentication)
├── WarehouseGroups (Multi-tenant Organization)
├── Inventory (Core Product Data)
├── Orders & OrderItems (Import Management)
├── ExportOrders & ExportOrderItems (Export Management)
└── inventory_log (Audit Trail)
```

### File Structure
Everything the website serves lives in `public/`, in one flat folder (the pages include each other by filename).
```
investorage/
├──  public/                 # Upload the contents of this folder
│   ├──  index.php           # Landing page
│   ├──  activeHome.php      # Main dashboard (renders dashboardCard.php)
│   ├──  indexElements.php   # Shared <head>, theme, sidebar and footer
│   ├──  searchInventory.php # Inventory list, search and filters
│   ├──  addInventory.php    # Add new items
│   ├──  orderImport.php     # CSV/JSON order import
│   ├──  inventoryReport.php # Reports (PDF via fpdf/)
│   ├──  lowstockReport.php  # Low stock report
│   ├──  connection.php      # Opens the database connection
│   ├──  config.example.php  # Template for config.php (database login)
│   ├──  fpdf/               # PDF library
│   └──  *.png, *.jpg        # Images
├──  schema.sql              # Creates all database tables
└──  Investorage Software Documentation Package.pdf
```

### Setup
1. Create a MySQL database and import `schema.sql` into it (phpMyAdmin → Import).
2. In `public/`, copy `config.example.php` to `config.php` and fill in your database host, user, password and name. `config.php` is in `.gitignore`, so it is never committed.
3. Upload the **contents** of `public/` to your host's web root (e.g. `htdocs`), or point XAMPP at it locally.
4. Open `index.php` and click **Try Demo** to fill a sample warehouse.

---

##  Security Features

- **Password Hashing** - User and warehouse passwords are stored with `password_hash()`
- **SQL Injection Prevention** - Prepared statements throughout
- **Session Management** - Sessions time out after an hour of inactivity
- **Role-Based Access** - Admin and Staff roles per warehouse
- **Credentials Kept Out of Git** - Database login lives in an ignored `config.php`

---

##  Key Highlights

- **Multi-Tenant Architecture** - Support for multiple warehouse groups
- **Real-Time Collaboration** - Team members see live updates
- **Smart Data Validation** - Prevent errors before they happen
- **Comprehensive Audit Trail** - Track every change with full context
- **Mobile-First Design** - Fully responsive across all devices
- **Scalable Codebase** - Clean, maintainable PHP with modern practices




## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.



