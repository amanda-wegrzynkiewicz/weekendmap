<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Event\CommentCreatedEvent;
use App\Form\CommentType;
use App\Repository\PostRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller used to manage blog contents in the public part of the site.
 */
#[Route('/blog')]
final class BlogController extends AbstractController
{
    /**
     * NOTE: For standard formats, Symfony will also automatically choose the best
     * Content-Type header for the response.
     *
     * See https://symfony.com/doc/current/routing.html#special-parameters
     */
    #[Route('/rss.xml', name: 'blog_rss', defaults: ['page' => '1', '_format' => 'xml'], methods: ['GET'])]
    #[Route('/page/{page}', name: 'blog_index_paginated', defaults: ['_format' => 'html'], requirements: ['page' => Requirement::POSITIVE_INT], methods: ['GET'])]
    #[Cache(smaxage: 10)]
    public function index(Request $request, int $page, string $_format, PostRepository $posts, TagRepository $tags): Response
    {
        $tag = null;

        if ($request->query->has('tag')) {
            $tag = $tags->findOneBy(['name' => $request->query->get('tag')]);
        }

        $latestPosts = $posts->findLatest($page, $tag);

        // Every template name also has two extensions that specify the format and
        // engine for that template.
        // See https://symfony.com/doc/current/templates.html#template-naming
        return $this->render('blog/index.'.$_format.'.twig', [
            'paginator' => $latestPosts,
            'tagName' => $tag?->getName(),
        ]);
    }

    /**
     * NOTE: when the controller argument is a Doctrine entity, Symfony makes an
     * automatic database query to fetch it based on the value of the route parameters.
     * The '{slug:post}' configuration tells Symfony to use the 'slug' route
     * parameter in the database query that fetches the entity of the $post argument.
     * This is mostly useful when the route has multiple parameters and the controller
     * also has multiple arguments.
     * See https://symfony.com/doc/current/doctrine.html#automatically-fetching-objects-entityvalueresolver.
     */
    #[Route('/posts/{slug:post}', name: 'blog_post', requirements: ['slug' => Requirement::ASCII_SLUG], methods: ['GET'])]
    public function postShow(Post $post): Response
    {
        // Symfony's 'dump()' function is an improved version of PHP's 'var_dump()' but
        // it's not available in the 'prod' environment to prevent leaking sensitive information.
        // It can be used both in PHP files and Twig templates, but it requires to
        // have enabled the DebugBundle. Uncomment the following line to see it in action:
        //
        // dump($post, $this->getUser(), new \DateTime());
        //
        // The result will be displayed either in the Symfony Profiler or in the stream output.
        // See https://symfony.com/doc/current/profiler.html
        // See https://symfony.com/doc/current/templates.html#the-dump-twig-utilities
        //
        // You can also leverage Symfony's 'dd()' function that dumps and
        // stops the execution

        return $this->render('blog/post_show.html.twig', ['post' => $post]);
    }
}
