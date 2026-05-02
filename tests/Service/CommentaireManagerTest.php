<?php

namespace App\Tests\Service;

use App\Entity\Commentaire;
use App\Service\CommentaireManager;
use PHPUnit\Framework\TestCase;

class CommentaireManagerTest extends TestCase
{
    public function testValidCommentaire(): void
    {
        $commentaire = new Commentaire();
        $commentaire->setContenu('Très beau voyage, je recommande !');

        $manager = new CommentaireManager();
        
        $this->assertTrue($manager->validate($commentaire));
    }

    public function testCommentaireVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le commentaire ne peut pas être vide.');

        $commentaire = new Commentaire();
        $commentaire->setContenu('');

        $manager = new CommentaireManager();
        $manager->validate($commentaire);
    }

    public function testCommentaireTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le commentaire doit comporter au moins 2 caracteres.');

        $commentaire = new Commentaire();
        $commentaire->setContenu('a');

        $manager = new CommentaireManager();
        $manager->validate($commentaire);
    }

    public function testCommentaireTropLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le commentaire ne peut pas depasser 500 caracteres.');

        $commentaire = new Commentaire();
        $commentaire->setContenu(str_repeat('a', 501));

        $manager = new CommentaireManager();
        $manager->validate($commentaire);
    }

    public function testCommentaireAvecEspaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le commentaire ne peut pas être vide.');

        $commentaire = new Commentaire();
        $commentaire->setContenu('     ');

        $manager = new CommentaireManager();
        $manager->validate($commentaire);
    }

    public function testCommentaireExactement500Caracteres(): void
    {
        $commentaire = new Commentaire();
        $commentaire->setContenu(str_repeat('a', 500));

        $manager = new CommentaireManager();
        
        $this->assertTrue($manager->validate($commentaire));
    }

    public function testValidAuteur(): void
    {
        $commentaire = new Commentaire();
        $commentaire->setAuteur('Jean Dupont');

        $manager = new CommentaireManager();
        
        $this->assertTrue($manager->validateAuteur($commentaire));
    }

    public function testAuteurVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'auteur du commentaire est obligatoire.");

        $commentaire = new Commentaire();
        $commentaire->setAuteur('');

        $manager = new CommentaireManager();
        $manager->validateAuteur($commentaire);
    }

    public function testAuteurNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'auteur du commentaire est obligatoire.");

        $commentaire = new Commentaire();
        $commentaire->setAuteur(null);

        $manager = new CommentaireManager();
        $manager->validateAuteur($commentaire);
    }

    public function testAuteurAvecEspaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'auteur du commentaire est obligatoire.");

        $commentaire = new Commentaire();
        $commentaire->setAuteur('   ');

        $manager = new CommentaireManager();
        $manager->validateAuteur($commentaire);
    }
}