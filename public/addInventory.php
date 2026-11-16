<?php
// Items are now added from the inventory list (searchInventory.php) in a side panel.
// Kept so old links and bookmarks still work: opens that panel.
header("Location: searchInventory.php?add=1");
exit();
