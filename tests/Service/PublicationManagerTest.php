<?php

namespace App\Tests\Service;

use App\Entity\Publication;
use App\Service\PublicationManager;  // Changé ici
use PHPUnit\Framework\TestCase;

class PublicationManagerTest extends TestCase  // Changé ici
{
    public function testValidPublication(): void
    {
        $publication = new Publication();
        $publication->setTitre('Voyage à Paris');
        $publication->setDescription('Un super voyage de 10 jours dans la capitale française.');

        $manager = new PublicationManager();  // Changé ici
        
        $this->assertTrue($manager->validate($publication));  // Changé ici
    }

    public function testPublicationWithoutTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre de la publication est obligatoire.');

        $publication = new Publication();
        $publication->setDescription('Une longue description de plus de 10 caracteres.');

        $manager = new PublicationManager();  // Changé ici
        $manager->validate($publication);  // Changé ici
    }

    public function testPublicationWithTitleTooShort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre doit comporter au moins 3 caracteres.');

        $publication = new Publication();
        $publication->setTitre('Ab');
        $publication->setDescription('Description correcte assez longue.');

        $manager = new PublicationManager();  // Changé ici
        $manager->validate($publication);  // Changé ici
    }

    public function testPublicationWithoutDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description est obligatoire.');

        $publication = new Publication();
        $publication->setTitre('Titre valide');

        $manager = new PublicationManager();  // Changé ici
        $manager->validate($publication);  // Changé ici
    }

    public function testPublicationWithTooShortDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description doit comporter au moins 10 caracteres.');

        $publication = new Publication();
        $publication->setTitre('Voyage');
        $publication->setDescription('123456789'); // 9 caracteres

        $manager = new PublicationManager();  // Changé ici
        $manager->validate($publication);  // Changé ici
    }

    public function testPublicationWithEmptyDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description est obligatoire.');

        $publication = new Publication();
        $publication->setTitre('Voyage');
        $publication->setDescription('');

        $manager = new PublicationManager();  // Changé ici
        $manager->validate($publication);  // Changé ici
    }

    public function testPublicationWithSpacesOnlyDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description est obligatoire.');

        $publication = new Publication();
        $publication->setTitre('Voyage');
        $publication->setDescription('     ');

        $manager = new PublicationManager();  // Changé ici
        $manager->validate($publication);  // Changé ici
    }
}