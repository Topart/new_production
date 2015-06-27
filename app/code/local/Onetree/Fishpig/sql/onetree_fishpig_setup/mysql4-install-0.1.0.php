<?php
/**
 * Created by PhpStorm.
 * User: ikis1520
 * Date: 25/06/15
 * Time: 23:47
 */ 
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$inchooSwitch = new Mage_Core_Model_Config();
$inchooSwitch ->saveConfig('wordpress/module/enabled', 1, 'default', 0);
$inchooSwitch ->saveConfig('wordpress/module/check_for_updates', 1, 'default', 0);
$inchooSwitch ->saveConfig('wordpress/database/is_shared', 0, 'default', 0);
$inchooSwitch ->saveConfig('wordpress/database/host', 'localhost', 'default', 0);
$inchooSwitch ->saveConfig('wordpress/database/username', 'WERbqceLxpLbGroZ52WHDw==', 'default', 0);
$inchooSwitch ->saveConfig('wordpress/database/password', 'n20O+OYDMeoM/u2msPcfzH2Tdf/ao12F', 'default', 0);
$inchooSwitch ->saveConfig('wordpress/database/dbname', 'WERbqceLxpJNIB0Znc9+VA==', 'default', 0);
$inchooSwitch ->saveConfig('wordpress/database/charset', 'utf8', 'default', 0);
$inchooSwitch ->saveConfig('wordpress/database/table_prefix', 'wp_', 'default', 0);
$inchooSwitch ->saveConfig('wordpress/social/enabled', 1, 'default', 0);
$inchooSwitch ->saveConfig('wordpress/social/service', 'sharethis', 'default', 0);
$inchooSwitch ->saveConfig('wordpress/social/head_html', '<script charset="utf-8" type="text/javascript">var switchTo5x=true;</script>
<script charset="utf-8" type="text/javascript" src="http://w.sharethis.com/button/buttons.js"></script>
<script charset="utf-8" type="text/javascript">stLight.options({"publisher":"685dccc5-e933-45bf-a14c-2a77b29ef015"});var st_type="wordpress4.0";</script>
', 'default', 0);
$inchooSwitch ->saveConfig('wordpress/social/buttons_html', '<span class="st_facebook" st_title="<?php the_title(); ?>" st_url="<?php the_permalink(); ?>"></span>
<span class="st_twitter" st_title="<?php the_title(); ?>" st_url="<?php the_permalink(); ?>"></span>
<span st_title="<?php the_title(); ?>" st_url="<?php the_permalink(); ?>" class="st_pinterest"></span>', 'default', 0);

$installer->endSetup();