<?php
// Create JAKPUS Logo PNG using GD library
$width = 800;
$height = 800;

// Create image
$image = imagecreatetruecolor($width, $height);

// Colors (RGB)
$gold = imagecolorallocate($image, 212, 175, 55);
$dark_blue = imagecolorallocate($image, 26, 42, 58);
$light_blue = imagecolorallocate($image, 42, 74, 106);
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);

// Fill background (transparent-like dark)
imagefill($image, 0, 0, $dark_blue);

// Outer gold circle
imagefilledellipse($image, 400, 400, 760, 760, $gold);

// Inner dark blue circle with gradient effect
imagefilledellipse($image, 400, 400, 700, 700, $dark_blue);

// Inner blue circles for gradient
imagefilledellipse($image, 400, 400, 680, 680, $light_blue);

// Star at top (5-pointed)
$star_cx = 400;
$star_cy = 150;
$star_size = 50;
$points = array();
for ($i = 0; $i < 5; $i++) {
    $angle = ($i * 4 * M_PI / 5) - M_PI / 2;
    $points[] = $star_cx + $star_size * cos($angle);
    $points[] = $star_cy + $star_size * sin($angle);
}
imagefilledpolygon($image, $points, 5, $gold);

// Building/Tower lines
$tower_left = 330;
$tower_top = 200;
$tower_width = 140;
$tower_height = 200;

// Draw tower block
imagefilledellipse($image, 400, 300, $tower_width, $tower_height, $gold);

// Horizontal lines in tower
for ($i = 1; $i < 8; $i++) {
    $y = $tower_top + ($tower_height / 8) * $i;
    imageline($image, $tower_left, $y, $tower_left + $tower_width, $y, $dark_blue);
}

// Draw circular emblem outline
imageellipse($image, 400, 400, 650, 650, $gold);
imageellipse($image, 400, 400, 640, 640, $light_blue);

// Banner circles (IMIGRASI)
imagefilledellipse($image, 400, 420, 300, 100, $white);
imagestringup($image, 5, 360, 440, 'IMIGRASI', $dark_blue);

// Save PNG
$path = dirname(__FILE__) . '/assets/images/jakpus-logo.png';
$result = imagepng($image, $path);
imagedestroy($image);

if ($result) {
    echo "✓ PNG logo created successfully at: " . $path;
} else {
    echo "✗ Failed to create PNG logo";
}
?>
