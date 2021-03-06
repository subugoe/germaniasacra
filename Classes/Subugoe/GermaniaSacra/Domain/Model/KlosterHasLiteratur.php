<?php
namespace Subugoe\GermaniaSacra\Domain\Model;

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class KlosterHasLiteratur
{
    /**
     * @var \Subugoe\GermaniaSacra\Domain\Model\Kloster
     * @ORM\ManyToOne(inversedBy="klosterHasLiteraturs")
     * @ORM\JoinColumn(onDelete="NO ACTION", nullable=false)
     */
    protected $kloster;

    /**
     * @var \Subugoe\GermaniaSacra\Domain\Model\Literatur
     * @ORM\ManyToOne(inversedBy="klosterHasLiteraturs")
     * @ORM\JoinColumn(onDelete="NO ACTION", nullable=false)
     */
    protected $literatur;

    /**
     * @return \Subugoe\GermaniaSacra\Domain\Model\Kloster
     */
    public function getKloster()
    {
        return $this->kloster;
    }

    /**
     * @param \Subugoe\GermaniaSacra\Domain\Model\Kloster $kloster
     */
    public function setKloster(\Subugoe\GermaniaSacra\Domain\Model\Kloster $kloster)
    {
        $this->kloster = $kloster;
    }


    /**
     * @return \Subugoe\GermaniaSacra\Domain\Model\Literatur
     */
    public function getLiteratur()
    {
        return $this->literatur;
    }

    /**
     * @param \Subugoe\GermaniaSacra\Domain\Model\Literatur $literatur
     */
    public function setLiteratur(\Subugoe\GermaniaSacra\Domain\Model\Literatur $literatur)
    {
        $this->literatur = $literatur;
    }
}
