<?php
/**
 * Plugin Name: Priyadarshini Blocks
 * Description: Presentation for the Priyadarshini marketplace: registered blocks and Interactivity API stores. Data and rules live in pd-marketplace.
 * Version: 0.1.0
 * Requires PHP: 8.1
 * Requires at least: 6.6
 * License: GPL-2.0-or-later
 * Text Domain: pd-blocks
 */

namespace PD\Blocks;

defined( 'ABSPATH' ) || exit;

const VERSION = '0.1.0';
const DIR     = __DIR__;

require_once __DIR__ . '/src/State.php';
require_once __DIR__ . '/src/Plugin.php';

add_action( 'plugins_loaded', array( Plugin::class, 'boot' ) );
