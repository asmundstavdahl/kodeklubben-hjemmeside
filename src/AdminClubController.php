<?php









class AdminClubController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/kontrollpanel/info", name="cp_info")
     * @Method({"GET", "POST"})
     */
    public function showAction(Request $request)
    {
        $club = self::get('club_manager')->getCurrentClub();
        $form = self::createForm(ClubType::class, $club);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager = self::getDoctrine()->getManager();
            $manager->persist($club);
            $manager->flush();

            return self::redirectToRoute('cp_info');
        }

        return self::render('control_panel/club_info.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
