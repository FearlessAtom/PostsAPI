<?php

namespace App\Controller;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthenticationController extends AbstractController
{
    #[Route("/api/register", methods: [Request::METHOD_POST])]
    public function register(Request $request, EntityManagerInterface $entity_manager, UserRepository $user_repository) : JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data["username"]) || !isset($data["password"]))
        {
            return new JsonResponse(["error" => "Missing required fields!"], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (strlen($data["username"]) > 16 || strlen($data["username"]) < 4)
        {
            return new JsonResponse(["error" => "Invalid username length!"], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($data["password"]) > 32 || strlen($data["password"]) < 8)
        {
            return new JsonResponse(["error" => "Invalid password length!"], Response::HTTP_BAD_REQUEST);
        }

        $user = $user_repository->GetByUsername($data["username"]);

        if ($user)
        {
            return new JsonResponse(["error" => "Username is taken!"], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();

        $password_hash = password_hash($data["password"], PASSWORD_BCRYPT);

        $user->setUsername($data["username"]);
        $user->setPasswordHash($password_hash);

        $entity_manager->persist($user);
        $entity_manager->flush();

        return new JsonResponse(["message" => "User created successfully!"], Response::HTTP_CREATED);
    }
}
