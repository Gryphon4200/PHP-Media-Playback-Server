<?php
include_once 'functions.php';

echo "<h2>Preset Update Debug</h2>";
echo "<pre>";

echo "POST Data:\n";
print_r($_POST);

echo "\nFILES Data:\n";
print_r($_FILES);

echo "\nCurrent Presets:\n";
print_r($Presets);

echo "\nCurrent Settings:\n";
print_r($Settings);

// Test the update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update']) && $_POST['update'] === 'ConfigFileUpdate') {
    echo "\n=== TESTING UPDATE LOGIC ===\n";
    
    try {
        // Build new configuration (same logic as update.php)
        $json_data = ['path' => $ServerPath];
        $updated_presets = [];
        
        foreach ($_POST as $key => $value) {
            if ($key !== "update") {
                $clean_key = preg_replace('/[^a-zA-Z0-9_\-]/', '', $key);
                $clean_value = trim($value);
                
                echo "Processing: {$key} = {$value} (cleaned: {$clean_key} = {$clean_value})\n";
                
                if (!empty($clean_key) && !empty($clean_value)) {
                    $json_data[$clean_key] = $clean_value;
                    if ($clean_key !== 'path') {
                        $updated_presets[$clean_key] = $clean_value;
                    }
                }
            }
        }
        
        echo "\nNew JSON data structure:\n";
        print_r($json_data);
        
        echo "\nUpdated presets:\n";
        print_r($updated_presets);
        
        // Test JSON encoding
        $json_text = json_encode($json_data, JSON_PRETTY_PRINT);
        echo "\nJSON to write:\n{$json_text}\n";
        
        // Test file writing (but don't actually write yet)
        $can_write = is_writable('config.json') || is_writable('.');
        echo "\nCan write to config.json: " . ($can_write ? 'YES' : 'NO') . "\n";
        
        if (isset($_POST['actually_update'])) {
            echo "\n=== ACTUALLY UPDATING ===\n";
            if (file_put_contents("config.json", $json_text) !== false) {
                echo "SUCCESS: Config file updated!\n";
            } else {
                echo "ERROR: Failed to write config file!\n";
            }
        } else {
            echo "\nTo actually update, add '&actually_update=1' to the URL\n";
        }
        
    } catch (Exception $e) {
        echo "\nERROR: " . $e->getMessage() . "\n";
    }
}

echo "</pre>";

// Simple test form
?>
<h3>Test Preset Update Form</h3>
<form method="post">
    <input type="hidden" name="update" value="ConfigFileUpdate">
    
    <p>Preset 1: <input type="text" name="1" value="<?php echo htmlspecialchars($Presets['1'] ?? ''); ?>"></p>
    <p>Preset 2: <input type="text" name="2" value="<?php echo htmlspecialchars($Presets['2'] ?? ''); ?>"></p>
    <p>Preset 3: <input type="text" name="3" value="<?php echo htmlspecialchars($Presets['3'] ?? ''); ?>"></p>
    
    <p><input type="submit" value="Test Update (Dry Run)"></p>
</form>

<form method="post">
    <input type="hidden" name="update" value="ConfigFileUpdate">
    <input type="hidden" name="actually_update" value="1">
    
    <p>Preset 1: <input type="text" name="1" value="<?php echo htmlspecialchars($Presets['1'] ?? ''); ?>"></p>
    <p>Preset 2: <input type="text" name="2" value="<?php echo htmlspecialchars($Presets['2'] ?? ''); ?>"></p>
    <p>Preset 3: <input type="text" name="3" value="<?php echo htmlspecialchars($Presets['3'] ?? ''); ?>"></p>
    
    <p><input type="submit" value="Test Update (ACTUALLY UPDATE)"></p>
</form>
