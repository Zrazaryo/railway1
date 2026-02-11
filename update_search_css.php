<?php
/**
 * Script untuk menambahkan CSS search components ke semua halaman yang telah diupdate
 */

$files_to_update = [
    'documents/pemusnahan.php',
    'documents/index.php', 
    'logs/index.php',
    'lockers/select.php',
    'lockers/detail.php'
];

$css_link = '<link href="../assets/css/search-components.css" rel="stylesheet">';

foreach ($files_to_update as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check if CSS already added
        if (strpos($content, 'search-components.css') === false) {
            // Find the last CSS link and add after it
            $pattern = '/(<link[^>]*\.css[^>]*>)(?!.*<link[^>]*\.css)/s';
            
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, '$1' . "\n    " . $css_link, $content);
                
                if (file_put_contents($file, $content)) {
                    echo "✅ Updated: $file\n";
                } else {
                    echo "❌ Failed to update: $file\n";
                }
            } else {
                echo "⚠️  Could not find CSS pattern in: $file\n";
            }
        } else {
            echo "ℹ️  Already updated: $file\n";
        }
    } else {
        echo "❌ File not found: $file\n";
    }
}

echo "\n🎉 CSS update process completed!\n";
?>