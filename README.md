# 📦 Investorage
**Modern Full-Stack Inventory Management System**

A comprehensive web-based inventory management platform designed for small-to-medium enterprises, featuring real-time tracking, intelligent analytics, and seamless team collaboration.

[![PHP](https://img.shields.io/badge/PHP-8%2B-777BB4?style=flat-square&logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=flat-square&logo=mysql)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

---

## ✨ Key Features

### 🔐 **Advanced Authentication & Security**
- **Role-Based Access Control (RBAC)** - Admin and Staff roles with granular permissions
- **Warehouse Group Management** - Multi-tenant architecture for team collaboration
- **Session Security** - Automatic timeouts and CSRF protection
- **Demo Account System** - Try the platform with pre-loaded sample data

### 📊 **Smart Inventory Management**
- **Real-Time Dashboard** - Modern analytics with interactive charts and KPIs
- **Intelligent Low-Stock Alerts** - Customizable thresholds with visual indicators
- **Advanced Search & Filtering** - Multi-criteria search with pagination and sorting
- **Bulk Operations** - Select and manage multiple items simultaneously
- **Stock Level Indicators** - Visual progress bars and color-coded status badges

### 📈 **Order Processing & Workflow**
- **Multi-Format Import** - Support for CSV, JSON, and Excel files
- **Smart Validation** - Real-time error detection with detailed feedback
- **Order Confirmation System** - Pending → Confirmed workflow with inventory sync
- **Export Management** - Track shipments to different locations
- **Duplicate Detection** - Prevent accidental duplicate entries

### 📋 **Comprehensive Reporting**
- **Interactive Analytics Dashboard** - Real-time metrics and trends
- **Customizable Date Ranges** - Filter reports by specific time periods
- **PDF Export** - Professional reports for stakeholders
- **Activity Logging** - Complete audit trail of all inventory changes
- **Low Stock Reports** - Proactive inventory management alerts

### 🎨 **Modern User Experience**
- **Responsive Design** - Works perfectly on desktop, tablet, and mobile
- **Dark Theme UI** - Modern, professional interface with smooth animations
- **Progressive Enhancement** - JavaScript-enhanced UX with graceful degradation
- **Real-Time Updates** - Live notifications and status updates
- **Intuitive Navigation** - User-friendly interface with contextual help

---

## 🛠️ Tech Stack

| Category | Technologies |
|----------|-------------|
| **Frontend** | HTML5, CSS3, Bootstrap 5, Vanilla JavaScript, Font Awesome |
| **Backend** | PHP 8+, Session Management, File Processing |
| **Database** | MySQL 8.0+ (InnoDB), Prepared Statements |
| **Security** | RBAC, Input Sanitization, SQL Injection Prevention |
| **Hosting** | Apache/Nginx, Cloud Linux Compatible |
| **Development** | Git, Modern PHP Standards, MVC Architecture |

---

## 📱 Usage Guide

### 🏠 **Dashboard Overview**
- **Quick Stats** - Total items, low stock alerts, pending orders, inventory value
- **Recent Activity** - Latest inventory changes with user attribution
- **Quick Actions** - One-click access to common tasks
- **System Status** - Storage usage and health indicators

### 📦 **Inventory Management**
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

### 📊 **Order Processing**
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

### 📈 **Reports & Analytics**
- **Inventory Reports** - Current stock levels, values, and trends
- **Low Stock Alerts** - Items requiring attention with reorder suggestions
- **Activity Logs** - Complete change history with user tracking
- **Custom Date Ranges** - Filter any report by specific time periods

---

## 🏗️ Architecture

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
```
investorage/
├── 📁 assets/           # CSS, JS, Images
├── 📁 includes/         # Shared PHP components
├── 📄 index.php         # Landing page
├── 📄 activeHome.php    # Main dashboard
├── 📄 addInventory.php  # Add new items
├── 📄 searchInventory.php # Advanced search
├── 📄 orderImport.php   # Bulk import system
├── 📄 inventoryReport.php # Analytics dashboard
├── 📄 lowstockReport.php # Alert system
└── 📄 connection.php    # Database configuration
```

---

## 🔒 Security Features

- **Input Sanitization** - All user inputs are validated and sanitized
- **SQL Injection Prevention** - Prepared statements throughout
- **Session Management** - Secure session handling with timeouts
- **Role-Based Access** - Granular permission system
- **CSRF Protection** - Form token validation
- **Data Encryption** - Sensitive data protection

---

## 🎯 Key Highlights

- **Multi-Tenant Architecture** - Support for multiple warehouse groups
- **Real-Time Collaboration** - Team members see live updates
- **Smart Data Validation** - Prevent errors before they happen
- **Comprehensive Audit Trail** - Track every change with full context
- **Mobile-First Design** - Fully responsive across all devices
- **Scalable Codebase** - Clean, maintainable PHP with modern practices

---

## 🔄 Recent Updates

- ✅ **Enhanced UI/UX** - Complete visual overhaul with modern dark theme
- ✅ **Advanced Search** - Multi-criteria filtering with real-time results
- ✅ **Smart Analytics** - Interactive dashboard with key metrics
- ✅ **Demo System** - Try before you buy with sample data
- ✅ **Mobile Optimization** - Perfect experience on all devices
- ✅ **Performance Improvements** - Faster load times and smoother interactions

---

## 📄 License

This project is licensed under the MIT License

---

## 🙏 Acknowledgments

- Built with modern web standards and best practices
- Designed for small-to-medium enterprises
- Focused on user experience and data security
- Continuously updated based on user feedback

---

**Want to test out investorage? Get started** [HERE](https://investorage.site/index.php)!
