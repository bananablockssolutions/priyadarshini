<?php
/** DEV ONLY: stand-ins for things WooCommerce provides, so M-1 spikes can run where WooCommerce is not installed. Never ship. */
add_action('init', function(){
  if (!taxonomy_exists('product_cat')) register_taxonomy('product_cat', array(), array('hierarchical'=>true,'public'=>true,'show_in_rest'=>true,'label'=>'Product categories (STAND-IN)'));
}, 1);
// Stand-in for the WooCommerce product post type (comments of type `review` attach to it, as in WooCommerce).
add_action('init', function(){ if(!post_type_exists('product')) register_post_type('product', array('public'=>true,'label'=>'Products (STAND-IN)','supports'=>array('title','comments'))); }, 1);
