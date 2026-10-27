-- Investorage database schema
-- Reconstructed from the queries in the PHP source (the original database
-- was lost with the iFastNet hosting account). Column names match the code
-- exactly, including its mixed casing (UserID vs User_Id).
--
-- No foreign keys on purpose: several pages delete parent rows before child
-- rows (e.g. removeOrder.php, deleteExportOrder.php), which FKs would reject.

-- Import into an existing database: select it in phpMyAdmin first (shared
-- hosts like InfinityFree don't allow CREATE DATABASE from SQL).

-- A warehouse; users join one by name + password at signup.
CREATE TABLE IF NOT EXISTS WarehouseGroups (
  GroupID       INT AUTO_INCREMENT PRIMARY KEY,
  GroupName     VARCHAR(100) NOT NULL,
  GroupPassword VARCHAR(255) NOT NULL,
  UNIQUE KEY uq_groupname (GroupName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User accounts. Role is 'Admin' or 'Staff'.
CREATE TABLE IF NOT EXISTS RoleAccess (
  UserID    INT AUTO_INCREMENT PRIMARY KEY,
  email     VARCHAR(255) NOT NULL,
  entryDate DATE,
  FirstName VARCHAR(100),
  LastName  VARCHAR(100),
  password  VARCHAR(255) NOT NULL,
  Role      VARCHAR(20)  NOT NULL DEFAULT 'Staff',
  userName  VARCHAR(100),
  GroupID   INT NOT NULL,
  KEY idx_email (email),
  KEY idx_group (GroupID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stock on hand, scoped to a warehouse by GroupID.
-- Status values used: In-Stock, Ordered, Backordered, Reserved, Dropped.
CREATE TABLE IF NOT EXISTS Inventory (
  InventoryID     INT AUTO_INCREMENT PRIMARY KEY,
  SKU             VARCHAR(50)  NOT NULL,
  Name            VARCHAR(255) NOT NULL,
  Category        VARCHAR(100),
  Description     TEXT,
  SalesPrice      DECIMAL(12,2) NOT NULL DEFAULT 0,
  Stock           INT NOT NULL DEFAULT 0,
  LowStockWarning INT NOT NULL DEFAULT 0,
  Status          VARCHAR(30) DEFAULT 'In-Stock',
  GroupID         INT NOT NULL,
  User_Id         INT,
  KEY idx_sku_group (SKU, GroupID),
  KEY idx_group (GroupID),
  KEY idx_user (User_Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit trail. ChangeType values used: add, updateInventory, removeAll,
-- removeSelected, import, confirmImport, export.
CREATE TABLE IF NOT EXISTS inventory_log (
  LogID      INT AUTO_INCREMENT PRIMARY KEY,
  SKU        VARCHAR(50),
  ChangeType VARCHAR(30) NOT NULL,
  OldValue   TEXT,
  NewValue   TEXT,
  CreatedAt  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  User_Id    INT,
  -- Read by reporting/generateInventoryReportPDF.php but never written.
  GroupID    INT NULL,
  KEY idx_user_created (User_Id, CreatedAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Incoming (import) orders. OrderStatus: pending, complete.
CREATE TABLE IF NOT EXISTS Orders (
  OrderID              VARCHAR(64) PRIMARY KEY,
  OrderDate            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  OrderStatus          VARCHAR(20) NOT NULL DEFAULT 'pending',
  ExpectedDeliveryDate DATETIME NULL,
  DateCompleted        DATETIME NULL,
  TotalAmount          DECIMAL(12,2) NOT NULL DEFAULT 0,
  UserID               INT NOT NULL,
  OrderType            VARCHAR(20) NULL,
  KEY idx_user (UserID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS OrderItems (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  OrderID         VARCHAR(64) NOT NULL,
  SKU             VARCHAR(50) NOT NULL,
  Name            VARCHAR(255),
  Category        VARCHAR(100),
  Description     TEXT,
  SalesPrice      DECIMAL(12,2) NOT NULL DEFAULT 0,
  Stock           INT NOT NULL DEFAULT 0,
  LowStockWarning INT NOT NULL DEFAULT 0,
  Status          VARCHAR(30),
  User_Id         INT,
  Quantity        INT NOT NULL DEFAULT 0,
  KEY idx_order (OrderID),
  KEY idx_user (User_Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Outgoing shipments to another location.
CREATE TABLE IF NOT EXISTS ExportOrders (
  ExportOrderID VARCHAR(64) PRIMARY KEY,
  ExportDate    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Destination   VARCHAR(255),
  ExportedBy    VARCHAR(100),
  UserID        INT NOT NULL,
  GroupID       INT NOT NULL,
  TotalItems    INT NOT NULL DEFAULT 0,
  TotalValue    DECIMAL(12,2) NOT NULL DEFAULT 0,
  KEY idx_user (UserID),
  KEY idx_group (GroupID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ExportOrderItems (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  ExportOrderID    VARCHAR(64) NOT NULL,
  SKU              VARCHAR(50) NOT NULL,
  Category         VARCHAR(100),
  QuantityExported INT NOT NULL DEFAULT 0,
  KEY idx_export (ExportOrderID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
