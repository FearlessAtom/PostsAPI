<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use Symfony\Component\Serializer\SerializerInterface;

class PostsController extends AbstractController
{
    #[IsGranted("PUBLIC_ACCESS")]
    #[Route("/api/posts", methods: [Request::METHOD_GET])]
    public function GetPosts(Request $request, EntityManagerInterface $entity_manager,
    SerializerInterface $serializer): Response
    {
        $repository = $entity_manager->getRepository(Post::class);
        $posts = $repository->findAll();

        $json_content = $serializer->serialize($posts, 'json', ['groups' => 'post:read'],
            ["json_encode_options" => JSON_PRETTY_PRINT]);

        return new JsonResponse($json_content, Response::HTTP_OK, [], true);
    }

    #[Route("/api/posts", methods: [Request::METHOD_POST])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function AddPost(Request $request, EntityManagerInterface $entity_manager,
        UserRepository $user_repository, SerializerInterface $serializer, TokenStorageInterface $token_storage): Response
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data["title"]) || !isset($data["content"]) || !isset($data["user_id"]))
        {
            return new JsonResponse(["error" => "Required fields are missing!"], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        $token = $token_storage->getToken();
        $token->getUser();

        $user_interface = $token->getUser();

        $post = new Post();

        $user = $user_repository->GetById($user_interface->getUserIdentifier());

        $post->setTitle($data["title"]);
        $post->setContent($data["content"]);
        $post->setUserId($user);

        $entity_manager->persist($post);
        $entity_manager->flush();

        $json_content = $serializer->serialize($post, "json", ["groups" => "post:read"]);
        return new JsonResponse($json_content, Response::HTTP_CREATED, [], true);
    }

    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    #[Route("/api/posts/{post_id}", methods: [Request::METHOD_DELETE])]
    public function DeletePost(int $post_id, Request $request, UserRepository $user_repository, SerializerInterface $serializer,
        PostRepository $post_repository, TokenStorageInterface $token_storage, EntityManagerInterface $entity_manager) : Response
    {
        $post = $post_repository->GetById($post_id);

        if (!$post)
        {
            return new JsonResponse(["error" => "Post not found!"], Response::HTTP_NOT_FOUND);
        }
        
        $token = $token_storage->getToken();
        $user_id = $token->getUserIdentifier();
        $user = $user_repository->GetById($user_id);

        if ($post->getUserId()->getId() != $user_id && !in_array("ROLE_ADMIN", $user->getRoles()))
        {
            return new JsonResponse(["error" => "You cannot delete a post that wasn't created by you!"],
                Response::HTTP_FORBIDDEN);
        }

        $entity_manager->remove($post);
        $entity_manager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    #[Route("api/posts/{post_id}", methods: [Request::METHOD_PATCH])]
    public function UpdatePost(int $post_id, Request $request, EntityManagerInterface $entity_manager,
        PostRepository $post_repository, TokenStorageInterface $token_storage, UserRepository $user_repository,
        SerializerInterface $serializer): Response
    {
        $post = $post_repository->GetById($post_id);

        if (!$post)
        {
            return new JsonResponse(["error" => "Post not found!"], Response::HTTP_NOT_FOUND);
        }

        $token = $token_storage->getToken();
        $user_id = $token->getUserIdentifier();
        $user = $user_repository->GetById($user_id);

        if ($post->getUserId()->getId() != $user_id && !in_array("ROLE_ADMIN", $user->getRoles()))
        {
            return new JsonResponse(["error" => "You cannot delete a post that wasn't created by you!"],
                Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data["title"]))
        {
            $post->setTitle($data["title"]);
        }

        if (isset($data["content"]))
        {
            $post->setContent($data["content"]);
        }

        $entity_manager->flush();

        $json_content = $serializer->serialize($post, "json", ["groups" => "post:read"]);

        return new Response($json_content, Response::HTTP_OK, [], true);
    }
}
