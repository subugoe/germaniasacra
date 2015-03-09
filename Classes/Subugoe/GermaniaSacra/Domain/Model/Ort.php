<?php
namespace Subugoe\GermaniaSacra\Domain\Model;

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class Ort {

	/**
	* @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	* @Flow\Inject
	*/
	protected $persistenceManager;

	/**
	 * @var integer
	 * @ORM\Column(nullable=true)
	 */
	protected $uid;

	/**
	 * @var string
	 */
	protected $ort;

	/**
	 * @var string
	 * @ORM\Column(nullable=true)
	 */
	protected $gemeinde;

	/**
	 * @var string
	 * @ORM\Column(nullable=true)
	 */
	protected $kreis;

	/**
	 * @var integer
	 * @ORM\Column(nullable=true)
	 */
	protected $wuestung;

	/**
	 * @var float
	 * @ORM\Column(nullable=true)
	 */
	protected $breite;

	/**
	 * @var float
	 * @ORM\Column(nullable=true)
	 */
	protected $laenge;

	/**
	 * @var \Subugoe\GermaniaSacra\Domain\Model\Land
	 * @ORM\ManyToOne(inversedBy="orts")
	 * @ORM\JoinColumn(onDelete="NO ACTION")
	 * @ORM\Column(nullable=true)
	 */
	protected $land;

	/**
	 * @var \Subugoe\GermaniaSacra\Domain\Model\Bistum
	 * @ORM\ManyToOne(inversedBy="orts")
	 * @ORM\JoinColumn(onDelete="NO ACTION")
	 * @ORM\Column(nullable=true)
	 */
	protected $bistum;

	/**
	 * @var \Doctrine\Common\Collections\Collection<\Subugoe\GermaniaSacra\Domain\Model\OrtHasUrl>
	 * @ORM\OneToMany(mappedBy="ort", cascade={"all"})
	 * @ORM\JoinColumn(onDelete="NO ACTION", nullable=false)
	 */
	protected $ortHasUrls;

	/**
	 * @return integer
	 */
	public function getuid() {
		return $this->uid;
	}

	/**
	 * @param integer $uid
	 * @return void
	 */
	public function setuid($uid) {
		$this->uid = $uid;
	}

	/**
	 * @return string
	 */
	public function getOrt() {
		return $this->ort;
	}

	/**
	 * @return string
	 */
	public function getFullOrt() {
		$fullOrt = $this->ort;
		$ortDetails = array();
		if (isset($this->gemeinde) && !empty($this->gemeinde)) {
			$ortDetails[] = $this->gemeinde;
		}
		if (isset($this->kreis) && !empty($this->kreis)) {
			$ortDetails[] = $this->kreis;
		}
		if (isset($this->bistum) && !empty($this->bistum)) {
			$ortDetails[] = $this->bistum;
		}
		if ( !empty($ortDetails )) {
			$fullOrt .= ' (' . join(', ', $ortDetails) . ')';
		}
		return $fullOrt;
	}

	/**
	 * @param string $ort
	 * @return void
	 */
	public function setOrt($ort) {
		$this->ort = $ort;
	}

	/**
	 * @return string
	 */
	public function getGemeinde() {
		return $this->gemeinde;
	}

	/**
	 * @param string $gemeinde
	 * @return void
	 */
	public function setGemeinde($gemeinde) {
		$this->gemeinde = $gemeinde;
	}

	/**
	 * @return string
	 */
	public function getKreis() {
		return $this->kreis;
	}

	/**
	 * @param string $kreis
	 * @return void
	 */
	public function setKreis($kreis) {
		$this->kreis = $kreis;
	}

	/**
	 * @return integer
	 */
	public function getWuestung() {
		return $this->wuestung;
	}

	/**
	 * @param string $wuestung
	 * @return void
	 */
	public function setWuestung($wuestung) {
		$this->wuestung = $wuestung;
	}

	/**
	 * @return float
	 */
	public function getBreite() {
		return $this->breite;
	}

	/**
	 * @param string $breite
	 * @return void
	 */
	public function setBreite($breite) {
		$this->breite = $breite;
	}

	/**
	 * @return float
	 */
	public function getLaenge() {
		return $this->laenge;
	}

	/**
	 * @param string $laenge
	 * @return void
	 */
	public function setLaenge($laenge) {
		$this->laenge = $laenge;
	}

	/**
	 * @return \Subugoe\GermaniaSacra\Domain\Model\Land
	 */
	public function getLand() {
		return $this->land;
	}

	/**
	 * @param \Subugoe\GermaniaSacra\Domain\Model\Land $land
	 * @return void
	 */
	public function setLand(\Subugoe\GermaniaSacra\Domain\Model\Land $land) {
		$this->land = $land;
	}

	/**
	 * @return \Subugoe\GermaniaSacra\Domain\Model\Bistum
	 */
	public function getBistum() {
		return $this->bistum;
	}

	/**
	 * @param \Subugoe\GermaniaSacra\Domain\Model\Bistum $bistum
	 * @return void
	 */
	public function setBistum(\Subugoe\GermaniaSacra\Domain\Model\Bistum $bistum) {
		$this->bistum = $bistum;
	}

	/**
	 * @return \Doctrine\Common\Collections\Collection<\Subugoe\GermaniaSacra\Domain\Model\OrtHasUrl>
	 */
	public function getOrtHasUrls() {
		return $this->ortHasUrls;
	}

	/**
	 * @param \Doctrine\Common\Collections\Collection $ortHasUrls
	 * @return void
	 */
	public function setOrtHasUrls(\Doctrine\Common\Collections\Collection $ortHasUrls) {
		foreach ($ortHasUrls as $ortHasUrl) {
			$ortHasUrl->setOrt($this);
		}

		$this->ortHasUrls = $ortHasUrls;
	}

	/**
	 * @return string
	 */
	public function __toString() {
	  return $this->getOrt();
	}

	/**
	 * Returns the persistence object identifier of the object
	 * @return string
	 */
	public function getUUID() {
        return $this->persistenceManager->getIdentifierByObject($this);
    }
}
?>