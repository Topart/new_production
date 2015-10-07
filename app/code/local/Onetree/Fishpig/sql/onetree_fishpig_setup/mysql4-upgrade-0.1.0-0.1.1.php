<?php
/**
 * Created by PhpStorm.
 * User: ikis1520
 * Date: 11/08/2015
 * Time: 23:37
 */
$installer = $this;

$installer->startSetup();

$inchooSwitch = new Mage_Core_Model_Config();

$inchooSwitch ->saveConfig('wordpress/social/buttons_html', '
<span class="st_facebook" st_title="<?php the_title(); ?>" st_url="<?php the_permalink(); ?>"></span>
<span href="https://twitter.com/share" class="st_twitter" data-url="<?php the_permalink(); ?>"  data-count="none"></span>
<span st_title="<?php the_title(); ?>" st_url="<?php the_permalink(); ?>" class="st_pinterest"></span>', 'default', 0);

$installer->endSetup();