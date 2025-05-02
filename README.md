# Investorage

**Full-Stack Inventory Management for SMEs**  
A web-based system that streamlines real-time inventory tracking, low-stock alerts, order imports/exports, and reporting for small-to-medium warehouses.

---

## Features

- **Secure Authentication & RBAC**: Admin and Staff roles enforced via PHP sessions and prepared statements.  
- **Inventory CRUD**: Add, edit, remove items with real-time low-stock alerts via dynamic notification bell.  
- **Order Management**: CSV/JSON import and PDF/CSV export pipelines with duplicate-detection and per-row error reporting.  
- **Advanced Search & Filters**: Search by SKU, name, category or status, scoped to the logged-in user’s data.  
- **Selective “Change Inventory” UI**: JavaScript-driven toggles let you update only the fields you choose.  
- **Audit Logging**: Every stock change (who, when, old vs new) is recorded for historical reporting.  
- **Reporting**: Generate date-range PDF exports of current inventory, changes and order summaries.  
- **Error Handling & Session Timeouts**: Inline Bootstrap alerts and inactivity redirects keep the UX smooth.

---

## Tech Stack

- **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript  
- **Backend:** PHP 8+  
- **Database:** MySQL (InnoDB)  
- **Hosting:** Cloud Linux / Apache  
- **Version Control:** Git & GitHub  

---

## Getting Started

1. **Clone the repository**  
2. **Configure** your database credentials in `connection.php` (or your chosen config file).  
3. **Import** the provided SQL schema to create the necessary tables (`Inventory`, `RoleAccess`, `Orders`, etc.).  
4. **Point** your web server’s document root at the project’s `public/` (or top-level) directory.  
5. **Visit** the site in your browser, sign up as Admin (creates your warehouse group), then invite Staff.

---

## Usage

1. **Sign Up / Log In**  
2. **Dashboard** links: Add Inventory, Remove Inventory, Change Inventory, Reports, Order Import.  
3. **Add Inventory**: enter SKU, name, category, price, stock, status, low-stock threshold.  
4. **Remove Inventory**: decrement or delete by quantity.  
5. **Change Inventory**: select an item, click the field-specific button(s) you want to edit, submit.  
6. **Search**: filter by name, category, or status.  
7. **Order Import/Export**: upload CSV/JSON, review per-row errors, export results.  
8. **Reports**: generate and download PDF summaries by date range.  
9. **Logout** or wait out the session timeout to return to login.

---

## Contributing

1. Fork and clone the repo  
2. Create a feature branch (`git checkout -b feature/xyz`)  
3. Commit your changes and push  
4. Open a pull request—describe your changes and link any related issue

---

## License

This project is licensed under the MIT License.

---

## Authors

- **Carlos Cuadra** – Authentication & RBAC  
- **Andrew Puig** – Inventory CRUD & Database Schema  
- **Jose Flores** – Update Logic & Notifications  
- **Cesar Collazo** – Search & Reporting  

