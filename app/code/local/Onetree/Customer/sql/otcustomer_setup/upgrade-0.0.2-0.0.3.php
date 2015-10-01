<?php
/**
 * Created by PhpStorm.
 * User: ikis1520
 * Date: 20/07/15
 * Time: 21:45
 */

$content = <<<EOT

<div class="collapsible mobile-collapsible">

	<h6 class="block-title heading">Do Business With Us</h6>
	<div class="block-content">

		<ul class="bullet">
			<li><a href="{{store url='do-business-with-us'}}">Learn more</a></li>
			<li><a href="{{store url='faq-for-business'}}">FAQ For Business</a></li>
		</ul>

	</div>

</div>

EOT;
$content2
    = <<<EOT

<div class="collapsible mobile-collapsible">

	<h6 class="block-title heading">About Us</h6>
	<div class="block-content">

		<ul class="bullet">
			<li><a href="{{store url='about-topart'}}">About Topart</a></li>
			<li><a href="{{store url='privacy-policy'}}">Privacy Policy</a></li>
                        <li><a href="{{store url='tos'}}">Terms of Service</a></li>
		</ul>

	</div>

</div>

EOT;

$cmsBlock = Mage::getModel('cms/block')->load('block_footer_column4', 'identifier');
$cmsBlock_2 = Mage::getModel('cms/block')->load('block_footer_column2', 'identifier');

if ($cmsBlock->isObjectNew()) {
    $cmsBlock->setIdentifier('block_footer_column4')
        ->setStores(array(0))
        ->setIsActive(true)
        ->setTitle('Footer column 4');
}
$cmsBlock->setContent($content)->save();

if ($cmsBlock_2->isObjectNew()) {
    $cmsBlock_2->setIdentifier('block_footer_column2')
        ->setStores(array(0))
        ->setIsActive(true)
        ->setTitle('Footer column 2');
}
$cmsBlock_2->setContent($content2)->save();