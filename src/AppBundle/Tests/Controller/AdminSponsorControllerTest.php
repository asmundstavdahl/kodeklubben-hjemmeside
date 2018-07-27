<?php

namespace AppBundle\Test\Controller;

use AppBundle\Tests\AppWebTestCase;
use AppBundle\Entity\Sponsor;

class AdminSponsorControllerTest extends AppWebTestCase
{
    private $entityManager;
    private $clubManager;

    protected function setUp()
    {
        $container = \TestDataManager::$kernel->getContainer();
        $this->entityManager = $container->get("doctrine")->getManager();
        $this->clubManager = $container->get("club_manager");
    }

    /**
     * @group sponsor
     */
    public function testNewSponsor()
    {
        $client = $this->getAdminClient();

        $crawler = $this->goToSuccessful($client, '/');

        $sponsorName = "New Sponsor";
        $sponsorUrl = "http://sponsor.no/?ref=kk#kodeklubben";

        // Assert that new content does not already exist on home page
        $this->assertEquals(0, $crawler->filter("h3:contains('$sponsorName')")->count());

        $crawler = $this->goToSuccessful($client, '/kontrollpanel/sponsors/new');

        $form = $crawler->selectButton("Lagre")->first()->form();

        $form['sponsor[name]'] = $sponsorName;
        $form['sponsor[url]'] = $sponsorUrl;

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect('/kontrollpanel/sponsors'));

        $crawler = $this->goToSuccessful($client, '/');

        // Assert that new content now exists on home page
        $this->assertEquals(1, $crawler->filter("h3:contains('$sponsorName')")->count());
        $this->assertEquals(1, $crawler->filter("a:contains('$sponsorName')")->count());
        $this->assertEquals($sponsorUrl, $crawler->filter("a:contains('$sponsorName')")->attr("href"));

        \TestDataManager::restoreDatabase();
    }
    /**
     * @group sponsor
     */
    public function testEditSponsor()
    {
        $client = $this->getAdminClient();

        $sponsorName = "Sponsor to be edited";
        $sponsorUrl = "http://sponsor.no/?ref=kk#kodeklubben";
        $currentClub = $this->clubManager->getCurrentClub();

        $sponsor = new Sponsor();
        $sponsor->setName($sponsorName);
        $sponsor->setUrl($sponsorUrl);
        $sponsor->setClub($currentClub);

        $this->entityManager->persist($sponsor);
        $this->entityManager->flush();

        $sponsorId = $sponsor->getId();
        
        $crawler = $this->goToSuccessful($client, '/');

        $this->assertEquals(1, $crawler->filter("h3:contains('$sponsorName')")->count());

        $crawler = $this->goToSuccessful($client, "/kontrollpanel/sponsors/{$sponsorId}/edit");

        $form = $crawler->selectButton("Lagre")->first()->form();

        $form['sponsor[name]'] = $sponsorNameEdited = "{$sponsorName} edited";
        $form['sponsor[url]'] = $sponsorUrlEdited = "{$sponsorUrl} edited";

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect('/kontrollpanel/sponsors'));

        $crawler = $this->goToSuccessful($client, '/');

        // Assert that new content now exists on home page
        $this->assertEquals(1, $crawler->filter("h3:contains('$sponsorNameEdited')")->count());
        $this->assertEquals(1, $crawler->filter("a:contains('$sponsorNameEdited')")->count());
        $this->assertEquals($sponsorUrlEdited, $crawler->filter("a:contains('$sponsorNameEdited')")->attr("href"));

        \TestDataManager::restoreDatabase();
    }
    /**
     * @group sponsor
     */
    public function testDeleteSponsor()
    {
        $client = $this->getAdminClient();

        $sponsorName = "Sponsor to be deleted";
        $sponsorUrl = "http://settmeg.no";
        $currentClub = $this->clubManager->getCurrentClub();

        $sponsor = new Sponsor();
        $sponsor->setName($sponsorName);
        $sponsor->setUrl($sponsorUrl);
        $sponsor->setClub($currentClub);

        $this->entityManager->persist($sponsor);
        $this->entityManager->flush();

        $sponsorId = $sponsor->getId();
        
        $crawler = $this->goToSuccessful($client, '/');
        $this->assertEquals(1, $crawler->filter("h3:contains('$sponsorName')")->count());

        $crawler = $this->goToSuccessful($client, "/kontrollpanel/sponsors");
        $deleteUri = $crawler->selectLink("Slett")->link()->getUri();
        $deletePath = "/kontrollpanel/sponsors/{$sponsorId}/delete";
        $this->assertNotEqual(-1, strpos($deleteUri, $deletePath));
        $crawler = $this->goToSuccessful($client, $deletePath);

        $this->assertTrue($client->getResponse()->isRedirect('/kontrollpanel/sponsors'));

        // Assert that new content now exists on home page
        $crawler = $this->goToSuccessful($client, '/');
        $this->assertEquals(0, $crawler->filter("h3:contains('$sponsorName')")->count());
        $this->assertEquals(0, $crawler->filter("a:contains('$sponsorName')")->count());

        \TestDataManager::restoreDatabase();
    }
}
