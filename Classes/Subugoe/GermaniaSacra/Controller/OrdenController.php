<?php
namespace Subugoe\GermaniaSacra\Controller;

use TYPO3\Flow\Annotations as Flow;
use Subugoe\GermaniaSacra\Domain\Model\Orden;
use Subugoe\GermaniaSacra\Domain\Model\Url;
use Subugoe\GermaniaSacra\Domain\Model\OrdenHasUrl;

class OrdenController extends AbstractBaseController {

	/**
	 * @Flow\Inject
	 * @var \Subugoe\GermaniaSacra\Domain\Repository\OrdenRepository
	 */
	protected $ordenRepository;

	/**
	 * @Flow\Inject
	 * @var \Subugoe\GermaniaSacra\Domain\Repository\OrdenstypRepository
	 */
	protected $ordenstypRepository;

	/**
	 * @Flow\Inject
	 * @var \Subugoe\GermaniaSacra\Domain\Repository\OrdenHasUrlRepository
	 */
	protected $ordenHasUrlRepository;

	/**
	 * @Flow\Inject
	 * @var \Subugoe\GermaniaSacra\Domain\Repository\UrlRepository
	 */
	protected $urlRepository;

	/**
	 * @Flow\Inject
	 * @var \Subugoe\GermaniaSacra\Domain\Repository\UrltypRepository
	 */
	protected $urltypRepository;

	/**
	 * @Flow\Inject
	 * @var \Subugoe\GermaniaSacra\Domain\Repository\BistumRepository
	 */
	protected $bistumRepository;

	/**
	 * @Flow\Inject
	 * @var \Subugoe\GermaniaSacra\Domain\Repository\KlosterordenRepository
	 */
	protected $klosterordenRepository;

	/**
	 * @var array
	 */
	protected $supportedMediaTypes = array('text/html', 'application/json');

	/**
	 * @var array
	 */
	protected $viewFormatToObjectNameMap = array(
			'json' => 'TYPO3\\Flow\\Mvc\\View\\JsonView',
			'html' => 'TYPO3\\Fluid\\View\\TemplateView'
	);

	/**
	 * List of all Orden entities
	 * @return void
	 */
	public function listAction() {
		if ($this->request->getFormat() === 'json') {
			$this->view->setVariablesToRender(array('orden'));
		}
		$this->view->assign('orden', ['data' => $this->ordenRepository->findAll()]);
		$this->view->assign('bearbeiter', $this->bearbeiterObj->getBearbeiter());
	}

