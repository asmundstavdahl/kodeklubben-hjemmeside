<?php











/**
 * Class AdminSponsorController.
 *
 * @Route("/kontrollpanel")
 */
class AdminSponsorController extends Controller
{
    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/sponsors", name="cp_sponsors")
     * @Method({"GET", "POST"})
     */
    public function showSponsorsAction(Request $request)
    {
        $currentClub = self::get("club_manager")->getCurrentClub();
        $sponsors = self::getDoctrine()->getRepository(Sponsor::class)->findAllByClub($currentClub);
        return self::render("sponsor/manage.html.twig", [
            "sponsors" => $sponsors
        ]);
    }

    /**
     * @param Request $request
     * @param Sponsor $sponsor
     *
     * @return Response
     *
     * @Route("/sponsors/{sponsor}/edit", name="cp_edit_sponsor")
     * @Method({"GET", "POST"})
     */
    public function editSponsorAction(Request $request, Sponsor $sponsor)
    {
        #$currentClub = self::get("club_manager")->getCurrentClub();
        #$sponsors = self::getDoctrine()->getRepository(Sponsor::class)->findAllByClub($currentClub);

        $form = self::createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager = self::getDoctrine()->getManager();
            $manager->persist($sponsor);
            $manager->flush();

            return self::redirectToRoute('cp_sponsors');
        }

        return self::render("sponsor/edit.html.twig", [
            "sponsor" => $sponsor,
            "form" => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/sponsors/new", name="cp_new_sponsor")
     * @Method({"GET", "POST"})
     */
    public function newSponsorAction(Request $request)
    {
        $currentClub = self::get("club_manager")->getCurrentClub();

        $sponsor = new Sponsor();
        $sponsor->setClub($currentClub);

        $form = self::createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager = self::getDoctrine()->getManager();
            $manager->persist($sponsor);
            $manager->flush();

            return self::redirectToRoute('cp_sponsors');
        }

        return self::render("sponsor/edit.html.twig", [
            "sponsor" => $sponsor,
            "form" => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param Sponsor $sponsor
     *
     * @return Response
     *
     * @Route("/sponsors/{sponsor}/delete", name="cp_delete_sponsor")
     * @Method({"GET"})
     */
    public function deleteSponsorAction(Request $request, Sponsor $sponsor)
    {
        $em = self::getDoctrine()->getEntityManager();
        $em->remove($sponsor);
        $em->flush();

        return self::redirectToRoute('cp_sponsors');
    }

    /**
     * @param Request $request
     * @param Sponsor $sponsor
     *
     * @return Response
     *
     * @Route("/sponsors/{sponsor}/image", name="cp_edit_sponsor_image")
     * @Method({"GET"})
     */
    public function editSponsorImageAction(Request $request, Sponsor $sponsor)
    {
        $currentClub = self::get("club_manager")->getCurrentClub();
        
        $image = self::getDoctrine()
                    ->getRepository(\AppBundle\Entity\Image::class)
                    ->findByClubAndName($currentClub, $sponsor->getImageName());

        return self::render("image/editable_image_template.html.twig", [
            "image" => $image,
        ]);
    }
}
