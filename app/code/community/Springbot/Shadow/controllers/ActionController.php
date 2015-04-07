<?php
class Springbot_Shadow_ActionController extends Springbot_Shadow_Controller_Action
{
	public function viewAction()
	{
		$params = $this->getRequest()->getParams();

		$params['type'] = 'view';
		$params['visitor_ip'] = Mage::helper('core/http')->getRemoteAddr(true);

		Springbot_Boss::insertEvent($params);

		// return 1x1 pixel transparent gif
		$this->getResponse()->setHeader('Content-type', 'image/gif');
		// needed to avoid cache time on browser side
		$this->getResponse()->setHeader('Content-Length', '42');
		$this->getResponse()->setHeader('Cache-Control', 'private, no-cache, no-cache=Set-Cookie, proxy-revalidate');
		$this->getResponse()->setHeader('Expires', 'Wed, 11 Jan 2000 12:59:00 GMT');
		$this->getResponse()->setHeader('Last-Modified', 'Wed, 11 Jan 2006 12:59:00 GMT');
		$this->getResponse()->setHeader('Pragma', 'no-cache');
		$this->getResponse()->setBody(sprintf('%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%',71,73,70,56,57,97,1,0,1,0,128,255,0,192,192,192,0,0,0,33,249,4,1,0,0,0,0,44,0,0,0,0,1,0,1,0,0,2,2,68,1,0,59));
	}
}