	/**
	 * Create a new Orden entity
	 * @return void
	 */
	public function createAction() {
		$ordenObj = new Orden();
		if (is_object($ordenObj)) {
			if (!$this->request->hasArgument('orden')) {
				$this->throwStatus(400, 'Orden name not provided', Null);
			}
			$ordenObj->setOrden($this->request->getArgument('orden'));
			$ordenObj->setOrdo($this->request->getArgument('ordo'));
			$ordenObj->setSymbol($this->request->getArgument('symbol'));
			$ordenObj->setGraphik($this->request->getArgument('graphik'));
			if ($this->request->hasArgument('ordenstyp_uid')) {
				$ordenstypUUID = $this->request->getArgument('ordenstyp_uid');
				$ordenstypObj = $this->ordenstypRepository->findByIdentifier($ordenstypUUID);
				$ordenObj->setOrdenstyp($ordenstypObj);
			}
			$this->ordenRepository->add($ordenObj);
			// Add GND if set
			if ($this->request->hasArgument('gnd')) {
				$gnd = $this->request->getArgument('gnd');
				if ($this->request->hasArgument('gnd_label')) {
					$gnd_label = $this->request->getArgument('gnd_label');
				}
				if (empty($gnd_label)) {
					$gndid = str_replace('http://d-nb.info/gnd/', '', trim($gnd));
					$gnd_label = $this->request->getArgument('orden') . ' [' . $gndid . ']';
				}
				if (isset($gnd) && !empty($gnd)) {
					$url = new Url();
					$url->setUrl($gnd);
					if (!empty($gnd_label)) {
						$url->setBemerkung($gnd_label);
					}
					$urlTypObj = $this->urltypRepository->findOneByName('GND');
					$url->setUrltyp($urlTypObj);
					$this->urlRepository->add($url);
					$urlUUID = $url->getUUID();
					$urlObj = $this->urlRepository->findByIdentifier($urlUUID);
					$ordenhasurl = new OrdenHasUrl();
					$ordenhasurl->setOrden($ordenObj);
					$ordenhasurl->setUrl($urlObj);
					$this->ordenHasUrlRepository->add($ordenhasurl);
				}
			}
			//Add Wikipedia if set
			if ($this->request->hasArgument('wikipedia')) {
				$wikipedia = $this->request->getArgument('wikipedia');
				if ($this->request->hasArgument('wikipedia_label')) {
					$wikipedia_label = $this->request->getArgument('wikipedia_label');
				}
				if (empty($wikipedia_label)) {
					$wikipedia_label = str_replace('http://de.wikipedia.org/wiki/', '', trim($wikipedia));
					$wikipedia_label = str_replace('_', ' ', $wikipedia_label);
					$wikipedia_label = rawurldecode($wikipedia_label);
				}
				if (isset($wikipedia) && !empty($wikipedia)) {
					$url = new Url();
					$url->setUrl($wikipedia);
					if (!empty($wikipedia_label)) {
						$url->setBemerkung($wikipedia_label);
					}
					$urlTypObj = $this->urltypRepository->findOneByName('Wikipedia');
					$url->setUrltyp($urlTypObj);
					$this->urlRepository->add($url);
					$urlUUID = $url->getUUID();
					$urlObj = $this->urlRepository->findByIdentifier($urlUUID);
					$ordenhasurl = new OrdenHasUrl();
					$ordenhasurl->setOrden($ordenObj);
					$ordenhasurl->setUrl($urlObj);
					$this->ordenHasUrlRepository->add($ordenhasurl);
				}
			}
			// Add Url if set
			if ($this->request->hasArgument('url')) {
				$urlArr = $this->request->getArgument('url');
				if (isset($urlArr) && !empty($urlArr)) {
					if ($this->request->hasArgument('url_typ')) {
						$urlTypArr = $this->request->getArgument('url_typ');
					}
					if ($this->request->hasArgument('links_label')) {
						$linksLabelArr = $this->request->getArgument('links_label');
					}
					if ((isset($urlArr) && !empty($urlArr)) && (isset($urlTypArr) && !empty($urlTypArr))) {
						foreach ($urlArr as $k => $url) {
							if (!empty($url)) {
								$urlObj = new Url();
								$urlObj->setUrl($url);
								$urlTypObj = $this->urltypRepository->findByIdentifier($urlTypArr[$k]);
								$urlTyp = $urlTypObj->getName();
								$urlObj->setUrltyp($urlTypObj);
								if (isset($linksLabelArr[$k]) && !empty($linksLabelArr[$k])) {
									$urlObj->setBemerkung($linksLabelArr[$k]);
								}
								else {
									$urlObj->setBemerkung($urlTyp);
								}
								$this->urlRepository->add($urlObj);
								$ordenhasurlObj = new OrdenHasUrl();
								$ordenhasurlObj->setOrden($ordenObj);
								$ordenhasurlObj->setUrl($urlObj);
								$this->ordenHasUrlRepository->add($ordenhasurlObj);
							}
						}
					}
				}
			}
			$this->persistenceManager->persistAll();
			$this->throwStatus(201, NULL, Null);
		}
		else {
			$this->throwStatus(400, 'Entity not available', Null);
		}
	}

	/**
	 * Edit an Orden entity
	 * @return array $ordenArr
	 */
	public function editAction() {
		if ($this->request->hasArgument('uUID')) {
			$uuid = $this->request->getArgument('uUID');
		}
		if (empty($uuid)) {
			$this->throwStatus(400, 'Required uUID not provided', Null);
		}
		$ordenArr = array();
		$ordenObj = $this->ordenRepository->findByIdentifier($uuid);
		$ordenArr['uUID'] = $ordenObj->getUUID();
		$ordenArr['orden'] = $ordenObj->getOrden();
		$ordenArr['ordo'] = $ordenObj->getOrdo();
		$ordenstyp = $ordenObj->getOrdenstyp();
		if ($ordenstyp) {
			$ordenArr['ordenstyp'] = $ordenstyp->getUUID();
		}
		else {
			$ordenArr['ordenstyp'] = '';
		}
		$ordenArr['graphik'] = $ordenObj->getGraphik();
		$ordenArr['symbol'] = $ordenObj->getSymbol();
		$Urls = array();
		$ordenHasUrls = $ordenObj->getOrdenHasUrls();
		foreach ($ordenHasUrls as $k => $ordenHasUrl) {
			$urlObj = $ordenHasUrl->getUrl();
			$url = rawurldecode($urlObj->getUrl());
			$url_bemerkung = $urlObj->getBemerkung();
			if ($url !== 'keine Angabe') {
				$urlTypObj = $urlObj->getUrltyp();
				if (is_object($urlTypObj)) {
					$urlTyp = $urlTypObj->getUUID();
					$urlTypName = $urlTypObj->getName();
					if ($urlTypName == 'GND' || $urlTypName == 'Wikipedia') {
						$Urls[$k] = array('url_typ' => $urlTyp, 'url' => $url, 'url_label' => $url_bemerkung, 'url_typ_name' => $urlTypName);
					}
					else {
						$Urls[$k] = array('url_typ' => $urlTyp, 'url' => $url, 'links_label' => $url_bemerkung, 'url_typ_name' => $urlTypName);
					}
				}
			}
		}
		$ordenArr['url'] = $Urls;

		return json_encode($ordenArr);
	}

