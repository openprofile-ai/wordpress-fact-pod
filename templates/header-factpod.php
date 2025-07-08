<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php bloginfo('name'); ?> – Custom Page</title>
    <?php wp_head(); ?>
    <style>
        body {
            font-family: system-ui, sans-serif;
            padding: 2rem;
            background: #f9f9f9;
        }
        .custom-wrap {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 2rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
         header .main-navigation, .main-header,
         .site-header .menu, .screen-reader-text.skip-link,
         nav,
         .primary-menu {
             display: none !important;
         }
        .fact-pod-form{
            width: 75%;
            margin: 45px auto;
        }
        .fact-pod-form h1, .fact-pod-form h2, .fact-pod-form h3{
            text-align: center;
        }
    </style>
</head>
<body <?php body_class(); ?>>