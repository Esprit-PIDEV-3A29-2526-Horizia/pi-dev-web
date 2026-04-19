<?php

namespace App\Tests\Controller;

use App\Entity\Publication;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PublicationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<Publication> */
    private EntityRepository $publicationRepository;
    private string $path = '/yes/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->publicationRepository = $this->manager->getRepository(Publication::class);

        foreach ($this->publicationRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Publication index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'publication[titre]' => 'Testing',
            'publication[description]' => 'Testing',
            'publication[image]' => 'Testing',
            'publication[categorie]' => 'Testing',
            'publication[utilisateur_id]' => 'Testing',
            'publication[auteur]' => 'Testing',
            'publication[likes]' => 'Testing',
            'publication[commentaires]' => 'Testing',
            'publication[date_creation]' => 'Testing',
        ]);

        self::assertResponseRedirects('/yes');

        self::assertSame(1, $this->publicationRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new Publication();
        $fixture->setTitre('My Title');
        $fixture->setDescription('My Title');
        $fixture->setImage('My Title');
        $fixture->setCategorie('My Title');
        $fixture->setUtilisateurId('My Title');
        $fixture->setAuteur('My Title');
        $fixture->setLikes('My Title');
        $fixture->setCommentaires('My Title');
        $fixture->setDateCreation('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Publication');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new Publication();
        $fixture->setTitre('Value');
        $fixture->setDescription('Value');
        $fixture->setImage('Value');
        $fixture->setCategorie('Value');
        $fixture->setUtilisateurId('Value');
        $fixture->setAuteur('Value');
        $fixture->setLikes('Value');
        $fixture->setCommentaires('Value');
        $fixture->setDateCreation('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'publication[titre]' => 'Something New',
            'publication[description]' => 'Something New',
            'publication[image]' => 'Something New',
            'publication[categorie]' => 'Something New',
            'publication[utilisateur_id]' => 'Something New',
            'publication[auteur]' => 'Something New',
            'publication[likes]' => 'Something New',
            'publication[commentaires]' => 'Something New',
            'publication[date_creation]' => 'Something New',
        ]);

        self::assertResponseRedirects('/yes');

        $fixture = $this->publicationRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getTitre());
        self::assertSame('Something New', $fixture[0]->getDescription());
        self::assertSame('Something New', $fixture[0]->getImage());
        self::assertSame('Something New', $fixture[0]->getCategorie());
        self::assertSame('Something New', $fixture[0]->getUtilisateurId());
        self::assertSame('Something New', $fixture[0]->getAuteur());
        self::assertSame('Something New', $fixture[0]->getLikes());
        self::assertSame('Something New', $fixture[0]->getCommentaires());
        self::assertSame('Something New', $fixture[0]->getDateCreation());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new Publication();
        $fixture->setTitre('Value');
        $fixture->setDescription('Value');
        $fixture->setImage('Value');
        $fixture->setCategorie('Value');
        $fixture->setUtilisateurId('Value');
        $fixture->setAuteur('Value');
        $fixture->setLikes('Value');
        $fixture->setCommentaires('Value');
        $fixture->setDateCreation('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/yes');
        self::assertSame(0, $this->publicationRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