	/**
	 * Update an Orden entity
	 * @return void
	 */
	public function updateAction() {
		if ($this->request->hasArgument('uUID')) {
			$uuid = $this->request->getArgument('uUID');
		}
		if (empty($uuid)) {
			$this->throwStatus(400, 'Required uUID not provided', Null);
		}
		$ordenObj = $this->ordenRepository->findByIdentifier($uuid);
		if (is_object($ordenObj)) {
			$ordenObj->setOrden($this->request->getArgument('orden'));
			$ordenObj->setOrdo($this->request->getArgument('ordo'));
			$ordenObj->setSymbol($this->request->getArgument('symbol'));
			$ordenObj->setGraphik($this->request->getArgument('graphik'));
			if ($this->request->hasArgument('ordenstyp_uid')) {
				$ordenstypUUID = $this->request->getArgument('ordenstyp_uid');
				$ordenstypObj = $this->ordenstypRepository->findByIdentifier($ordenstypUUID);
				$ordenObj->setOrdenstyp($ordenstypObj);
			}
			$this->ordenRepository->update($ordenObj);
			// Fetch Orden Urls
			$ordenHasUrls = $ordenObj->getOrdenHasUrls();
			$ordenHasGND = false;
			// Update GND if set
			if ($this->request->hasArgument('gnd')) {
				$gnd = $this->request->getArgument('gnd');
				if ($this->request->hasArgument('gnd_label')) {
					$gnd_label = $this->request->getArgument('gnd_label');
				}
				if (empty($gnd_label)) {
					$gndid = str_replace('http://d-nb.info/gnd/', '', trim($gnd));
					$gnd_label = $this->request->getArgument('orden') . ' [' . $gndid . ']';
				}
				if (isset($gnd) && !empty($gnd)) {
					if (!empty($ordenHasUrls)) {
						foreach ($ordenHasUrls as $i => $ordenHasUrl) {
							$urlObj = $ordenHasUrl->getUrl();
							$urlTypObj = $urlObj->getUrltyp();
							$urlTyp = $urlTypObj->getName();
							if ($urlTyp == "GND") {
								$urlObj->setUrl($gnd);
								if (!empty($gnd_label)) {
									$urlObj->setBemerkung($gnd_label);
								}
								$this->urlRepository->update($urlObj);
								$ordenHasGND = true;
							}
						}
					}
					if (!$ordenHasGND) {
						$url = new Url();
						$url->setUrl($gnd);
						if (!empty($gnd_label)) {
							$url->setBemerkung($gnd_label);
						}
						$urlTypObj = $this->urltypRepository->findOneByName('GND');
						$url->setUrltyp($urlTypObj);
						$this->urlRepository->add($url);
						$urlUUID = $url->getUUID();
						$urlObj = $this->urlRepository->findByIdentifier($urlUUID);
						$ordenhasurl = new OrdenHasUrl();
						$ordenhasurl->setOrden($ordenObj);
						$ordenhasurl->setUrl($urlObj);
						$this->ordenHasUrlRepository->add($ordenhasurl);
					}
				}
			}
			//Update Wikipedia if set
			$ordenHasWiki = false;
			if ($this->request->hasArgument('wikipedia')) {
				$wikipedia = $this->request->getArgument('wikipedia');

				if ($this->request->hasArgument('wikipedia_label')) {
					$wikipedia_label = $this->request->getArgument('wikipedia_label');
				}
				if (empty($wikipedia_label)) {
					$wikipedia_label = str_replace('http://de.wikipedia.org/wiki/', '', trim($wikipedia));
					$wikipedia_label = str_replace('_', ' ', $wikipedia_label);
					$wikipedia_label = rawurldecode($wikipedia_label);
				}
				if (isset($wikipedia) && !empty($wikipedia)) {
					foreach ($ordenHasUrls as $i => $ordenHasUrl) {
						$urlObj = $ordenHasUrl->getUrl();
						$url = $urlObj->getUrl();
						$urlTypObj = $urlObj->getUrltyp();
						$urlTyp = $urlTypObj->getName();
						if ($urlTyp == "Wikipedia") {
							$urlObj->setUrl($wikipedia);
							if (!empty($wikipedia_label)) {
								$urlObj->setBemerkung($wikipedia_label);
							}
							$this->urlRepository->update($urlObj);
							$ordenHasWiki = true;
						}
					}
					if (!$ordenHasWiki) {
						$url = new Url();
						$url->setUrl($wikipedia);
						if (!empty($wikipedia_label)) {
							$url->setBemerkung($wikipedia_label);
						}
						$urlTypObj = $this->urltypRepository->findOneByName('Wikipedia');
						$url->setUrltyp($urlTypObj);
						$this->urlRepository->add($url);
						$urlUUID = $url->getUUID();
						$urlObj = $this->urlRepository->findByIdentifier($urlUUID);
						$ordenhasurl = new OrdenHasUrl();
						$ordenhasurl->setOrden($ordenObj);
						$ordenhasurl->setUrl($urlObj);
						$this->ordenHasUrlRepository->add($ordenhasurl);
					}
				}
			}
			// Add Url if set
			if ($this->request->hasArgument('url')) {
				$urlArr = $this->request->getArgument('url');
				if (isset($urlArr) && !empty($urlArr)) {
					if ($this->request->hasArgument('url_typ')) {
						$urlTypArr = $this->request->getArgument('url_typ');
					}

					if ($this->request->hasArgument('links_label')) {
						$linksLabelArr = $this->request->getArgument('links_label');
					}
					if ((isset($urlArr) && !empty($urlArr)) && (isset($urlTypArr) && !empty($urlTypArr))) {
						foreach ($ordenHasUrls as $i => $ordenHasUrl) {
							$urlObj = $ordenHasUrl->getUrl();
							$url = $urlObj->getUrl();
							$urlTypObj = $urlObj->getUrltyp();
							$urlTyp = $urlTypObj->getName();
							if ($urlTyp != "Wikipedia" && $urlTyp != "GND") {
								$this->ordenHasUrlRepository->remove($ordenHasUrl);
								$this->urlRepository->remove($urlObj);
							}
						}
						foreach ($urlArr as $k => $url) {
							if (!empty($url)) {
								$urlObj = new Url();
								$urlObj->setUrl($url);
								$urlTypObj = $this->urltypRepository->findByIdentifier($urlTypArr[$k]);
								$urlTyp = $urlTypObj->getName();
								$urlObj->setUrltyp($urlTypObj);
								if (isset($linksLabelArr[$k]) && !empty($linksLabelArr[$k])) {
									$urlObj->setBemerkung($linksLabelArr[$k]);
								}
								else {
									$urlObj->setBemerkung($urlTyp);
								}
								$this->urlRepository->add($urlObj);
								$ordenhasurlObj = new OrdenHasUrl();
								$ordenhasurlObj->setOrden($ordenObj);
								$ordenhasurlObj->setUrl($urlObj);
								$this->ordenHasUrlRepository->add($ordenhasurlObj);
							}
						}
					}
				}
			}
			$this->persistenceManager->persistAll();
			$this->throwStatus(200, NULL, Null);
		}
		else {
			$this->throwStatus(400, 'Entity Orden not available', Null);
		}
	}

