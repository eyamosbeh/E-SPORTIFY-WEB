<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ReactionRepository;

#[ORM\Entity(repositoryClass: ReactionRepository::class)]
#[ORM\Table(name: 'reactions')]
class Reaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\OneToOne(targetEntity: Evenement::class, inversedBy: 'reaction')]
    #[ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id', unique: true)]
    private ?Evenement $evenement = null;

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): self
    {
        $this->evenement = $evenement;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id_joueur = null;

    public function getId_joueur(): ?int
    {
        return $this->id_joueur;
    }

    public function setId_joueur(int $id_joueur): self
    {
        $this->id_joueur = $id_joueur;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $type = null;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getIdJoueur(): ?int
    {
        return $this->id_joueur;
    }

    public function setIdJoueur(int $id_joueur): static
    {
        $this->id_joueur = $id_joueur;

        return $this;
    }

}
