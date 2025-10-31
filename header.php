<!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PHP Media Playback Server</title>
    
  <!-- Display-specific meta tags -->
  <meta name="robots" content="noindex, nofollow">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">

  <!-- Load CSS files in correct order -->
  <link rel="stylesheet" href="styles/base.css">
  <link rel="stylesheet" href="styles/components.css">
  <link rel="stylesheet" href="styles/main.css">
  <link rel="stylesheet" href="styles/setup.css">
  <link rel="stylesheet" href="styles/media-queries.css">

  <link rel="icon" href="favicon.ico">
    
  <!-- Optional: Development cache control -->
  <?php if (isset($Settings['debug']) && $Settings['debug']): ?>
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <?php endif; ?>
 </head>
