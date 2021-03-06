<?php
namespace Subugoe\GermaniaSacra\Controller;

use TYPO3\Flow\Annotations as Flow;
use Subugoe\GermaniaSacra\Domain\Model\Band;
use Subugoe\GermaniaSacra\Domain\Model\Url;
use Subugoe\GermaniaSacra\Domain\Model\BandHasUrl;

class BandController extends AbstractBaseController
{
    /**
     * @Flow\Inject
     * @var \Subugoe\GermaniaSacra\Domain\Repository\BandRepository
     */
    protected $bandRepository;

    /**
     * @Flow\Inject
     * @var \Subugoe\GermaniaSacra\Domain\Repository\UrlRepository
     */
    protected $urlRepository;

    /**
     * @Flow\Inject
     * @var \Subugoe\GermaniaSacra\Domain\Repository\BistumRepository
     */
    protected $bistumRepository;

    /**
     * @Flow\Inject
     * @var \Subugoe\GermaniaSacra\Domain\Repository\UrltypRepository
     */
    protected $urltypRepository;

    /**
     * @Flow\Inject
     * @var \Subugoe\GermaniaSacra\Domain\Repository\BandHasUrlRepository
     */
    protected $bandHasUrlRepository;

    /**
     * @Flow\Inject
     * @var \Subugoe\GermaniaSacra\Domain\Repository\KlosterRepository
     */
    protected $klosterRepository;

    /**
     * @var array
     */
    protected $supportedMediaTypes = ['text/html', 'application/json'];

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = [
            'json' => 'TYPO3\\Flow\\Mvc\\View\\JsonView',
            'html' => 'TYPO3\\Fluid\\View\\TemplateView'
    ];

    /**
     * @var string
     */
    const  start = 0;

    /**
     * @var string
     */
    const  length = 100;

    /**
     * Returns the list of all Band entities
     */
    public function listAction()
    {
        if ($this->request->getFormat() === 'json') {
            $this->view->setVariablesToRender(['bands']);
        }
        $searchArr = [];
        if ($this->request->hasArgument('columns')) {
            $columns = $this->request->getArgument('columns');
            foreach ($columns as $column) {
                if (!empty($column['data']) && !empty($column['search']['value'])) {
                    $searchArr[$column['data']] = $column['search']['value'];
                }
            }
        }
        if ($this->request->hasArgument('order')) {
            $order = $this->request->getArgument('order');
            if (!empty($order)) {
                $orderDir = $order[0]['dir'];
                $orderById = $order[0]['column'];
                if (!empty($orderById)) {
                    $columns = $this->request->getArgument('columns');
                    $orderBy = $columns[$orderById]['data'];
                }
            }
        }
        if ($this->request->hasArgument('draw')) {
            $draw = $this->request->getArgument('draw');
        } else {
            $draw = 0;
        }
        $start = $this->request->hasArgument('start') ? $this->request->getArgument('start'):self::start;
        $length = $this->request->hasArgument('length') ? $this->request->getArgument('length'):self::length;
        if (empty($searchArr)) {
            if ((isset($orderBy) && !empty($orderBy)) && (isset($orderDir) && !empty($orderDir))) {
                if ($orderDir === 'asc') {
                    $orderArr = [$orderBy => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING];
                } elseif ($orderDir === 'desc') {
                    $orderArr = [$orderBy => \TYPO3\Flow\Persistence\QueryInterface::ORDER_DESCENDING];
                }
            }
            if (isset($orderArr) && !empty($orderArr)) {
                $orderings = $orderArr;
            } else {
                $orderings = ['sortierung' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING];
            }
            $bands = $this->bandRepository->getCertainNumberOfBand($start, $length, $orderings);
            $recordsTotal = $this->bandRepository->getNumberOfEntries();
        } else {
            if ((isset($orderBy) && !empty($orderBy)) && (isset($orderDir) && !empty($orderDir))) {
                if ($orderDir === 'asc') {
                    $orderArr = [$orderBy, 'ASC'];
                } elseif ($orderDir === 'desc') {
                    $orderArr = [$orderBy, 'DESC'];
                }
            }
            if (isset($orderArr) && !empty($orderArr)) {
                $orderings = $orderArr;
            } else {
                $orderings = ['sortierung', 'ASC'];
            }
            $bands = $this->bandRepository->searchCertainNumberOfBand($start, $length, $orderings, $searchArr, 1);
            $recordsFiltered = $this->bandRepository->searchCertainNumberOfBand($start, $length, $orderings, $searchArr, 2);
            $recordsTotal = $this->bandRepository->getNumberOfEntries();
        }
        if (!isset($recordsFiltered)) {
            $recordsFiltered = $recordsTotal;
        }
        $bandArr = [];
        foreach ($bands as $k => $band) {
            if (is_object($band)) {
                $uUID = $band->getUUID();
                if (!empty($uUID)) {
                    $bandArr[$k]['uUID'] = $uUID;
                } else {
                    $bandArr[$k]['uUID'] = '';
                }
                $nummer = $band->getNummer();
                if (!empty($nummer)) {
                    $bandArr[$k]['nummer'] = $nummer;
                } else {
                    $bandArr[$k]['nummer'] = '';
                }
                $titel = $band->getTitel();
                if (!empty($titel)) {
                    $bandArr[$k]['titel'] = $titel;
                } else {
                    $bandArr[$k]['titel'] = '';
                }
                $kurztitel = $band->getKurztitel();
                if (!empty($kurztitel)) {
                    $bandArr[$k]['kurztitel'] = $kurztitel;
                } else {
                    $bandArr[$k]['kurztitel'] = '';
                }
                $sortierung = $band->getSortierung();
                if (!empty($sortierung)) {
                    $bandArr[$k]['sortierung'] = $sortierung;
                } else {
                    $bandArr[$k]['sortierung'] = '';
                }
                $bistumObj = $band->getBistum();
                if (is_object($bistumObj)) {
                    $bistum = $bistumObj->getUUID();
                    if (!empty($bistum)) {
                        $bandArr[$k]['bistum'] = $bistum;
                    } else {
                        $bandArr[$k]['bistum'] = '';
                    }
                }
            }
        }
        $this->view->assign('bands', ['data' => $bandArr, 'draw' => $draw, 'recordsTotal' => $recordsTotal, 'recordsFiltered' => $recordsFiltered]);
        $this->view->assign('bearbeiter', $this->bearbeiterObj->getBearbeiter());
        return $this->view->render();
    }

