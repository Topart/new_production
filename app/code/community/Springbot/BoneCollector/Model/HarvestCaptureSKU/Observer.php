<?php
/**
 * BoneCollector Event Listener (Capture SKU when Product Viewed)
 *
 * @version		v1.0.0 - 5/28/2013
 *
 * @category    Magento Integrations
 * @package     springbot
 * @author		William Seitz
 * @division	SpringBot Integration Team
 * @support		magentosupport@springbot.com
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class Springbot_BoneCollector_Model_HarvestCaptureSKU_Observer extends Springbot_BoneCollector_Model_HarvestAbstract
{
	const VERSION						= '1.2.0';
	const METHOD						= 'getsku';
	const LISTENER_PUBLIC_NAME			= 'CaptureSKU Harvesting Listener: ';
	const ZERO							= 0;

	public function captureskuharvest($observer)
	{
		$controllerName = Mage::app()->getRequest()->getControllerName();
		$actionName     = Mage::app()->getRequest()->getActionName();
		$routerName     = Mage::app()->getRequest()->getRouteName();

		if ($this->viewingProductDetailPage($controllerName,$actionName,$routerName)) {
			$this->_initObserver($observer);
			$productData = $observer->getEvent()->getProduct();
			$entityViewed         = $productData['sku'];
			$eventDatetime        = date("Y-m-d H:i:s");
			$openModeAppend       = 'a';
			$eventHistoryFilename = Mage::getBaseDir().'/var/log/Springbot-EventHistory.csv';
			$urlViewed            = Mage::helper('core/url')->getCurrentUrl();
			$user_agent           = Mage::helper('core/http')->getHttpUserAgent();

			if ($this->qualifyURL($urlViewed,$user_agent)) {
				try {
					$lastCatId     = $this->_getLastCategory();
					$visitorIP     = Mage::helper('core/http')->getRemoteAddr(true);
					$storeId       = Mage::app()->getStore()->getStoreId();
					$fHandle       = fopen($eventHistoryFilename,$openModeAppend);
					$viewedMessage = array('view',
						$eventDatetime,
						$urlViewed,
						$entityViewed,
						$visitorIP,
						$storeId,
						$lastCatId
					);
					fputcsv($fHandle,$viewedMessage,',');
					fclose ($fHandle);

				}  catch (Exception $e)  {
					Mage::log('Unknown exception opening '.$eventHistoryFilename);
				}
			}
		}
		return;
	}

	private function viewingProductDetailPage($controllerName,$actionName,$routerName)
	{
		if ($controllerName == 'product'
			&&  $actionName == 'view'
			&&  $routerName == 'catalog') {
				return true;
			} else {
				return false;
			}
	}

	protected function _getLastCategory()
	{
		return Mage::helper('combine')->getLastCategoryId();
	}

	private function qualifyURL($url,$user_agent)
	{
		$rtn = true;

		if (strpos($url,'/api')  >  self::ZERO
			|| strpos($url,'/ajax') >  self::ZERO
			|| strpos($url,'/soap') >  self::ZERO) {
				$rtn = false;
			} else {
				$rtn = false;
				if ($this->is_bot($user_agent) == false &&  $this->urlIsIPAddress($url)== false) {
					$rtn = true;
				}
			}
		return $rtn;
	}

	private function urlIsIPAddress($url)
	{
		$numericComponents = self::ZERO;
		$ipComponents      = explode('.',$url);

		foreach($ipComponents as $ipVal) {
			if (is_numeric($ipVal)) {
				$numericComponents++;
			}
		}
		if ($numericComponents == 4) {
			return true;
		} else {
			return false;
		}
	}

	private function is_bot($user_agent)	{
		$spiders = array(
			"abot",
			"dbot",
			"ebot",
			"hbot",
			"kbot",
			"lbot",
			"mbot",
			"nbot",
			"obot",
			"pbot",
			"rbot",
			"sbot",
			"tbot",
			"vbot",
			"ybot",
			"zbot",
			"bot/",
			"_bot",
			".bot",
			"/bot",
			"-bot",
			":bot",
			"(bot",
			"crawl",
			"slurp",
			"spider",
			"seek",
			"accoona",
			"acoon",
			"adressendeutschland",
			"ah-ha.com",
			"ahoy",
			"altavista",
			"ananzi",
			"anthill",
			"appie",
			"arachnophilia",
			"arale",
			"araneo",
			"aranha",
			"architext",
			"aretha",
			"arks",
			"asterias",
			"atlocal",
			"atn",
			"atomz",
			"augurfind",
			"backrub",
			"bannana_bot",
			"baypup",
			"bdfetch",
			"big brother",
			"biglotron",
			"bjaaland",
			"blackwidow",
			"blaiz",
			"blog",
			"blo.",
			"bloodhound",
			"boitho",
			"booch",
			"bradley",
			"butterfly",
			"calif",
			"cassandra",
			"ccubee",
			"cfetch",
			"charlotte",
			"churl",
			"cienciaficcion",
			"cmc",
			"collective",
			"comagent",
			"combine",
			"computingsite",
			"csci",
			"curl",
			"cusco",
			"daumoa",
			"deepindex",
			"delorie",
			"depspid",
			"deweb",
			"die blinde kuh",
			"digger",
			"ditto",
			"dmoz",
			"docomo",
			"download express",
			"dtaagent",
			"dwcp",
			"ebiness",
			"ebingbong",
			"e-collector",
			"ejupiter",
			"emacs-w3 search engine",
			"esther",
			"evliya celebi",
			"ezresult",
			"falcon",
			"felix ide",
			"ferret",
			"fetchrover",
			"fido",
			"findlinks",
			"fireball",
			"fish search",
			"fouineur",
			"funnelweb",
			"gazz",
			"gcreep",
			"genieknows",
			"getterroboplus",
			"geturl",
			"glx",
			"goforit",
			"golem",
			"grabber",
			"grapnel",
			"gralon",
			"griffon",
			"gromit",
			"grub",
			"gulliver",
			"hamahakki",
			"harvest",
			"havindex",
			"helix",
			"heritrix",
			"hku www octopus",
			"homerweb",
			"htdig",
			"html index",
			"html_analyzer",
			"htmlgobble",
			"hubater",
			"hyper-decontextualizer",
			"ia_archiver",
			"ibm_planetwide",
			"ichiro",
			"iconsurf",
			"iltrovatore",
			"image.kapsi.net",
			"imagelock",
			"incywincy",
			"indexer",
			"infobee",
			"informant",
			"ingrid",
			"inktomisearch.com",
			"inspector web",
			"intelliagent",
			"internet shinchakubin",
			"ip3000",
			"iron33",
			"israeli-search",
			"ivia",
			"jack",
			"jakarta",
			"javabee",
			"jetbot",
			"jumpstation",
			"katipo",
			"kdd-explorer",
			"kilroy",
			"knowledge",
			"kototoi",
			"kretrieve",
			"labelgrabber",
			"lachesis",
			"larbin",
			"legs",
			"libwww",
			"linkalarm",
			"link validator",
			"linkscan",
			"lockon",
			"lwp",
			"lycos",
			"magpie",
			"mantraagent",
			"mapoftheinternet",
			"marvin/",
			"mattie",
			"mediafox",
			"mediapartners",
			"mercator",
			"merzscope",
			"microsoft url control",
			"minirank",
			"miva",
			"mj12",
			"mnogosearch",
			"moget",
			"monster",
			"moose",
			"motor",
			"multitext",
			"muncher",
			"muscatferret",
			"mwd.search",
			"myweb",
			"najdi",
			"nameprotect",
			"nationaldirectory",
			"nazilla",
			"ncsa beta",
			"nec-meshexplorer",
			"nederland.zoek",
			"netcarta webmap engine",
			"netmechanic",
			"netresearchserver",
			"netscoop",
			"newscan-online",
			"nhse",
			"nokia6682/",
			"nomad",
			"noyona",
			"nutch",
			"nzexplorer",
			"objectssearch",
			"occam",
			"omni",
			"open text",
			"openfind",
			"openintelligencedata",
			"orb search",
			"osis-project",
			"pack rat",
			"pageboy",
			"pagebull",
			"page_verifier",
			"panscient",
			"parasite",
			"partnersite",
			"patric",
			"pear.",
			"pegasus",
			"peregrinator",
			"pgp key agent",
			"phantom",
			"phpdig",
			"picosearch",
			"piltdownman",
			"pimptrain",
			"pinpoint",
			"pioneer",
			"piranha",
			"plumtreewebaccessor",
			"pogodak",
			"poirot",
			"pompos",
			"poppelsdorf",
			"poppi",
			"popular iconoclast",
			"psycheclone",
			"publisher",
			"python",
			"rambler",
			"raven search",
			"roach",
			"road runner",
			"roadhouse",
			"robbie",
			"robofox",
			"robozilla",
			"rules",
			"salty",
			"sbider",
			"scooter",
			"scoutjet",
			"scrubby",
			"search.",
			"searchprocess",
			"semanticdiscovery",
			"senrigan",
			"sg-scout",
			"shai'hulud",
			"shark",
			"shopwiki",
			"sidewinder",
			"sift",
			"silk",
			"simmany",
			"site searcher",
			"site valet",
			"sitetech-rover",
			"skymob.com",
			"sleek",
			"smartwit",
			"sna-",
			"snappy",
			"snooper",
			"sohu",
			"speedfind",
			"sphere",
			"sphider",
			"spinner",
			"spyder",
			"steeler/",
			"suke",
			"suntek",
			"supersnooper",
			"surfnomore",
			"sven",
			"sygol",
			"szukacz",
			"tach black widow",
			"tarantula",
			"templeton",
			"/teoma",
			"t-h-u-n-d-e-r-s-t-o-n-e",
			"theophrastus",
			"titan",
			"titin",
			"tkwww",
			"toutatis",
			"t-rex",
			"tutorgig",
			"twiceler",
			"twisted",
			"ucsd",
			"udmsearch",
			"url check",
			"updated",
			"vagabondo",
			"valkyrie",
			"verticrawl",
			"victoria",
			"vision-search",
			"volcano",
			"voyager/",
			"voyager-hc",
			"w3c_validator",
			"w3m2",
			"w3mir",
			"walker",
			"wallpaper",
			"wanderer",
			"wauuu",
			"wavefire",
			"web core",
			"web hopper",
			"web wombat",
			"webbandit",
			"webcatcher",
			"webcopy",
			"webfoot",
			"weblayers",
			"weblinker",
			"weblog monitor",
			"webmirror",
			"webmonkey",
			"webquest",
			"webreaper",
			"websitepulse",
			"websnarf",
			"webstolperer",
			"webvac",
			"webwalk",
			"webwatch",
			"webwombat",
			"webzinger",
			"wget",
			"whizbang",
			"whowhere",
			"wild ferret",
			"worldlight",
			"wwwc",
			"wwwster",
			"xenu",
			"xget",
			"xift",
			"xirq",
			"yandex",
			"yanga",
			"yeti",
			"yodao",
			"zao/",
			"zippp",
			"zyborg"
		);
		foreach($spiders as $spider) {
			if (stripos($user_agent, $spider) !== false ) return true;
		}
		return false;
	}

}
