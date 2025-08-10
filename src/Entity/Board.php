<?php

namespace App\Entity;

use App\Repository\BoardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoardRepository::class)]
class Board
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, BoardList>
     */
    #[ORM\OneToMany(targetEntity: BoardList::class, mappedBy: 'board', orphanRemoval: true)]
    private Collection $lists;

    public function __construct()
    {
        $this->lists = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, BoardList>
     */
    public function getLists(): Collection
    {
        return $this->lists;
    }

    public function addList(BoardList $list): static
    {
        if (!$this->lists->contains($list)) {
            $this->lists->add($list);
            $list->setBoard($this);
        }

        return $this;
    }

    public function removeList(BoardList $list): static
    {
        if ($this->lists->removeElement($list)) {
            // set the owning side to null (unless already changed)
            if ($list->getBoard() === $this) {
                $list->setBoard(null);
            }
        }

        return $this;
    }
}
