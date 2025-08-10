<?php
namespace App\Service;

use App\Entity\Board;
use App\Entity\BoardList;
use App\Entity\Card;
use Doctrine\ORM\EntityManagerInterface;

class BoardManager
{
    public function __construct(private EntityManagerInterface $em) {}

    public function addList(Board $board, string $title): BoardList
    {
        $position = count($board->getLists());

        $list = new BoardList();
        $list->setTitle($title);
        $list->setBoard($board);
        $list->setPosition($position);

        $this->em->persist($list);
        $this->em->flush();

        return $list;
    }

    public function addCard(BoardList $list, string $title, string $description = ''): Card
    {
        $position = count($list->getCards());

        $card = new Card();
        $card->setTitle($title);
        $card->setDescription($description);
        $card->setList($list);
        $card->setPosition($position);

        $this->em->persist($card);
        $this->em->flush();

        return $card;
    }

    public function moveList(BoardList $list, int $newPosition): void
    {
        $board = $list->getBoard();
        $lists = $board->getLists()->toArray();

        usort($lists, fn($a, $b) => $a->getPosition() <=> $b->getPosition());
        $lists = array_values($lists);
        $lists = array_filter($lists, fn($l) => $l->getId() !== $list->getId());

        array_splice($lists, $newPosition, 0, [$list]);

        foreach ($lists as $pos => $l) {
            $l->setPosition($pos);
        }

        $this->em->flush();
    }

    public function moveCard(Card $card, BoardList $targetList, int $newPosition): void
    {
        $sourceList = $card->getList();

        if ($sourceList->getId() === $targetList->getId()) {
            $cards = $sourceList->getCards()->toArray();
            usort($cards, fn($a, $b) => $a->getPosition() <=> $b->getPosition());

            $cards = array_values(array_filter($cards, fn($c) => $c->getId() !== $card->getId()));

            array_splice($cards, $newPosition, 0, [$card]);

            foreach ($cards as $pos => $c) {
                $c->setPosition($pos);
            }
        }
        else {
            $sourceCards = $sourceList->getCards()->toArray();
            usort($sourceCards, fn($a, $b) => $a->getPosition() <=> $b->getPosition());
            $sourceCards = array_values(array_filter($sourceCards, fn($c) => $c->getId() !== $card->getId()));
            foreach ($sourceCards as $pos => $c) {
                $c->setPosition($pos);
            }

            $targetCards = $targetList->getCards()->toArray();
            usort($targetCards, fn($a, $b) => $a->getPosition() <=> $b->getPosition());
            array_splice($targetCards, $newPosition, 0, [$card]);

            foreach ($targetCards as $pos => $c) {
                $c->setList($targetList);
                $c->setPosition($pos);
            }
        }

        $this->em->flush();
    }


    public function removeCard(Card $card): void
    {
        $list = $card->getList();
        $this->em->remove($card);
        $this->em->flush();

        $cards = $list->getCards()->toArray();
        usort($cards, fn($a, $b) => $a->getPosition() <=> $b->getPosition());
        foreach ($cards as $pos => $c) {
            $c->setPosition($pos);
        }
        $this->em->flush();
    }

    public function removeList(BoardList $list): void
    {
        $board = $list->getBoard();
        $this->em->remove($list);
        $this->em->flush();

        $lists = $board->getLists()->toArray();
        usort($lists, fn($a, $b) => $a->getPosition() <=> $b->getPosition());
        foreach ($lists as $pos => $l) {
            $l->setPosition($pos);
        }
        $this->em->flush();
    }
}
