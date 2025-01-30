<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PostsController
{
    #[Route("/")]
    public function Get() : Response
    {
        return Response("test");
    }
}
