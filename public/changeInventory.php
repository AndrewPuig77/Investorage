<?php
// Items are now edited from the inventory list (searchInventory.php), in a panel beside each row.
// Kept so old links and bookmarks still work; ?sku= opens that item's edit panel.
$sku = $_GET['sku'] ?? '';
header("Location: searchInventory.php" . (is_string($sku) && $sku !== '' ? '?' . http_build_query(['search' => $sku, 'edit' => $sku]) : ''));
exit();
