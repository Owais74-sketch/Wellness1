<?php
chdir('D:/laragon/www/wellness');
require 'wp-load.php';

echo "=== CATEGORIES ===\n";
$cats = get_categories(['hide_empty'=>false]);
foreach($cats as $c) echo $c->term_id.' | '.$c->slug.' | '.$c->name."\n";

echo "\n=== POSTS ===\n";
$posts = get_posts(['post_type'=>'post','numberposts'=>-1,'post_status'=>'any']);
foreach($posts as $p) echo $p->ID.' | '.$p->post_name.' | '.$p->post_title."\n";

echo "\n=== PAGES ===\n";
$pages = get_posts(['post_type'=>'page','numberposts'=>-1,'post_status'=>'any']);
foreach($pages as $pg) echo $pg->ID.' | '.$pg->post_name.' | '.$pg->post_title.' | '.$pg->post_status."\n";

echo "\n=== ALL USERS ===\n";
$all_users = get_users();
foreach($all_users as $u) echo $u->ID.' | '.$u->user_login.' | '.$u->display_name.' | '.implode(',',$u->roles)."\n";

echo "\n=== CONSULT TYPES ===\n";
$types = get_posts(['post_type'=>'wc_consult_type','numberposts'=>-1,'post_status'=>'any']);
foreach($types as $t) echo $t->ID.' | '.$t->post_title."\n";

echo "\n=== CUSTOMIZER MODS (sample) ===\n";
$mods = get_option('theme_mods_wellness-theme', []);
foreach(array_slice($mods,0,10,true) as $k=>$v) echo $k.': '.(is_string($v)?substr($v,0,80):'[non-string]')."\n";

echo "\n=== PLUGIN OPTIONS ===\n";
echo 'wc_consultations_enabled: '.get_option('wc_consultations_enabled','NOT SET')."\n";
echo 'wc_questions_enabled: '.get_option('wc_questions_enabled','NOT SET')."\n";
echo 'wc_payment_test_mode: '.get_option('wc_payment_test_mode','NOT SET')."\n";
echo 'blog_public: '.get_option('blog_public','NOT SET')."\n";
echo 'blogdescription: '.get_option('blogdescription','NOT SET')."\n";
