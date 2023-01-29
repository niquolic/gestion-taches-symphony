<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Tache;
use App\Entity\User;
use App\Form\AddTaskType;
use App\Form\RegistrationFormType;
use App\Repository\TacheRepository;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Controller\RegistrationController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use App\Security\AppCustomAuthenticator;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class DefaultController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/", name="app_default")
     */
    public function index(TacheRepository $taskRepository): Response
    {
        // Récupération de l'utilisateur
        $user = $this->getUser();
        // Si on ne récupère pas l'utilisateur, alors en redirige vers la page de login
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        // Récupération des tâches correspondant à l'utilisateur connecté
        $tasks = $taskRepository->findBy(['id_user' => $user->getId()]);

        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController', 'tasks' => $tasks,
        ]);
    }

    /**
     * @Route("/add_task", name="add_task")
     */
    public function add_task(Request $request)
    {
        $task = new Tache();
        // On met l'ID de l'utilisateur connecté au champ id_user
        $task -> setIdUser($this -> getUser() -> getId());
        $form = $this->createForm(AddTaskType::class, $task);
        $form->handleRequest($request);

        // Quand le formulaire est envoyé
        if ($form->isSubmitted() && $form->isValid()) {
            $photo = $form->get('photo')->getData();
            if ($photo) {
                $fichier = md5(uniqid()) . '.' . $photo->guessExtension();
                // Enregistrement du fichier dans le dossier public
                $photo -> move(
                    $this->getParameter('img_task_directory'),
                    $fichier
                );
                $task -> setPhoto($fichier);
            }
            $this->em->persist($task);
            $this->em->flush();
            // Redirection vers la page d'accueil
            return $this->redirectToRoute('app_default');
        }

        // Affiche la page d'ajout de tâche
        return $this->render('actions/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit_task")
     */
    public function edit_task(Tache $task, Request $request)
    {
        $form = $this->createForm(AddTaskType::class, $task);

        // Traitement de la soumission du formulaire
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $photo = $form->get('photo')->getData();
            if ($photo) {
                $fichier = md5(uniqid()) . '.' . $photo->guessExtension();
                // Enregistrement du fichier dans le dossier public
                $photo -> move(
                    $this->getParameter('img_task_directory'),
                    $fichier
                );
                $task -> setPhoto($fichier);
            }else{
                $task -> setPhoto(NULL);
            }
            // Mise à jour de la tâche dans la base de données
            $this->em->flush();

            // Redirige vers la page d'accueil
            return $this->redirectToRoute('app_default');
        }

        // Affichage de la vue pour la modification de la tâche
        return $this->render('actions/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task
        ]);
    }

    /**
     * @Route("/delete_task/{id}", name="delete_task")
     */
    public function delete_task($id)
    {
        // Recherche la tâche par rapport à son ID
        $task = $this->em->getRepository(Tache::class)->find($id);

        if (!$task) {
            throw $this->createNotFoundException(
                'Aucune tache trouvée'.$id
            );
        }

        // Supprime la tâche
        $this->em->remove($task);
        $this->em->flush();

        // Redirige vers la page d'accueil
        return $this->redirectToRoute('app_default');
    }

    /**
     * @Route("/login", name="login")
     */
    public function login()
    {
        // Affiche le formulaire de login
        return $this->render('security/login.html.twig');
    }

    /**
     * @Route("/register", name="register")
     */
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, AppCustomAuthenticator $authenticator, EntityManagerInterface $entityManager, RegistrationController $registrationController)
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        // Affiche le formulaire de création de compte
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
