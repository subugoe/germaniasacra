<?php
namespace Subugoe\GermaniaSacra\Controller;

use TYPO3\Flow\Annotations as Flow;
use Subugoe\GermaniaSacra\Domain\Model\Personallistenstatus;

class PersonallistenstatusController extends AbstractBaseController
{
    /**
     * @Flow\Inject
     * @var \Subugoe\GermaniaSacra\Domain\Repository\PersonallistenstatusRepository
     */
    protected $personallistenstatusRepository;

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
     * Returns the list of all Personallistenstatus entities
     */
    public function listAction()
    {
        if ($this->request->getFormat() === 'json') {
            $this->view->setVariablesToRender(['personallistenstatus']);
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
                $orderings = ['land' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING];
            }
            $personallistenstatus = $this->personallistenstatusRepository->getCertainNumberOfPersonallistenstatus($start, $length, $orderings);
            $recordsTotal = $this->personallistenstatusRepository->getNumberOfEntries();
            $recordsFiltered = $recordsTotal;
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
                $orderings = ['land', 'ASC'];
            }
            $personallistenstatus = $this->personallistenstatusRepository->searchCertainNumberOfPersonallistenstatus($start, $length, $orderings, $searchArr, 1);
            $recordsFiltered = $this->personallistenstatusRepository->searchCertainNumberOfPersonallistenstatus($start, $length, $orderings, $searchArr, 2);
            $recordsTotal = $this->personallistenstatusRepository->getNumberOfEntries();
        }
        if (!isset($recordsFiltered)) {
            $recordsFiltered = $recordsTotal;
        }
        $this->view->assign('personallistenstatus', ['data' => $personallistenstatus, 'draw' => $draw, 'recordsTotal' => $recordsTotal, 'recordsFiltered' => $recordsFiltered]);
        $this->view->assign('bearbeiter', $this->bearbeiterObj->getBearbeiter());
        return $this->view->render();
    }

    /**
     * Create a new Personallistenstatus entity
     */
    public function createAction()
    {
        $personallistenstatusObj = new Personallistenstatus();
        if (is_object($personallistenstatusObj)) {
            if (!$this->request->hasArgument('name')) {
                $this->throwStatus(400, 'Personallistenstatus name not provided', null);
            }
            $personallistenstatusObj->setName($this->request->getArgument('name'));
            $this->personallistenstatusRepository->add($personallistenstatusObj);
            $this->persistenceManager->persistAll();
            $this->throwStatus(201, null, null);
        }
    }

    /**
     * Edit a Personallistenstatus entity
     * @return array $personallistenstatusArr
     */
    public function editAction()
    {
        if ($this->request->hasArgument('uUID')) {
            $uuid = $this->request->getArgument('uUID');
        }
        if (empty($uuid)) {
            $this->throwStatus(400, 'Required uUID not provided', null);
        }
        $personallistenstatusArr = [];
        $personallistenstatusObj = $this->personallistenstatusRepository->findByIdentifier($uuid);
        $personallistenstatusArr['uUID'] = $personallistenstatusObj->getUUID();
        $personallistenstatusArr['name'] = $personallistenstatusObj->getName();
        return json_encode($personallistenstatusArr);
    }

    /**
     * Update a Personallistenstatus entity
     */
    public function updateAction()
    {
        if ($this->request->hasArgument('uUID')) {
            $uuid = $this->request->getArgument('uUID');
        }
        if (empty($uuid)) {
            $this->throwStatus(400, 'Required uUID not provided', null);
        }
        $personallistenstatusObj = $this->personallistenstatusRepository->findByIdentifier($uuid);
        if (is_object($personallistenstatusObj)) {
            $personallistenstatusObj->setName($this->request->getArgument('name'));
            $this->personallistenstatusRepository->update($personallistenstatusObj);
            $this->persistenceManager->persistAll();
            $this->throwStatus(200, null, null);
        } else {
            $this->throwStatus(400, 'Entity Personallistenstatus not available', null);
        }
    }

    /**
     * Delete a Personallistenstatus entity
     */
    public function deleteAction()
    {
        if ($this->request->hasArgument('uUID')) {
            $uuid = $this->request->getArgument('uUID');
        }
        if (empty($uuid)) {
            $this->throwStatus(400, 'Required uUID not provided', null);
        }
        $klosters = count($this->klosterRepository->findByPersonallistenstatus($uuid));
        if ($klosters == 0) {
            $personallistenstatusObj = $this->personallistenstatusRepository->findByIdentifier($uuid);
            if (!is_object($personallistenstatusObj)) {
                $this->throwStatus(400, 'Entity Personallistenstatus not available', null);
            }
            $this->personallistenstatusRepository->remove($personallistenstatusObj);
            $this->throwStatus(200, null, null);
        } else {
            $this->throwStatus(400, 'Due to dependencies Personallistenstatus entity could not be deleted', null);
        }
    }

    /**
     * Update a list of Personallistenstatus entities
     */
    public function updateListAction()
    {
        if ($this->request->hasArgument('data')) {
            $personallistenstatuslist = $this->request->getArgument('data');
        }
        if (empty($personallistenstatuslist)) {
            $this->throwStatus(400, 'Required data arguemnts not provided', null);
        }
        foreach ($personallistenstatuslist as $uuid => $personallistenstatus) {
            $personallistenstatusObj = $this->personallistenstatusRepository->findByIdentifier($uuid);
            $personallistenstatusObj->setName($personallistenstatus['name']);
            $this->personallistenstatusRepository->update($personallistenstatusObj);
        }
        $this->persistenceManager->persistAll();
        $this->throwStatus(200, null, null);
    }
}
