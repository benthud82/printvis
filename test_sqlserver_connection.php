<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check PHP details
echo "PHP Details:<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "PHP Architecture: " . (PHP_INT_SIZE == 8 ? '64-bit' : '32-bit') . "<br>";
echo "PHP Thread Safety: " . (PHP_ZTS ? 'Enabled' : 'Disabled') . "<br>";
echo "Operating System: " . PHP_OS . "<br>";

// Check for required extensions
echo "<br>Required Extensions:<br>";
echo "SQLSRV Extension loaded: " . (extension_loaded('sqlsrv') ? 'Yes' : 'No') . "<br>";
echo "PDO_SQLSRV Extension loaded: " . (extension_loaded('pdo_sqlsrv') ? 'Yes' : 'No') . "<br>";

// Show PHP extension directory
echo "<br>PHP Extension Directory: " . ini_get('extension_dir') . "<br>";

// Show all loaded extensions
echo "<br>Loaded extensions:<br>";
print_r(get_loaded_extensions());
echo "<br><br>";

// Check ODBC drivers
echo "<br>Available ODBC Drivers:<br>";
$drivers = odbc_drivers();
if ($drivers) {
    foreach($drivers as $driver) {
        echo $driver . "<br>";
    }
} else {
    echo "No ODBC drivers found.<br>";
}

try {
    // Try connection with explicit driver version
    $dbh = new PDO("sqlsrv:Server=USTXGPSQLVDP;Database=Local_PickingSupervisor;Driver={ODBC Driver 18 for SQL Server}", "dallasvoice", "dallasvoice");
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Successfully connected to SQL Server!<br>";
    
    // Test a simple query
    $test_query = $dbh->prepare("SELECT TOP 1 * FROM [Local_PickingSupervisor].[dbo].[Task]");
    $test_query->execute();
    $result = $test_query->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "Successfully executed test query!<br>";
        echo "Sample data from Task table:<br>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "<br>";
    
    // Additional error details that might be helpful
    echo "Error Code: " . $e->getCode() . "<br>";
    if ($e->errorInfo) {
        echo "SQL State: " . $e->errorInfo[0] . "<br>";
        echo "Driver Error Code: " . $e->errorInfo[1] . "<br>";
        echo "Driver Error Message: " . $e->errorInfo[2] . "<br>";
    }
}

// Test connection properties if successful
if (isset($dbh)) {
    echo "<br>Connection Properties:<br>";
    echo "Client Version: " . $dbh->getAttribute(PDO::ATTR_CLIENT_VERSION) . "<br>";
    echo "Server Version: " . $dbh->getAttribute(PDO::ATTR_SERVER_VERSION) . "<br>";
    echo "Connection Status: " . $dbh->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "<br>";
    echo "Driver Name: " . $dbh->getAttribute(PDO::ATTR_DRIVER_NAME) . "<br>";
}

?> 