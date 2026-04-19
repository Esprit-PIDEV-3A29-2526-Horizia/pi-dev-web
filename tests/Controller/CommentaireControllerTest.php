<?php

namespace App\Tests\Controller;

use App\Entity\Commentaire;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CommentaireControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<Commentaire> */
    private EntityRepository $commentaireRepository;
    private string $path = '/commentaire/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->commentaireRepository = $this->manager->getRepository(Commentaire::class);

        foreach ($this->commentaireRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Commentaire index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'commentaire[utilisateur_id]' => 'Testing',
            'commentaire[auteur]' => 'Testing',
            'commentaire[contenu]' => 'Testing',
            'commentaire[date_creation]' => 'Testing',
            'commentaire[modifie]' => 'Testing',
            'commentaire[publication]' => 'Testing',
        ]);

        self::assertResponseRedirects('/commentaire');

        self::assertSame(1, $this->commentaireRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new Commentaire();
        $fixture->setUtilisateurId('My Title');
        $fixture->setAuteur('My Title');
        $fixture->setContenu('My Title');
        $fixture->setDateCreation('My Title');
        $fixture->setModifie('My Title');
        $fixture->setPublication('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Commentaire');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new Commentaire();
        $fixture->setUtilisateurId('Value');
        $fixture->setAuteur('Value');
        $fixture->setContenu('Value');
        $fixture->setDateCreation('Value');
        $fixture->setModifie('Value');
        $fixture->setPublication('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'commentaire[utilisateur_id]' => 'Something New',
            'commentaire[auteur]' => 'Something New',
            'commentaire[contenu]' => 'Something New',
            'commentaire[date_creation]' => 'Something New',
            'commentaire[modifie]' => 'Something New',
            'commentaire[publication]' => 'Something New',
        ]);

        self::assertResponseRedirects('/commentaire');

        $fixture = $this->commentaireRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getUtilisateurId());
        self::assertSame('Something New', $fixture[0]->getAuteur());
        self::assertSame('Something New', $fixture[0]->getContenu());
        self::assertSame('Something New', $fixture[0]->getDateCreation());
        self::assertSame('Something New', $fixture[0]->getModifie());
        self::assertSame('Something New', $fixture[0]->getPublication());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new Commentaire();
        $fixture->setUtilisateurId('Value');
        $fixture->setAuteur('Value');
        $fixture->setContenu('Value');
        $fixture->setDateCreation('Value');
        $fixture->setModifie('Value');
        $fixture->setPublication('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/commentaire');
        self::assertSame(0, $this->commentaireRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