	/**
	 * Delete an Orden entity
	 * @return void
	 */
	public function deleteAction() {
		if ($this->request->hasArgument('uUID')) {
			$uuid = $this->request->getArgument('uUID');
		}
		if (empty($uuid)) {
			$this->throwStatus(400, 'Required uUID not provided', Null);
		}
		$klosterordens = count($this->klosterordenRepository->findByOrden($uuid));
		$ordenhasurls = count($this->ordenHasUrlRepository->findByOrden($uuid));
		if ($klosterordens == 0 && $ordenhasurls == 0) {
			$ordenObj = $this->ordenRepository->findByIdentifier($uuid);
			if (!is_object($ordenObj)) {
				$this->throwStatus(400, 'Entity Orden not available', Null);
			}
			$this->ordenRepository->remove($ordenObj);
			// Fetch Orden Urls
			$ordenHasUrls = $ordenObj->getOrdenHasUrls();
			if (is_array($ordenHasUrls)) {
				foreach ($ordenHasUrls as $ordenHasUrl) {
					$this->ordenHasUrlRepository->remove($ordenHasUrl);
				}
			}
			$this->throwStatus(200, NULL, Null);
		}
		else {
			$this->throwStatus(400, 'Due to dependencies Orden entity could not be deleted', Null);
		}
	}

	/**
	 * Update a list of Orden entities
	 * @return void
	 */
	public function updateListAction() {
		if ($this->request->hasArgument('data')) {
			$ordenlist = $this->request->getArgument('data');
		}
		if (empty($ordenlist)) {
			$this->throwStatus(400, 'Required data arguemnts not provided', Null);
		}
		foreach ($ordenlist as $uuid => $orden) {
			$ordenObj = $this->ordenRepository->findByIdentifier($uuid);
			$ordenObj->setOrden($orden['orden']);
			$ordenObj->setOrdo($orden['ordo']);
			$ordenObj->setSymbol($orden['symbol']);
			$ordenObj->setGraphik($orden['graphik']);
			$this->ordenRepository->update($ordenObj);
		}
		$this->persistenceManager->persistAll();
		$this->throwStatus(200, NULL, Null);
	}
}
?>