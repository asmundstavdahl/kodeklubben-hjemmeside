<?php











/**
 * Class AdminStaticContentController.
 *
 * @Route("/kontrollpanel")
 */
class AdminStaticContentController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @Route("/statisk_innhold/header", name="cp_sc_header")
     * @Method({"GET", "POST"})
     */
    public function showHeaderAction(Request $request)
    {
        return self::renderForm($request, 'header', 'Header');
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @Route("/statisk_innhold/tagline", name="cp_sc_tagline")
     * @Method({"GET", "POST"})
     */
    public function showTaglineAction(Request $request)
    {
        return self::renderForm($request, 'tagline', 'Tagline');
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @Route("/statisk_innhold/deltaker", name="cp_sc_participant_info")
     * @Method({"GET", "POST"})
     */
    public function showParticipantAction(Request $request)
    {
        return self::renderForm($request, 'participant_info', 'Deltaker');
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @Route("/statisk_innhold/veileder", name="cp_sc_tutor_info")
     * @Method({"GET", "POST"})
     */
    public function showTutorAction(Request $request)
    {
        return self::renderForm($request, 'tutor_info', 'Veileder');
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @Route("/statisk_innhold/om_deltakere", name="cp_sc_about_participant")
     * @Method({"GET", "POST"})
     */
    public function showAboutParticipantAction(Request $request)
    {
        return self::renderForm($request, 'about_participant', 'Om oss - For Deltakere');
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @Route("/statisk_innhold/om_veiledere", name="cp_sc_about_tutor")
     * @Method({"GET", "POST"})
     */
    public function showAboutTutorAction(Request $request)
    {
        return self::renderForm($request, 'about_tutor', 'Om oss - For Veiledere');
    }

    private function renderForm(Request $request, $idString, $label)
    {
        $club = self::get('club_manager')->getCurrentClub();
        $content = self::getDoctrine()->getRepository('AppBundle:StaticContent')->findOneByStringId($idString, $club);
        if (is_null($content)) {
            $content = new StaticContent();
            $content->setIdString($idString);
            $content->setClub($club);
        }

        $form = self::createForm(StaticContentType::class, $content, array(
            'label' => $label,
        ));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $content->setLastEditedBy(self::getUser());
            $content->setLastEdited(new \DateTime());
            $manager = self::getDoctrine()->getManager();
            $manager->persist($content);
            $manager->flush();

            return self::redirectToRoute('cp_sc_'.$idString);
        }

        return self::render('static_content/show_form.html.twig', array(
            'form' => $form->createView(),
            'name' => $label,
        ));
    }
}
