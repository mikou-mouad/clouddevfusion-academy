<?php

namespace App\Command;

use App\Entity\BlogPost;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-blog-posts',
    description: 'Crée quelques articles de blog de démo pour l\'admin.',
)]
class SeedBlogPostsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = count($this->em->getRepository(BlogPost::class)->findAll());
        if ($count > 0) {
            $io->note("Il y a déjà {$count} article(s). Pas d'ajout pour éviter les doublons.");
            return Command::SUCCESS;
        }

        $posts = [
            [
                'title' => 'Bienvenue sur le blog CloudDev',
                'slug' => 'bienvenue-blog-clouddev',
                'excerpt' => 'Découvrez nos articles sur Azure, les certifications et les bonnes pratiques.',
                'content' => '<p>Bienvenue sur le blog de CloudDev Fusion. Vous trouverez ici des articles sur Microsoft Azure, les certifications et des retours d\'expérience.</p>',
                'category' => 'Azure',
                'author' => 'Admin',
                'readTime' => 2,
                'published' => true,
            ],
            [
                'title' => 'Préparer la certification AZ-900',
                'slug' => 'preparer-certification-az900',
                'excerpt' => 'Conseils et ressources pour réussir l\'examen Microsoft Azure Fundamentals.',
                'content' => '<p>La certification AZ-900 (Microsoft Azure Fundamentals) est un excellent point de départ. Voici quelques conseils pour bien vous préparer.</p>',
                'category' => 'Certification tips',
                'author' => 'Admin',
                'readTime' => 5,
                'published' => true,
            ],
            [
                'title' => 'Brouillon : prochain article',
                'slug' => 'brouillon-prochain-article',
                'excerpt' => '',
                'content' => '<p>Contenu à rédiger.</p>',
                'category' => 'Updates',
                'author' => 'Admin',
                'readTime' => 1,
                'published' => false,
            ],
        ];

        foreach ($posts as $data) {
            $post = new BlogPost();
            $post->setTitle($data['title']);
            $post->setSlug($data['slug']);
            $post->setExcerpt($data['excerpt']);
            $post->setContent($data['content']);
            $post->setCategory($data['category']);
            $post->setAuthor($data['author']);
            $post->setReadTime($data['readTime']);
            $post->setPublished($data['published']);
            $this->em->persist($post);
        }

        $this->em->flush();
        $io->success(count($posts) . ' article(s) de démo créés. Rafraîchis la page Blog dans l\'admin pour les voir.');

        return Command::SUCCESS;
    }
}
