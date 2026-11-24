<?php defined( 'ABSPATH' ) || exit; ?><!doctype html>
<html <?php language_attributes(); ?> data-base="<?php echo esc_url( get_template_directory_uri() . '/' ); ?>">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?><?php echo bg_body_attrs(); // phpcs:ignore WordPress.Security.EscapeOutput -- bg_body_attrs() escapel ?>>
<?php wp_body_open(); ?>
