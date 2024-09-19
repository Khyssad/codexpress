<?php

namespace App\Controller;

use App\Repository\NoteRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function search(Request $request,NoteRepository $nr, PaginatorInterface $paginator): Response
    {

        $searchQuery = $request->query->get('q');
        if(!$searchQuery){
            return $this->render('search/results.html.twig');
        }//Si l'accès à la page




        $pagination = $paginator->paginate(
            $nr->findByQuery($searchQuery),// Le tableau de données
            $request->query->getInt('page', 1), /*Page en cours*/
            24 /*Nb d'éléments par page*/
        );
           
        return $this->render('search/results.html.twig', [
            'allNotes' => $pagination,
            'searchQuery' => $searchQuery,
        ]);

    }
}
