# Investorage

**Full-Stack Inventory Management for SMEs**  
_A web-based system to streamline inventory tracking, low-stock alerts, order imports/exports, and reporting for small-to-medium warehouses._

---

## Table of Contents

- [Features](#features)  
- [Demo](#demo)  
- [Tech Stack](#tech-stack)  
- [Getting Started](#getting-started)  
  - [Prerequisites](#prerequisites)  
  - [Installation](#installation)  
  - [Configuration](#configuration)  
  - [Database Setup](#database-setup)  
  - [Running](#running)  
- [Usage](#usage)  
- [Folder Structure](#folder-structure)  
- [Testing](#testing)  
- [Contributing](#contributing)  
- [License](#license)  
- [Authors](#authors)

---

## Features

- **Secure Authentication & RBAC**  
  Admin/Staff roles with PHP sessions and prepared statements
- **Inventory CRUD**  
  Add, edit, remove items; real-time low-stock alerts via dashboard bell
- **Order Management**  
  Import via CSV/JSON, export PDF/CSV; duplicate detection & per-row error reporting
- **Advanced Search & Filters**  
  Filter by SKU, name, category, status—scoped per logged-in user
- **Change Inventory Interface**  
  JavaScript-driven toggles for selective field updates
- **Audit Logging**  
  Tracks who changed what, when; supports historical forensic reports
- **Reporting**  
  Generate date-range reports and download as PDF
- **Session Timeout & Error Handling**  
  Inline Bootstrap alerts for validation, duplicates, and session expiry

---

## Demo

![Dashboard](docs/screenshot-dashboard.png)  
![Order Import](docs/screenshot-import.png)

---

## Tech Stack

- **Frontend:** HTML5, CSS3, [Bootstrap 5](https://getbootstrap.com/), JavaScript  
- **Backend:** PHP 8+  
- **Database:** MySQL (InnoDB)  
- **Hosting:** Shared/Cloud Linux (HostGator)  
- **Version Control:** Git & GitHub  

---

## Getting Started

### Prerequisites

- PHP 8+  
- MySQL 5.7+  
- Web server (Apache/Nginx) with HTTPS  
- Git

### Installation

1. **Clone repository**  
   ```bash
   git clone https://github.com/AndrewPuig77/Investorage.git
   cd Investorage
