<?php
namespace Subugoe\GermaniaSacra\Controller;

use TYPO3\Flow\Annotations as Flow;
use Subugoe\GermaniaSacra\Domain\Model\Bearbeiter;

class BearbeiterController extends AbstractBaseController {

	/**
	 * @Flow\Inject
	 * @var \Subugoe\GermaniaSacra\Domain\Repository\BearbeiterRepository
	 */
	protected $bearbeiterRepository;

	/**
	 * @Flow\Inject
	 * @var \Subugoe\GermaniaSacra\Domain\Repository\KlosterRepository
	 */
	protected $klosterRepository;

	/**
	 * @var \TYPO3\Flow\Security\Policy\PolicyService
	 * @Flow\Inject
	 */
	protected $policyService;

	/**
	 * @var \TYPO3\Flow\Security\Context
	 * @Flow\Inject
	 */
	protected $securityContext;

	/**
	 * @var \TYPO3\Flow\Security\Policy\RoleRepository
	 * @Flow\Inject
	 */
	protected $roleRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\AccountRepository
	 */
	protected $accountRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\AccountFactory
	 */
	protected $accountFactory;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Cryptography\HashService
	 */
	protected $hashService;

	/**
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
	 * @return void
	 */
	public function listAction() {
		if ($this->request->getFormat() === 'json') {
			$this->view->setVariablesToRender(array('bearbeiters'));
		}
		$this->view->assign('bearbeiters', ['data' => $this->bearbeiterRepository->findAll()]);
		$this->view->assign('bearbeiter', $this->bearbeiterObj->getBearbeiter());
	}

	/**
	 * Create a new Bearbeiter entity
	 * @return void
	 */
	public function createAction() {
		$bearbeiterObj = new Bearbeiter();
		if (is_object($bearbeiterObj)) {
			if (!$this->request->hasArgument('bearbeiter')) {
				$this->throwStatus(400, 'Bearbeiter name not provided', Null);
			}
			if ($this->request->hasArgument('role')) {
				$role = array($this->request->getArgument('role'));
			}
			if ($this->request->hasArgument('password')) {
				$password = $this->request->getArgument('password');
			}
			if ($this->request->hasArgument('username')) {
				$identifier = $this->request->getArgument('username');
			}
			if ((isset($role) && !empty($role)) && (isset($password) && !empty($password)) && (isset($identifier) && !empty($identifier))) {
				$account = $this->accountFactory->createAccountWithPassword($identifier,$password, $role);
				$this->accountRepository->add($account);
				$bearbeiterObj->setBearbeiter($this->request->getArgument('bearbeiter'));
				$bearbeiterObj->setAccount($account);
				$this->bearbeiterRepository->add($bearbeiterObj);
				$this->persistenceManager->persistAll();
				$this->throwStatus(201, NULL, Null);
			}
			else {
				$this->throwStatus(400, 'Required data arguemnts not provided', Null);
			}
		}
	}

	/**
	 * Edit a Bearbeiter entity
	 * @return array $bearbeiterArr
	 */
	public function editAction() {
		if ($this->request->hasArgument('uUID')) {
			$uuid = $this->request->getArgument('uUID');
		}
		if (empty($uuid)) {
			$this->throwStatus(400, 'Required uUID not provided', Null);
		}
		$bearbeiterArr = array();
		$bearbeiterObj = $this->bearbeiterRepository->findByIdentifier($uuid);
		$bearbeiterArr['uUID'] = $bearbeiterObj->getUUID();
		$bearbeiterArr['bearbeiter'] = $bearbeiterObj->getBearbeiter();
		$bearbeiterArr['role'] = array_keys($this->securityContext->getAccount()->getRoles())[0];
		return json_encode($bearbeiterArr);
	}

	/**
	 * Update a Bearbeiter entity
	 * @return void
	 */
	public function updateAction() {
		if ($this->request->hasArgument('uUID')) {
			$uuid = $this->request->getArgument('uUID');
		}
		if (empty($uuid)) {
			$this->throwStatus(400, 'Required uUID not provided', Null);
		}
		$bearbeiterObj = $this->bearbeiterRepository->findByIdentifier($uuid);
		if (is_object($bearbeiterObj)) {
			$bearbeiterObj->setBearbeiter($this->request->getArgument('bearbeiter'));
			$account = $bearbeiterObj->getAccount();
			$this->bearbeiterRepository->update($bearbeiterObj);
			if ($this->request->hasArgument('role')) {
				$roleIdentifier = $this->request->getArgument('role');
				if (!empty($roleIdentifier)) {
					$role = array($this->roleRepository->findByIdentifier($roleIdentifier));
					$account->setRoles($role);
				}
			}
			if ($this->request->hasArgument('password')) {
				$password = $this->request->getArgument('password');
				if (!empty($password)) {
					$account->setCredentialsSource($this->hashService->hashPassword($password));
				}
			}
			if ($this->request->hasArgument('username')) {
				$identifier = $this->request->getArgument('username');
				if (!empty($identifier)) {
					$account->setAccountIdentifier($identifier);
				}
			}

			$this->accountRepository->update($account);

			$this->persistenceManager->persistAll();
			$this->throwStatus(200, NULL, Null);
		}
		else {
			$this->throwStatus(400, 'Entity Bearbeiter not available', Null);
		}
	}

	/**
	 * Delete a Bearbeiter entity
	 * @return void
	 */
	public function deleteAction() {
		if ($this->request->hasArgument('uUID')) {
			$uuid = $this->request->getArgument('uUID');
		}
		if (empty($uuid)) {
			$this->throwStatus(400, 'Required uUID not provided', Null);
		}
		$klosters = count($this->klosterRepository->findByBearbeiter($uuid));
		if ($klosters == 0) {
			$bearbeiterObj = $this->bearbeiterRepository->findByIdentifier($uuid);
			$account = $bearbeiterObj->getAccount();
			if (!is_object($bearbeiterObj)) {
				$this->throwStatus(400, 'Entity Bearbeiter not available', Null);
			}
			if (!is_object($account)) {
				$this->throwStatus(400, 'Entity Account not available', Null);
			}
			$this->bearbeiterRepository->remove($bearbeiterObj);
			$this->accountRepository->remove($account);
			$this->throwStatus(200, NULL, Null);
		}
		else {
			$this->throwStatus(400, 'Due to dependencies Bearbeiter entity could not be deleted', Null);
		}
	}

	/**
	 * Update a list of Bearbeiter entities
	 * @return void
	 */
	public function updateListAction() {
		if ($this->request->hasArgument('data')) {
			$bearbeiterlist = $this->request->getArgument('data');
		}
		if (empty($bearbeiterlist)) {
			$this->throwStatus(400, 'Required data arguemnts not provided', Null);
		}
		foreach ($bearbeiterlist as $uuid => $bearbeiter) {
			$bearbeiterObj = $this->bearbeiterRepository->findByIdentifier($uuid);
			$bearbeiterObj->setBearbeiter($bearbeiter['bearbeiter']);
			$this->bearbeiterRepository->update($bearbeiterObj);
		}
		$this->persistenceManager->persistAll();
		$this->throwStatus(200, NULL, Null);
	}
}
?>