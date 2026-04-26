<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un utilisateur admin pour le développement local.',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Email de l\'admin', 'admin@localhost')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Mot de passe', 'admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getOption('email');
        $password = $input->getOption('password');

        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            $io->success("Un utilisateur avec l'email {$email} existe déjà. Tu peux te connecter avec.");
            return Command::SUCCESS;
        }

        // Réaligner la séquence id (au cas où des INSERT avec id explicite ont été faits en migration)
        $conn = $this->em->getConnection();
        $conn->executeStatement("SELECT setval('users_id_seq', (SELECT COALESCE(MAX(id), 0) FROM users))");

        $user = new User();
        $user->setEmail($email);
        $user->setUsername('Admin');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setActive(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $io->success("Admin créé : email = {$email}, mot de passe = {$password}");
        $io->note('Connecte-toi sur http://localhost:4200/admin/login en local.');
        return Command::SUCCESS;
    }
}
