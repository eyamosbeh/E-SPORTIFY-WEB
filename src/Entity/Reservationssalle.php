<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ReservationssalleRepository;

#[ORM\Entity(repositoryClass: ReservationssalleRepository::class)]
#[ORM\Table(name: 'reservationssalle')]
class Reservationssalle
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

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id_salle = null;

    public function getId_salle(): ?int
    {
        return $this->id_salle;
    }

    public function setId_salle(int $id_salle): self
    {
        $this->id_salle = $id_salle;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $date_debut = null;

    public function getDate_debut(): ?string
    {
        return $this->date_debut;
    }

    public function setDate_debut(string $date_debut): self
    {
        $this->date_debut = $date_debut;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $date_fin = null;

    public function getDate_fin(): ?string
    {
        return $this->date_fin;
    }

    public function setDate_fin(string $date_fin): self
    {
        $this->date_fin = $date_fin;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $statut = null;

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getIdSalle(): ?int
    {
        return $this->id_salle;
    }

    public function setIdSalle(int $id_salle): static
    {
        $this->id_salle = $id_salle;

        return $this;
    }

    public function getDateDebut(): ?string
    {
        return $this->date_debut;
    }

    public function setDateDebut(string $date_debut): static
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    public function getDateFin(): ?string
    {
        return $this->date_fin;
    }

    public function setDateFin(string $date_fin): static
    {
        $this->date_fin = $date_fin;

        return $this;
    }

}
