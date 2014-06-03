<?php
namespace Subugoe\GermaniaSacra\Domain\Model;

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class Url {

	/**
	* @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	* @Flow\Inject
	*/
	protected $persistenceManager;

	/**
	 * @var integer
	 * @ORM\Column(columnDefinition="INT(11) NOT NULL AUTO_INCREMENT UNIQUE") 
	 */
	protected $uid;

	/**
	 * @var string
	 */
	protected $url;

	/**
	 * @var string
	 * @ORM\Column(nullable=true)
	 */
	protected $bemerkung;

	/**
	 * @var \Subugoe\GermaniaSacra\Domain\Model\Urltyp
	 * @ORM\ManyToOne(inversedBy="urls")
	 * @ORM\JoinColumn(onDelete="NO ACTION")
	 */
	protected $urltyp;

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
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @param string $url
	 * @return void
	 */
	public function setUrl($url) {
		$this->url = $url;
	}

	/**
	 * @return string
	 */
	public function getBemerkung() {
		return $this->bemerkung;
	}

	/**
	 * @param string $bemerkung
	 * @return void
	 */
	public function setBemerkung($bemerkung) {
		$this->bemerkung = $bemerkung;
	}

	/**
	 * @return \Subugoe\GermaniaSacra\Domain\Model\Urltyp
	 */
	public function getUrltyp() {
		return $this->urltyp;
	}

	/**
	 * @param \Subugoe\GermaniaSacra\Domain\Model\Urltyp $urltyp
	 * @return void
	 */
	public function setUrltyp($urltyp) {
		$this->urltyp = $urltyp;
	}

	public function __toString()
	{
	  return $this->getUrl();
	}

	public function getUUID()
    {
        return $this->persistenceManager->getIdentifierByObject($this);
    }

}
?>