    /**
     * Create a new Band entity
     */
    public function createAction()
    {
        $bandObj = new Band();
        if (is_object($bandObj)) {
            $bandObj->setNummer($this->request->getArgument('nummer'));
            $bandObj->setTitel($this->request->getArgument('titel'));
            $bandObj->setKurztitel($this->request->getArgument('kurztitel'));
            $bandObj->setSortierung($this->request->getArgument('sortierung'));
            if ($this->request->hasArgument('bistum')) {
                $bistumUUID = $this->request->getArgument('bistum');
                $bistumObj = $this->bistumRepository->findByIdentifier($bistumUUID);
                if (is_object($bistumObj)) {
                    $bandObj->setBistum($bistumObj);
                }
            }
            $this->bandRepository->add($bandObj);
            // Add GND if set
            if ($this->request->hasArgument('gnd')) {
                $gnd = $this->request->getArgument('gnd');
                if ($this->request->hasArgument('gnd_label')) {
                    $gnd_label = $this->request->getArgument('gnd_label');
                }
                if (empty($gnd_label)) {
                    $gndid = str_replace('http://d-nb.info/gnd/', '', trim($gnd));
                    $gnd_label = $this->request->getArgument('nummer') . ' [' . $gndid . ']';
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
                    $bandhasurl = new BandHasUrl();
                    $bandhasurl->setBand($bandObj);
                    $bandhasurl->setUrl($urlObj);
                    $this->bandHasUrlRepository->add($bandhasurl);
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
                    $bandhasurl = new BandHasUrl();
                    $bandhasurl->setBand($bandObj);
                    $bandhasurl->setUrl($urlObj);
                    $this->bandHasUrlRepository->add($bandhasurl);
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
                                } else {
                                    $urlObj->setBemerkung($urlTyp);
                                }
                                $this->urlRepository->add($urlObj);
                                $bandhasurl = new BandHasUrl();
                                $bandhasurl->setBand($bandObj);
                                $bandhasurl->setUrl($urlObj);
                                $this->bandHasUrlRepository->add($bandhasurl);
                            }
                        }
                    }
                }
            }
            $this->persistenceManager->persistAll();
            $this->throwStatus(201, null, null);
        } else {
            $this->throwStatus(400, 'Entity Band not available', null);
        }
    }

    /**
     * Edit a Band entity
     * @return array $bandArr
     */
    public function editAction()
    {
        if ($this->request->hasArgument('uUID')) {
            $uuid = $this->request->getArgument('uUID');
        }
        if (empty($uuid)) {
            $this->throwStatus(400, 'Required uUID not provided', null);
        }
        $bandArr = [];
        $bandObj = $this->bandRepository->findByIdentifier($uuid);
        $bandArr['uUID'] = $bandObj->getUUID();
        $bandArr['nummer'] = $bandObj->getNummer();
        $bandArr['titel'] = $bandObj->getTitel();
        $bandArr['kurztitel'] = $bandObj->getKurztitel();
        $bandArr['sortierung'] = $bandObj->getSortierung();
        $bistum = $bandObj->getBistum();
        if ($bistum) {
            $bandArr['bistum'] = $bistum->getUUID();
        } else {
            $bandArr['bistum'] = '';
        }
        // Band Url data
        $Urls = [];
        $bandHasUrls = $bandObj->getBandHasUrls();
        foreach ($bandHasUrls as $k => $bandHasUrl) {
            $urlObj = $bandHasUrl->getUrl();
            $url = rawurldecode($urlObj->getUrl());
            $url_bemerkung = $urlObj->getBemerkung();
            if ($url !== 'keine Angabe') {
                $urlTypObj = $urlObj->getUrltyp();
                if (is_object($urlTypObj)) {
                    $urlTyp = $urlTypObj->getUUID();
                    $urlTypName = $urlTypObj->getName();
                    if ($urlTypName == 'GND' || $urlTypName == 'Wikipedia') {
                        $Urls[$k] = ['url_typ' => $urlTyp, 'url' => $url, 'url_label' => $url_bemerkung, 'url_typ_name' => $urlTypName];
                    } else {
                        $Urls[$k] = ['url_typ' => $urlTyp, 'url' => $url, 'links_label' => $url_bemerkung, 'url_typ_name' => $urlTypName];
                    }
                }
            }
        }
        $bandArr['url'] = $Urls;
        return json_encode($bandArr);
    }

    /**
     * Update a Band entity
     */
    public function updateAction()
    {
        if ($this->request->hasArgument('uUID')) {
            $uuid = $this->request->getArgument('uUID');
        }
        if (empty($uuid)) {
            $this->throwStatus(400, 'Required uUID not provided', null);
        }
        $bandObj = $this->bandRepository->findByIdentifier($uuid);
        if (is_object($bandObj)) {
            $bandObj->setNummer($this->request->getArgument('nummer'));
            $bandObj->setTitel($this->request->getArgument('titel'));
            $bandObj->setKurztitel($this->request->getArgument('kurztitel'));
            $bandObj->setSortierung($this->request->getArgument('sortierung'));
            if ($this->request->hasArgument('bistum')) {
                $bistumUUID = $this->request->getArgument('bistum');
                $bistumObj = $this->bistumRepository->findByIdentifier($bistumUUID);
                if (is_object($bistumObj)) {
                    $bandObj->setBistum($bistumObj);
                }
            }
            $this->bandRepository->update($bandObj);
            // Fetch Band Urls
            $bandHasUrls = $bandObj->getBandHasUrls();
            $bandHasGND = false;
            // Update GND if set
            if ($this->request->hasArgument('gnd')) {
                $gnd = $this->request->getArgument('gnd');
                if ($this->request->hasArgument('gnd_label')) {
                    $gnd_label = $this->request->getArgument('gnd_label');
                }
                if (empty($gnd_label)) {
                    $gndid = str_replace('http://d-nb.info/gnd/', '', trim($gnd));
                    $gnd_label = $this->request->getArgument('nummer') . ' [' . $gndid . ']';
                }
                if (isset($gnd) && !empty($gnd)) {
                    if (!empty($bandHasUrls)) {
                        foreach ($bandHasUrls as $i => $bandHasUrl) {
                            $urlObj = $bandHasUrl->getUrl();
                            $urlTypObj = $urlObj->getUrltyp();
                            $urlTyp = $urlTypObj->getName();
                            if ($urlTyp == "GND") {
                                $urlObj->setUrl($gnd);
                                if (!empty($gnd_label)) {
                                    $urlObj->setBemerkung($gnd_label);
                                }
                                $this->urlRepository->update($urlObj);
                                $bandHasGND = true;
                            }
                        }
                    }
                    if (!$bandHasGND) {
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
                        $bandhasurl = new BandHasUrl();
                        $bandhasurl->setBand($bandObj);
                        $bandhasurl->setUrl($urlObj);
                        $this->bandHasUrlRepository->add($bandhasurl);
                    }
                }
            }
            //Update Wikipedia if set
            $bandHasWiki = false;
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
                    foreach ($bandHasUrls as $i => $bandHasUrl) {
                        $urlObj = $bandHasUrl->getUrl();
                        $urlTypObj = $urlObj->getUrltyp();
                        $urlTyp = $urlTypObj->getName();
                        if ($urlTyp == "Wikipedia") {
                            $urlObj->setUrl($wikipedia);
                            if (!empty($wikipedia_label)) {
                                $urlObj->setBemerkung($wikipedia_label);
                            }
                            $this->urlRepository->update($urlObj);
                            $bandHasWiki = true;
                        }
                    }
                    if (!$bandHasWiki) {
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
                        $bandhasurl = new BandHasUrl();
                        $bandhasurl->setBand($bandObj);
                        $bandhasurl->setUrl($urlObj);
                        $this->bandHasUrlRepository->add($bandhasurl);
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
                        foreach ($bandHasUrls as $i => $bandHasUrl) {
                            $urlObj = $bandHasUrl->getUrl();
                            $urlTypObj = $urlObj->getUrltyp();
                            $urlTyp = $urlTypObj->getName();
                            if ($urlTyp != "Wikipedia" && $urlTyp != "GND") {
                                $this->bandHasUrlRepository->remove($bandHasUrl);
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
                                } else {
                                    $urlObj->setBemerkung($urlTyp);
                                }
                                $this->urlRepository->add($urlObj);
                                $bandhasurl = new BandHasUrl();
                                $bandhasurl->setBand($bandObj);
                                $bandhasurl->setUrl($urlObj);
                                $this->bandHasUrlRepository->add($bandhasurl);
                            }
                        }
                    }
                }
            }
            $this->persistenceManager->persistAll();
            $this->throwStatus(200, null, null);
        } else {
            $this->throwStatus(400, 'Entity Band not available', null);
        }
    }

    /**
     * Delete an Band entity
     */
    public function deleteAction()
    {
        if ($this->request->hasArgument('uUID')) {
            $uuid = $this->request->getArgument('uUID');
        }
        if (empty($uuid)) {
            $this->throwStatus(400, 'Required uUID not provided', null);
        }
        $klosters = count($this->klosterRepository->findByBand($uuid));
        $bandhasurls = count($this->bandHasUrlRepository->findByOrt($uuid));
        if ($klosters == 0 && $bandhasurls == 0) {
            $bandObj = $this->bandRepository->findByIdentifier($uuid);
            if (!is_object($bandObj)) {
                $this->throwStatus(400, 'Entity Band not available', null);
            }
            $this->bandRepository->remove($bandObj);
            // Fetch Band Urls
            $bandHasUrls = $bandObj->getBandHasUrls();
            if (is_array($bandHasUrls)) {
                foreach ($bandHasUrls as $bandHasUrl) {
                    $this->bandHasUrlRepository->remove($bandHasUrl);
                }
            }
            $this->throwStatus(200, null, null);
        } else {
            $this->throwStatus(400, 'Due to dependencies Band entity could not be deleted', null);
        }
    }

    /**
     * Update a list of Band entities
     */
    public function updateListAction()
    {
        if ($this->request->hasArgument('data')) {
            $bandlist = $this->request->getArgument('data');
        }
        if (empty($bandlist)) {
            $this->throwStatus(400, 'Required data arguemnts not provided', null);
        }
        foreach ($bandlist as $uuid => $band) {
            if (isset($uuid) && !empty($uuid)) {
                $bandObj = $this->bandRepository->findByIdentifier($uuid);
                $bandObj->setNummer($band['nummer']);
                $bandObj->setTitel($band['titel']);
                $bandObj->setKurztitel($band['kurztitel']);
                $bandObj->setSortierung($band['sortierung']);
                $bistumUUID = $band['bistum'];
                $bistum = $this->bistumRepository->findByIdentifier($bistumUUID);
                $bandObj->setBistum($bistum);
                $this->bandRepository->update($bandObj);
            } else {
                $this->throwStatus(400, 'Required uUID not provided', null);
            }
        }
        $this->persistenceManager->persistAll();
        $this->throwStatus(200, null, null);
    }
}
