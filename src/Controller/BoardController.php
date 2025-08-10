<?php

namespace App\Controller;

use App\Entity\Board;
use App\Entity\BoardList;
use App\Entity\Card;
use App\Service\BoardManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/board')]
class BoardController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        // Si POST → création board
        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name', 'Nouveau board'));

            $board = new Board();
            $board->setName($name);

            $em->persist($board);
            $em->flush();

            return $this->redirectToRoute('board_show', ['id' => $board->getId()]);
        }

        $boards = $em->getRepository(Board::class)->findAll();

        return $this->render('board/index.html.twig', [
            'boards' => $boards,
        ]);
    }

    #[Route('/{id}', name: 'board_show', methods: ['GET'])]
    public function show(Board $board): Response
    {
        return $this->render('board/show.html.twig', [
            'board' => $board,
        ]);
    }

    #[Route('/{id}/add-list', name: 'board_add_list', methods: ['POST'])]
    public function addList(Board $board, Request $request, BoardManager $manager): Response
    {
        $title = trim($request->request->get('title', 'Nouvelle liste'));
        $manager->addList($board, $title);

        return $this->redirectToRoute('board_show', ['id' => $board->getId()]);
    }

    #[Route('/list/{id}/add-card', name: 'list_add_card', methods: ['POST'])]
    public function addCard(BoardList $list, Request $request, BoardManager $manager): Response
    {
        $title = trim($request->request->get('title', 'Nouvelle carte'));
        $description = $request->request->get('description', '');
        $manager->addCard($list, $title, $description);

        return $this->redirectToRoute('board_show', ['id' => $list->getBoard()->getId()]);
    }

    #[Route('/list/{id}/move', name: 'list_move', methods: ['POST'])]
    public function moveList(BoardList $list, Request $request, BoardManager $manager): JsonResponse
    {
        $newPosition = (int) $request->request->get('position');
        $manager->moveList($list, $newPosition);

        return $this->json(['success' => true]);
    }

    #[Route('/card/{id}/move', name: 'card_move', methods: ['POST'])]
    public function moveCard(Card $card, Request $request, EntityManagerInterface $em, BoardManager $manager): JsonResponse
    {
        $listId = $request->request->get('listId');
        $newPosition = (int) $request->request->get('position');

        $newList = $em->getRepository(BoardList::class)->find($listId);

        if (!$newList) {
            return $this->json(['error' => 'List not found'], 404);
        }

        $manager->moveCard($card, $newList, $newPosition);

        return $this->json(['success' => true]);
    }

    #[Route('/list/{id}/delete', name: 'list_delete', methods: ['POST'])]
    public function deleteList(BoardList $list, BoardManager $manager): Response
    {
        $boardId = $list->getBoard()->getId();
        $manager->removeList($list);

        return $this->redirectToRoute('board_show', ['id' => $boardId]);
    }

    #[Route('/card/{id}/delete', name: 'card_delete', methods: ['POST'])]
    public function deleteCard(Card $card, BoardManager $manager): Response
    {
        $boardId = $card->getList()->getBoard()->getId();
        $manager->removeCard($card);

        return $this->redirectToRoute('board_show', ['id' => $boardId]);
    }
}