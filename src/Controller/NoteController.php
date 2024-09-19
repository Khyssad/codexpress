<?php

namespace App\Controller;

use App\Entity\Note;
use App\Form\NoteType;
use App\Repository\NoteRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;


use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/notes')]

class NoteController extends AbstractController
{
    #[Route('/', name: 'app_note_all')]
    public function all(NoteRepository $nr, PaginatorInterface $paginator, Request $request): Response
    {
        $pagination = $paginator->paginate(
            $nr->findBy(['is_public' => true], ['created_at' => 'DESC']), // Le tableau de données
            $request->query->getInt('page', 1), /*Page en cours*/
            10 /*Nb d'éléments par page*/
        );

        return $this->render('note/all.html.twig', ['allNotes' => $pagination]);
    }

    #[Route('/n/{slug}', name: 'app_note_show')]
    public function show(string $slug, NoteRepository $nr): Response
    {
        // $note = $nr->findOneBySlug($slug);
        // $author= $note->getAuthor();
        // $author= $author->getNotes();

        // if (!$note) {
        //     throw $this->createNotFoundException('La note demandée n\'existe pas');
        // }

        // return $this->render('note/show.html.twig', [
        //     'note' => $note,
        //     'authorNotes' => $author
        // ]);

        $note = $nr->findOneBySlug($slug); //Note
        return $this->render('note/show.html.twig', [
            'note' => $note,
            'authorNotes' => $nr->findByAuthor($note->getAuthor()->getId()),
        ]);
    }

    #[Route('/u/{username}', name: 'app_note_user')]
    public function userNotes(
        string $username,
        UserRepository $user,
    ): Response {
        $author = $user->findOneByUsername($username); // Recherche de l'utilisateur
        return $this->render('note/user.html.twig', [
            'author' => $author, //Envoie les données de l'utilisateur à la vue Twig
            'userNotes' =>  $user->getNotes(), // récupère les notes de l'utilisateur
        ]);
    }


    #[Route('/new', name: 'app_note_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('warning', 'You must be logged in to create a note');
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(NoteType::class); // Chargement du formulaire
        $form = $form->handleRequest($request); // Recuperation des données de la requête POST

        // Traitement des données
        
        if ($form->isSubmitted() && $form->isValid()) {
            $note = new Note();
            $note
                ->setTitle($form->get('title')->getData())
                ->setSlug($slugger->slug($note->getTitle()))
                ->setContent($form->get('content')->getData())
                ->setIsPublic($form->get('is_public')->getData())
                ->setCategory($form->get('category')->getData())
                // ->setAuthor($form->get('author')->getData())
            ;
            $em->persist($note);
            $em->flush();
            $this->addFlash('success', 'Your note has been created');
            return $this->redirectToRoute('app_note_show', ['slug' => $note->getSlug()]);
        }
        return $this->render('note/new.html.twig', [
            'noteForm' => $form
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/edit/{slug}', name: 'app_note_edit', methods: ['GET', 'POST'])]
    public function edit(
        string $slug,
        NoteRepository $nr,
        Request $request,
        EntityManagerInterface $em,

    ): Response {
        $note = $nr->findOneBySlug($slug); //Recheche de la note à modifier

        // Vérifie si l'auteur de la note est le même que l'utilisateur actuellement connecté
        if ($note->getAuthor() !== $this->getUser()) {

            // Affiche un message d'avertissement si ce n'est pas le cas
            $this->addFlash('warning', 'You must be the author to edit a note');

            // Redirige l'utilisateur vers la page de connexion
            return $this->redirectToRoute('app_note_show', ['slug' => $slug]);
        }

        $form = $this->createForm(NoteType::class, $note); // Chargement du formulaire
        $form = $form->handleRequest($request); // Recuperation des données de la requête POST

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($note);
            $em->flush();
            $this->addFlash('success', 'Your note has been updated');
            return $this->redirectToRoute('app_note_show', ['slug' => $note->getSlug()]);
        }
        return $this->render('note/edit.html.twig', [
            'noteForm' => $form
        ]);
    }

    #[Route('/delete/{slug}', name: 'app_note_delete', methods: ['POST'])]
    public function delete(string $slug, NoteRepository $nr): Response
    {
        $note = $nr->findOneBySlug($slug); //Recheche de la note à modifier
        $this->addFlash('success', 'La note a bien été supprimée');
        return $this->redirectToRoute('app_note_user');
    }
}
