<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ReservationRepository;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_reservation = null;

    public function getId_reservation(): ?int
    {
        return $this->id_reservation;
    }

    public function setId_reservation(int $id_reservation): self
    {
        $this->id_reservation = $id_reservation;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'id_event', referencedColumnName: 'id')]
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
    private ?int $nombrepersonnes = null;

    public function getNombrepersonnes(): ?int
    {
        return $this->nombrepersonnes;
    }

    public function setNombrepersonnes(int $nombrepersonnes): self
    {
        $this->nombrepersonnes = $nombrepersonnes;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $qrcode_path = null;

    public function getQrcode_path(): ?string
    {
        return $this->qrcode_path;
    }

    public function setQrcode_path(?string $qrcode_path): self
    {
        $this->qrcode_path = $qrcode_path;
        return $this;
    }

    public function getIdReservation(): ?int
    {
        return $this->id_reservation;
    }

    public function getQrcodePath(): ?string
    {
        return $this->qrcode_path;
    }

    public function setQrcodePath(?string $qrcode_path): static
    {
        $this->qrcode_path = $qrcode_path;

        return $this;
    }

}
